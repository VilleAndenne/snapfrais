<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\ExpenseSheet;
use App\Models\ExpenseSheetCost;
use App\Models\Form;
use App\Models\FormCost;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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
     * GET /api/forms/{id}/expense-sheet/create
     * Renvoie: form(costs+rates+requirements), departments (avec users, heads), authUser (id, name, email)
     */
    public function createPayload($id)
    {
        $form = Form::with('costs.reimbursementRates', 'costs.requirements')->findOrFail($id);

        return response()->json([
            'form' => $form,
            'departments' => auth()->user()
                ->departments()
                ->with(['users:id,name', 'heads:id,name'])
                ->get(['name']),
            'authUser' => auth()->user()->only(['id', 'name', 'email']),
        ]);
    }

    /**
     * POST /api/forms/{id}/expense-sheets
     * Body: multipart/form-data (costs[...][...] + fichiers)
     * -> Équivalent de ton store() web, mais JSON en sortie
     */
    public function store(Request $request, $id)
    {
        $validated = $request->validate([
            'costs' => 'required|array|max:7',
            'costs.*.cost_id' => 'required|exists:form_costs,id',
            'costs.*.data' => 'required|array',
            'costs.*.date' => 'required|date',
            'costs.*.requirements' => 'nullable|array',
            'department_id' => 'required|exists:departments,id',
            'target_user_id' => 'nullable|exists:users,id',
        ]);

        // Département + relations
        $department = Department::with(['heads:id', 'users:id'])->findOrFail($validated['department_id']);
        $currentUserId = auth()->id();
        $targetUserId = $request->input('target_user_id');

        // Autorisation si encode pour autrui
        if ($targetUserId && (int)$targetUserId !== (int)$currentUserId) {
            $isHead = $department->heads->contains('id', $currentUserId);
            if (!$isHead) {
                return response()->json([
                    'message' => "Vous devez être responsable du service pour encoder au nom d'un agent."
                ], 403);
            }
            $belongsToDept = $department->users->contains('id', (int)$targetUserId);
            if (!$belongsToDept) {
                return response()->json([
                    'errors' => ['target_user_id' => ["L'agent sélectionné n'appartient pas à ce service."]],
                ], 422);
            }
        }

        // Création
        $expenseSheet = ExpenseSheet::create([
            'user_id'         => $targetUserId ?: $currentUserId,
            'created_by'      => $currentUserId,
            'status'          => 'En attente',
            'total'           => 0,
            'form_id'         => $id,
            'department_id'   => $validated['department_id'],
        ]);

        $globalTotal = 0;

        foreach ($validated['costs'] as $i => $costItem) {
            $formCost = FormCost::find($costItem['cost_id']);
            $type = $formCost->type;
            $date = $costItem['date'];

            // Taux actif
            $rates = $formCost->reimbursementRates()
                ->where('start_date', '<=', $date)
                ->where(function ($q) use ($date) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', $date);
                })
                ->orderByDesc('start_date')
                ->get();

            if ($rates->count() === 0) {
                // Pas de taux -> on ignore l’item
                continue;
            }
            if ($rates->count() > 1) {
                $expenseSheet->delete();
                return response()->json([
                    'message' => "Configuration invalide : plusieurs taux actifs le $date pour le coût \"{$formCost->name}\". Veuillez corriger."
                ], 422);
            }

            $rate = $rates->first();
            $transport = $rate->transport ?? 'car';

            $data = $costItem['data'];
            $total = 0;
            $distance = null;
            $googleDistance = null;
            $route = null;

            if ($type === 'km') {
                // Deux options:
                // A) Le mobile envoie totalKm déjà calculé -> on l’utilise (recommandé côté UX)
                // B) Sinon, on peut recalculer ici avec l’endpoint /maps/distance
                $manualKm = (float)($data['manualKm'] ?? 0);
                $clientKm = isset($data['totalKm']) ? (float)$data['totalKm'] : 0.0;

                // Si tu veux forcer le calcul serveur, remets l’appel Directions comme dans ton contrôleur web
                $googleKm = isset($data['googleKm']) ? (float)$data['googleKm'] : 0.0;

                $googleDistance = round($googleKm, 2);
                $distance = round($googleKm + $clientKm + $manualKm, 2);
                $total = round($distance * (float)$rate->value, 2);

                $route = [
                    'departure'      => $data['departure'] ?? null,
                    'arrival'        => $data['arrival'] ?? null,
                    'google_km'      => $googleKm,
                    'client_km'      => $clientKm,
                    'manual_km'      => $manualKm,
                    'justification'  => $data['justification'] ?? null,
                    'transport'      => $transport,
                    'steps'          => $data['steps'] ?? [],
                ];
            } elseif ($type === 'fixed') {
                $total = round((float)$rate->value, 2);
            } elseif ($type === 'percentage') {
                $paid = (float)($data['paidAmount'] ?? 0);
                $total = round($paid * ((float)$rate->value / 100), 2);
            }

            // Requirements (fichiers + texte)
            $requirements = [];
            if (isset($costItem['requirements']) && is_array($costItem['requirements'])) {
                foreach ($costItem['requirements'] as $key => $req) {
                    // fichier: costs[i][requirements][key][file]
                    if ($request->hasFile("costs.$i.requirements.$key.file")) {
                        $file = $request->file("costs.$i.requirements.$key.file");
                        $path = Storage::putFile('expense_requirements', $file);
                        $requirements[$key] = ['file' => Storage::url($path)];
                    } else {
                        $val = data_get($req, 'value');
                        if ($val !== null && $val !== '') {
                            $requirements[$key] = ['value' => $val];
                        }
                    }
                }
            }

            $expenseSheet->costs()->create([
                'form_cost_id'     => $formCost->id,
                'type'             => $type,
                'distance'         => $distance,
                'google_distance'  => $googleDistance,
                'route'            => $route,
                'total'            => $total,
                'date'             => $date,
                'amount'           => $data['paidAmount'] ?? null,
                'requirements'     => json_encode($requirements),
                'expense_sheet_id' => $expenseSheet->id,
            ]);

            $globalTotal += $total;
        }

        if ($globalTotal <= 0) {
            $expenseSheet->delete();
            return response()->json([
                'message' => 'Le total de la note de frais ne peut pas être nul. Veuillez vérifier les coûts saisis.'
            ], 422);
        }

        $expenseSheet->update(['total' => $globalTotal]);

        // Notifications identiques à ton web
        $user = auth()->user();
        $department = $expenseSheet->department;
        $heads = $department->heads;
        if ($heads->contains($user) && $department->parent) {
            $heads = $department->parent->heads;
        }

        $heads->each(function ($head) use ($expenseSheet) {
            $head->notify(new \App\Notifications\ExpenseSheetToApproval($expenseSheet));
        });

        if (auth()->id() !== $expenseSheet->user->id) {
            $expenseSheet->creator->notify(new \App\Notifications\ReceiptExpenseSheetForUser($expenseSheet));
        }
        $expenseSheet->user->notify(new \App\Notifications\ReceiptExpenseSheet($expenseSheet));

        return response()->json([
            'message' => 'Note de frais enregistrée.',
            'expense_sheet' => $expenseSheet->load('costs'),
        ], 201);
    }

    /**
     * POST /api/maps/distance
     * Body JSON:
     * {
     *   "points": ["A", "B", "C", "D"],
     *   "transport": "bike"|"car"
     * }
     * -> Retourne { googleKm: number }
     */
    public function computeDistance(Request $request)
    {
        $validated = $request->validate([
            'points' => 'required|array|min:2',
            'points.*' => 'required|string',
            'transport' => 'nullable|in:bike,car',
        ]);

        $mode = ($validated['transport'] ?? 'car') === 'bike' ? 'bicycling' : 'driving';

        $googleMeters = 0;
        $points = $validated['points'];

        foreach (range(0, count($points)-2) as $i) {
            $params = [
                'origin'      => $points[$i],
                'destination' => $points[$i+1],
                'mode'        => $mode,
                'key'         => env('GOOGLE_MAPS_API_KEY'),
            ];
            $response = Http::get('https://maps.googleapis.com/maps/api/directions/json', $params);
            $json = $response->json();

            if ($response->successful()
                && ($json['status'] ?? null) === 'OK'
                && isset($json['routes'][0]['legs'][0]['distance']['value'])) {
                $googleMeters += (int)$json['routes'][0]['legs'][0]['distance']['value'];
            }
        }

        return response()->json([
            'googleKm' => round($googleMeters / 1000, 2)
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
