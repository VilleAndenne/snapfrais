<?php

namespace App\Http\Controllers;

use App\Models\ExpenseSheet;
use App\Models\Form;
use App\Models\FormCost;
use App\Notifications\ApprovalExpenseSheet;
use App\Notifications\ReceiptExpenseSheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class ExpenseSheetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $expenseSheets = ExpenseSheet::with('form', 'costs', 'department.heads', 'user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(fn($expenseSheet) => auth()->user()->can('view', $expenseSheet))
            ->values()
            ->all();

        return Inertia::render('expenseSheet/Index', [
            'expenseSheets' => $expenseSheets,
            'canExport' => auth()->user()->can('export', ExpenseSheet::class),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */

    public function create($id)
    {
        $form = Form::with('costs.reimbursementRates', 'costs.requirements')->findOrFail($id);

        return inertia('expenseSheet/Create', [
            'form' => $form,
            'departments' => auth()->user()->departments()->with('heads')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
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
        ]);

        $expenseSheet = ExpenseSheet::create([
            'user_id' => auth()->id(),
            'status' => 'En attente',
            'total' => 0,
            'form_id' => $id,
            'department_id' => $validated['department_id'],
            'organization_id' => auth()->user()->organization_id,
        ]);

        $globalTotal = 0;

        foreach ($validated['costs'] as $costItem) {
            $formCost = FormCost::find($costItem['cost_id']);
            $type = $formCost->type;
            $date = $costItem['date'];

            // Récupération du taux de remboursement actif à la date du coût
            $rate = $formCost->reimbursementRates()
                ->where('start_date', '<=', $date)
                ->where(function ($q) use ($date) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', $date);
                })
                ->orderByDesc('start_date')
                ->first();

            if (!$rate) {
                continue;
            }

            $data = $costItem['data'];
            $total = 0;
            $distance = null;
            $googleDistance = null;
            $route = null;

            if ($type === 'km') {
                $origin = $data['departure'] ?? null;
                $destination = $data['arrival'] ?? null;
                $steps = $data['steps'] ?? [];
                $manualKm = $data['manualKm'] ?? 0;

                if (!$origin || !$destination) {
                    continue;
                }

                $points = array_merge([$origin], $steps, [$destination]);
                $googleKm = 0;

                foreach (range(0, count($points) - 2) as $i) {
                    $segmentOrigin = $points[$i];
                    $segmentDest = $points[$i + 1];

                    $params = [
                        'origin' => $segmentOrigin,
                        'destination' => $segmentDest,
                        'mode' => 'driving',
                        'key' => env('GOOGLE_MAPS_API_KEY'),
                    ];

                    $response = Http::get("https://maps.googleapis.com/maps/api/directions/json", $params);
                    $json = $response->json();

                    if ($response->successful() && $json['status'] === 'OK' && isset($json['routes'][0]['legs'][0]['distance']['value'])) {
                        $googleKm += $json['routes'][0]['legs'][0]['distance']['value'];
                    }
                }

                $googleKm = round($googleKm / 1000, 2);
                $googleDistance = $googleKm;
                $distance = $googleKm + $manualKm;
                $total = round($distance * $rate->value, 2);

                $route = [
                    'departure' => $origin,
                    'arrival' => $destination,
                    'google_km' => $googleKm,
                    'manual_km' => $manualKm,
                    'justification' => $data['justification'] ?? null,
                ];

                // In route, we can also save the steps if needed
                if (count($steps) > 0) {
                    $route['steps'] = [];
                    foreach ($steps as $index => $address) {
                        $route['steps'][] = [
                            'address' => $address,
                            'order' => $index + 1,
                        ];
                    }
                }

            } elseif ($type === 'fixed') {
                $total = round($rate->value, 2);
            } elseif ($type === 'percentage') {
                $paid = $data['paidAmount'] ?? 0;
                $total = round($paid * ($rate->value / 100), 2);
            }

            // Gestion des requirements sous forme de JSON
            $requirements = [];
            if (isset($costItem['requirements'])) {
                foreach ($costItem['requirements'] as $key => $requirement) {
                    if (is_array($requirement) && isset($requirement['file']) && $requirement['file'] instanceof \Illuminate\Http\UploadedFile) {
                        $path = Storage::url(Storage::putFile($requirement['file']));
                        $requirements[$key] = ['file' => $path];
                    } elseif (is_array($requirement) && isset($requirement['value'])) {
                        $requirements[$key] = ['value' => $requirement['value']];
                    }
                }
            }

            // Création du coût avec requirements stockés en JSON
            $createdCost = $expenseSheet->costs()->create([
                'form_cost_id' => $formCost->id,
                'type' => $type,
                'distance' => $distance,
                'google_distance' => $googleDistance,
                'route' => $route,
                'total' => $total,
                'date' => $date,
                'amount' => $data['paidAmount'] ?? null,
                'requirements' => json_encode($requirements),  // Enregistrement des requirements en JSON
                'expense_sheet_id' => $expenseSheet->id
            ]);

            $globalTotal += $total;
        }

        $expenseSheet->update(['total' => $globalTotal]);

        $user = auth()->user();
        $department = $expenseSheet->department;

// Récupère les responsables
        $heads = $department->heads;

// Si l'agent est lui-même head du service, alors on passe au parent
        if ($heads->contains($user) && $department->parent) {
            $heads = $department->parent->heads;
        }

        $heads->each(function ($head) use ($expenseSheet) {
            $head->notify(new \App\Notifications\ExpenseSheetToApproval($expenseSheet));
        });

        $expenseSheet->user->notify(new ReceiptExpenseSheet($expenseSheet));

        return redirect()->route('dashboard')->with('success', 'Note de frais enregistrée.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $expenseSheet = ExpenseSheet::findOrFail($id);
        if (!auth()->user()->can('view', $expenseSheet)) {
            abort(403);
        }
        //        return $expenseSheet->load(['costs.formCost', 'costs.steps', 'user', 'department', 'costs.formCost.reimbursementRates']);
        $canApprove = auth()->user()->can('approve', $expenseSheet);
        $canReject = auth()->user()->can('reject', $expenseSheet);
        $canEdit = auth()->user()->can('edit', $expenseSheet);
        return Inertia::render('expenseSheet/Show', [
            'expenseSheet' => $expenseSheet->load(['costs.formCost', 'user', 'department', 'costs.formCost.reimbursementRates', 'validatedBy']),
            'canApprove' => $canApprove,
            'canReject' => $canReject,
            'canEdit' => $canEdit,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $expenseSheet = ExpenseSheet::findOrFail($id);
        if (!auth()->user()->can('edit', $expenseSheet)) {
            abort(403);
        }
        // Charger tous les coûts disponibles avec leurs taux et prérequis
        $formCosts = FormCost::with(['reimbursementRates', 'requirements'])->get();

        // Charger les coûts liés à cette note de frais avec leurs relations
        $expenseSheet->load(['costs.formCost.reimbursementRates', 'costs.formCost.requirements', 'department']);

        // Préparer les données pour la vue
        $expenseSheetData = [
            'id' => $expenseSheet->id,
            'department_id' => $expenseSheet->department_id,
            'costs' => $expenseSheet->costs->map(function ($cost) {
                $requirementsData = json_decode($cost->requirements, true) ?? [];

                return [
                    'id' => $cost->form_cost_id,
                    'cost_id' => $cost->form_cost_id,
                    'name' => $cost->formCost->name,
                    'description' => $cost->formCost->description,
                    'type' => $cost->type,
                    'requirements' => $cost->formCost->requirements,
                    'reimbursement_rates' => $cost->formCost->reimbursementRates,
                    'data' => [
                        'route' => $cost->route,
                        'paidAmount' => $cost->amount,
                        'steps' => $cost->route['steps'] ?? [],
                        'departure' => $cost->route['departure'] ?? '',
                        'arrival' => $cost->route['arrival'] ?? '',
                        'manualKm' => $cost->route['manual_km'] ?? 0,
                        'justification' => $cost->route['justification'] ?? '',
                    ],
                    'date' => $cost->date,
                    'total' => $cost->total,
                    'requirements_data' => $requirementsData,
                ];
            })->toArray(),
        ];


        return Inertia::render('expenseSheet/Edit', [
            'form' => [
                'costs' => $formCosts->map(function ($cost) {
                    return [
                        'id' => $cost->id,
                        'name' => $cost->name,
                        'description' => $cost->description,
                        'type' => $cost->type,
                        'requirements' => $cost->requirements,
                        'reimbursement_rates' => $cost->reimbursementRates,
                    ];
                })->toArray(),
            ],
            'expenseSheet' => $expenseSheetData,
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $expenseSheet = ExpenseSheet::findOrFail($id);
        if (!auth()->user()->can('edit', $expenseSheet)) {
            abort(403);
        }
        $validated = $request->validate([
            'costs' => 'required|array|max:7',
            'costs.*.cost_id' => 'required|exists:form_costs,id',
            'costs.*.data' => 'required|array',
            'costs.*.date' => 'required|date',
            'costs.*.requirements' => 'nullable|array',
        ]);

        try {
            // Supprimer tous les coûts existants
            $expenseSheet->costs()->delete();

            // Mettre à jour les informations de la note de frais
            $expenseSheet->update([
                'approved' => null,
                'status' => 'En attente',
            ]);

            $globalTotal = 0;

            foreach ($validated['costs'] as $cost) {
                $formCost = FormCost::findOrFail($cost['cost_id']);
                $type = $formCost->type;
                $date = $cost['date'];
                $data = $cost['data'];
                $requirements = $cost['requirements'] ?? [];

                // Traitement des fichiers dans les requirements
                foreach ($requirements as $key => $requirement) {
                    if (isset($requirement['file']) && $requirement['file'] instanceof \Illuminate\Http\UploadedFile) {
                        $file = $requirement['file'];
                        $path = Storage::url(Storage::putFile($file));
                        $requirements[$key] = ['file' => $path];
                    }
                }

                $distance = null;
                $googleDistance = null;
                $route = null;
                $total = 0;
                $steps = [];

                if ($type === 'km') {
                    $distance = $data['manualKm'] ?? 0;
                    $googleDistance = $data['googleDistance'] ?? null;
                    $route = [
                        'departure' => $data['departure'] ?? '',
                        'arrival' => $data['arrival'] ?? '',
                        'steps' => $data['steps'] ?? [],
                        'manual_km' => $distance,
                        'justification' => $data['justification'] ?? '',
                    ];
                    $total = $distance * $formCost->getActiveRate($date);
                } elseif ($type === 'percentage') {
                    $total = $data['paidAmount'] * ($data['percentage'] / 100);
                } elseif ($type === 'fixed') {
                    $total = $data['amount'];
                }

                $createdCost = $expenseSheet->costs()->create([
                    'form_cost_id' => $formCost->id,
                    'type' => $type,
                    'distance' => $distance,
                    'google_distance' => $googleDistance,
                    'route' => $route,
                    'total' => $total,
                    'date' => $date,
                    'amount' => $data['paidAmount'] ?? null,
                    'requirements' => json_encode($requirements),
                    'expense_sheet_id' => $expenseSheet->id
                ]);

                // Ajout des étapes pour le type "km"
                if ($type === 'km') {
                    foreach ($steps as $index => $address) {
                        $createdCost->steps()->create([
                            'address' => $address,
                            'order' => $index + 1,
                        ]);
                    }
                }

                $globalTotal += $total;
            }

            // Mettre à jour le total global
            $expenseSheet->update(['total' => $globalTotal]);

            return redirect()->route('dashboard')->with('success', 'Note de frais mise à jour avec succès.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ExpenseSheetController $expenseSheetController)
    {
        //
    }

    /**
     * Approve or reject the specified resource.
     */
    public function approve(Request $request, $id)
    {
        $expenseSheet = ExpenseSheet::findOrFail($id);

        $validated = $request->validate([
            'approval' => 'required|boolean',
            'reason' => 'required_if:approval,0',
        ]);
        if (!auth()->user()->can('approve', $expenseSheet) && $validated['approval'] === true) {
            abort(403);
        } elseif (!auth()->user()->can('reject', $expenseSheet) && $validated['approval'] === false) {
            abort(403);
        }

        $expenseSheet->approved = $validated['approval'];
        $expenseSheet->refusal_reason = $validated['reason'] ?? null;
        $expenseSheet->validated_by = auth()->id();
        $expenseSheet->validated_at = now();
        $expenseSheet->validated_by = auth()->id();
        $expenseSheet->save();

        if ($validated['approval']) {
            $expenseSheet->user->notify(new ApprovalExpenseSheet($expenseSheet));
        } else {
            $expenseSheet->user->notify(new \App\Notifications\RejectionExpenseSheet($expenseSheet));
        }

        return redirect()->route('dashboard')->with('success', 'Note de frais mise à jour.');
    }

    /**
     * Show expense export form.
     */
    public function exportForm()
    {
        if (!auth()->user()->can('export', ExpenseSheet::class)) {
            abort(403);
        }

        return Inertia::render('expenseSheet/Export');
    }

    /**
     * Export the expense sheets total to a CSV file.
     */

    public function export(Request $request)
    {
        if (!auth()->user()->can('export', ExpenseSheet::class)) {
            abort(403);
        }

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $startDate = $validated['start_date'];
        $endDate = $validated['end_date'];

        // Récupérer tous les utilisateurs ayant au moins un ExpenseSheetCost entre ces dates
        $users = \App\Models\User::whereHas('expenseSheets.expenseSheetCosts', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        })->with(['expenseSheets.expenseSheetCosts.formCost.form'])->get();

        // Création du tableau de données
        $data = [];

//        return 1;
        // Préparer la première ligne : Username + liste des coûts (types de coût + formulaire)
        $headers = ['Username'];
        $costTypes = []; // pour stocker tous les types de coût rencontrés

        foreach ($users as $user) {
            foreach ($user->expenseSheets as $expenseSheet) {
                foreach ($expenseSheet->expenseSheetCosts as $cost) {
                    if ($cost->date >= $startDate && $cost->date <= $endDate) {
                        $key = $cost->formCost->name . ' (' . $cost->formCost->form->name . ')';
                        $costTypes[$key] = $cost->formCost->type; // type: KM, FIXE, POURCENT
                    }
                }
            }
        }

        $headers = array_merge($headers, array_keys($costTypes));

        // Remplir les données utilisateur
        foreach ($users as $user) {
            $row = [];
            $row[] = $user->name;

            // Initialiser toutes les colonnes à zéro
            $costSums = array_fill_keys(array_keys($costTypes), 0);

            foreach ($user->expenseSheets as $expenseSheet) {
                foreach ($expenseSheet->expenseSheetCosts as $cost) {
                    if ($cost->date >= $startDate && $cost->date <= $endDate) {
                        $key = $cost->formCost->name . ' (' . $cost->formCost->form->name . ')';

                        if (isset($costTypes[$key])) {
                            if ($cost->formCost->type === 'km') {
                                $costSums[$key] += ($cost->route['google_km'] + $cost->route['manual_km']);
                            } else {
                                $costSums[$key] += $cost->amount;
                            }
                        }
                    }
                }
            }

            // Ajouter les valeurs à la ligne
            foreach (array_keys($costTypes) as $key) {
                $row[] = $costSums[$key];
            }

            $data[] = $row;
        }

        // Création du fichier Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Ecriture de l'entête
        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValueByColumnAndRow($col, 1, $header);
            $col++;
        }

        // Ecriture des données
        $rowNumber = 2;
        foreach ($data as $rowData) {
            $col = 1;
            foreach ($rowData as $cell) {
                $sheet->setCellValueByColumnAndRow($col, $rowNumber, $cell);
                $col++;
            }
            $rowNumber++;
        }

// Téléchargement du fichier
        $fileName = 'export_expense_sheets_' . now()->format('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($temp_file);

        return response()->download($temp_file, $fileName)->deleteFileAfterSend(true);
    }
}
