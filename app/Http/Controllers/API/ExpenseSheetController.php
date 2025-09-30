<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ExpenseSheet;
use App\Models\ExpenseSheetCost;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\DB;

class ExpenseSheetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'expenseSheets' => ExpenseSheet::with('form', 'costs', 'department.heads', 'user')
                ->orderBy('created_at', 'desc')
                ->get()
                ->filter(fn($expenseSheet) => auth()->user()->can('view', $expenseSheet))
                ->values()
                ->all(),
        ]);
    }

    public function summary(): JsonResponse
    {
        $user = auth()->user();

        $summary = ExpenseSheet::with(['costs.formCost'])
            ->get()
            ->filter(fn($sheet) => $user->can('view', $sheet)) // respect des policies
            ->flatMap(fn($sheet) => $sheet->costs) // on descend au niveau des coûts
            ->groupBy(function ($cost) {
                return Carbon::parse($cost->date)->format('Y-m'); // regroupe par mois/année
            })
            ->map(function ($costs, $monthYear) {
                $total = $costs->sum('total');
                $remboursable = $costs->filter(fn($c) => $c->formCost->is_reimbursable ?? true)->sum('total');
                $nonRemboursable = $total - $remboursable;

                return [
                    'month_year' => $monthYear,
                    'total' => $total,
                    'remboursable' => $remboursable,
                    'non_remboursable' => $nonRemboursable,
                ];
            })
            ->values();

        return response()->json([
            'summary' => $summary
        ]);
    }

    public function monthsSummary(string $month): JsonResponse
    {
        $user = auth()->user();

        // 1) "janvier" -> 1
        try {
            $d = Carbon::createFromLocaleFormat('F', 'fr', mb_strtolower($month));
            $monthNum = (int)$d->format('n'); // 1..12
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Mois invalide'], 400);
        }

        // 2) /api/months/janvier?year=2025 (optionnel)
        $year = request()->integer('year');

        // 3) Agrégations via sous-requêtes Eloquent (pas de GREATEST)
        $rows = ExpenseSheet::query()
            ->with([
                'form:id,name',
                'user:id,name,email',
                'department:id,name',
            ])
            // ->where('organization_id', $user->organization_id) // si besoin
            ->when(!$user->can('viewAll', \App\Models\ExpenseSheet::class), fn($q) => $q->where('user_id', $user->id))
            ->whereMonth('created_at', $monthNum)
            ->when($year, fn($q) => $q->whereYear('created_at', $year))
            ->addSelect([
                'expense_sheets.id',
                'expense_sheets.status',
                'expense_sheets.created_at',
                'expense_sheets.user_id',
                'expense_sheets.department_id',
                'expense_sheets.form_id',
            ])
            // remboursable = SUM(total)
            ->withSum('costs as reimbursable', 'total')
            // total_original = SUM(CASE WHEN lower(type)='percentage' THEN amount ELSE total END)
            ->addSelect([
                'total_original' => ExpenseSheetCost::selectRaw("
                COALESCE(SUM(
                    CASE
                        WHEN LOWER(type) = 'percentage' THEN COALESCE(amount, 0)
                        ELSE COALESCE(total, 0)
                    END
                ), 0)
            ")->whereColumn('expense_sheet_id', 'expense_sheets.id'),
            ])
            // non_remboursable = SUM(CASE WHEN lower(type)='percentage' THEN
            //                                CASE WHEN (amount - total) > 0 THEN (amount - total) ELSE 0 END
            //                             ELSE 0 END)
            ->addSelect([
                'non_reimbursable' => ExpenseSheetCost::selectRaw("
                COALESCE(SUM(
                    CASE
                        WHEN LOWER(type) = 'percentage'
                            THEN CASE
                                    WHEN (COALESCE(amount,0) - COALESCE(total,0)) > 0
                                         THEN (COALESCE(amount,0) - COALESCE(total,0))
                                    ELSE 0
                                 END
                        ELSE 0
                    END
                ), 0)
            ")->whereColumn('expense_sheet_id', 'expense_sheets.id'),
            ])
            ->orderByDesc('created_at')
            ->get();

        // 4) Payload
        $payload = $rows->map(function ($r) {
            $formName = optional($r->form)->name ?? 'Formulaire';

            return [
                'id' => (int)$r->id,
                'status' => $r->status,
                'created_at' => (string)$r->created_at,
                'form' => [
                    'id' => (int)$r->form_id,
                    'name' => $formName,
                ],
                'total' => (float)$r->total_original,
                'remboursable' => (float)($r->reimbursable ?? 0),
                'non_remboursable' => (float)$r->non_reimbursable,
                'user' => $r->user ? [
                    'id' => (int)$r->user->id,
                    'name' => $r->user->name,
                    'email' => $r->user->email,
                ] : null,
                'department' => $r->department ? [
                    'id' => (int)$r->department->id,
                    'name' => $r->department->name,
                ] : null,
            ];
        })->values();

        return response()->json($payload);
    }

    /**
     * Display all resource which user have access to.
     */

    public function all()
    {
        $expenseSheets = ExpenseSheet::with('form', 'costs', 'department.heads', 'user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(fn($expenseSheet) => auth()->user()->can('view', $expenseSheet))
            ->values()
            ->all();

        return response()->json([
            'expenseSheets' => $expenseSheets,
        ]);
    }

    /**
     * Display a listing of the resource to validate by the user.
     */

    public function validateIndex(): JsonResponse
    {
        return response()->json([
            'expenseSheets' => ExpenseSheet::with('form', 'costs', 'department.heads', 'user')
                ->where('validated', null)
                ->orderBy('created_at', 'desc')
                ->get()
                ->filter(fn($expenseSheet) => auth()->user()->can('approve', $expenseSheet)),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $expenseSheet = ExpenseSheet::findOrFail($id);
        if (!auth()->user()->can('view', $expenseSheet)) {
            abort(403);
        }
        //        return $expenseSheet->load(['costs.formCost', 'costs.steps', 'user', 'department', 'costs.formCost.reimbursementRates']);
        $canApprove = auth()->user()->can('approve', $expenseSheet);
        $canReject = auth()->user()->can('reject', $expenseSheet);
        $canEdit = auth()->user()->can('edit', $expenseSheet);
        return response()->json([
            'expenseSheet' => $expenseSheet->load(['costs.formCost', 'user', 'department', 'costs.formCost.reimbursementRates', 'validatedBy']),
            'canApprove' => $canApprove,
            'canReject' => $canReject,
            'canEdit' => $canEdit,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
