<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ExpenseSheet;
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
            $monthNum = (int) $d->format('n'); // 1..12
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Mois invalide'], 400);
        }

        // 2) /api/months/janvier?year=2025 (optionnel)
        $year = request()->integer('year');

        // 3) Agrégation par note + JOIN forms
        $rows = DB::table('expense_sheets as es')
            ->join('expense_sheet_costs as esc', 'esc.expense_sheet_id', '=', 'es.id')
            ->leftJoin('forms as f', 'f.id', '=', 'es.form_id')
            // décommente si tu veux restreindre par organisation :
            // ->where('es.organization_id', $user->organization_id)
            ->when(! $user->can('viewAll', \App\Models\ExpenseSheet::class), function ($q) use ($user) {
                $q->where('es.user_id', $user->id);
            })
            // ⚠️ Filtre sur la date de création de la NOTE (et pas des coûts)
            ->whereMonth('es.created_at', $monthNum)
            ->when($year, fn($q) => $q->whereYear('es.created_at', $year))
            ->groupBy('es.id', 'es.status', 'es.created_at', 'es.user_id', 'es.department_id', 'es.form_id', 'f.name')
            ->select([
                'es.id',
                'es.status',
                'es.created_at',
                'es.user_id',
                'es.department_id',
                'es.form_id',
                DB::raw('COALESCE(f.name, "Formulaire") as form_name'),

                // Montant total "d’origine" de la note :
                // - percentage: amount (payé par l’agent)
                // - autres (km, fixed, ...): total (montant remboursé = montant d’origine)
                DB::raw("
                SUM(
                    CASE
                        WHEN LOWER(esc.type) = 'percentage'
                            THEN COALESCE(esc.amount, 0)
                        ELSE COALESCE(esc.total, 0)
                    END
                ) as total_original
            "),

                // Montant remboursable = somme des 'total'
                DB::raw('SUM(COALESCE(esc.total, 0)) as reimbursable'),

                // Part non remboursable = (amount - total) pour les 'percentage', sinon 0
                DB::raw("
                SUM(
                    CASE
                        WHEN LOWER(esc.type) = 'percentage'
                            THEN CASE
                                     WHEN (COALESCE(esc.amount,0) - COALESCE(esc.total,0)) > 0
                                         THEN (COALESCE(esc.amount,0) - COALESCE(esc.total,0))
                                     ELSE 0
                                 END
                        ELSE 0
                    END
                ) as non_reimbursable
            "),
            ])
            ->orderByDesc('es.created_at')
            ->get();

        // 4) Enrichissement minimal (user / department)
        $userIds = $rows->pluck('user_id')->unique()->all();
        $deptIds = $rows->pluck('department_id')->unique()->all();

        $users = \App\Models\User::whereIn('id', $userIds)->get(['id','name','email'])->keyBy('id');
        $depts = \App\Models\Department::whereIn('id', $deptIds)->get(['id','name'])->keyBy('id');

        // 5) Payload
        $payload = $rows->map(function ($r) use ($users, $depts) {
            $totalOriginal   = (float) $r->total_original;
            $remboursable    = (float) $r->reimbursable;
            $nonRemboursable = (float) $r->non_reimbursable;

            return [
                'id' => (int) $r->id,
                'status' => $r->status,
                'created_at' => (string) $r->created_at,
                'form' => [
                    'id' => (int) $r->form_id,
                    'name' => $r->form_name,
                ],
                // ce que tu affiches en "montant total de la note"
                'total' => $totalOriginal,
                'remboursable' => $remboursable,
                'non_remboursable' => $nonRemboursable,
                'user' => $users[$r->user_id] ?? null,
                'department' => $depts[$r->department_id] ?? null,
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
