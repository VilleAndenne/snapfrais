<?php

namespace App\Http\Controllers;

use App\Models\ExpenseSheet;
use App\Models\Form;
use App\Models\FormCost;
use App\Notifications\ApprovalExpenseSheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

// en haut du fichier

class ExpenseSheetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ExpenseSheet::with(['form', 'costs', 'department', 'user'])
            ->visibleBy(auth()->user())
            ->orderBy('created_at', 'desc');

        // Filtre de recherche
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('form', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Filtre par statut
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $status = $request->input('status');
            if ($status === 'draft') {
                $query->where('is_draft', true);
            } elseif ($status === 'approved') {
                $query->where('is_draft', false)->where('approved', true);
            } elseif ($status === 'rejected') {
                $query->where('is_draft', false)->where('approved', false);
            } elseif ($status === 'pending') {
                $query->where('is_draft', false)->whereNull('approved');
            }
        }

        // Filtre par département
        if ($request->filled('department') && $request->input('department') !== 'all') {
            $query->whereHas('department', function ($q) use ($request) {
                $q->where('name', $request->input('department'));
            });
        }

        // Filtre par date de début
        if ($request->filled('dateStart')) {
            $query->whereDate('created_at', '>=', $request->input('dateStart'));
        }

        // Filtre par date de fin
        if ($request->filled('dateEnd')) {
            $query->whereDate('created_at', '<=', $request->input('dateEnd'));
        }

        $expenseSheets = $query->paginate(5)->withQueryString();

        // Récupérer tous les départements uniques pour le filtre
        $departments = \App\Models\Department::orderBy('name')->pluck('name')->unique()->values();

        return Inertia::render('expenseSheet/Index', [
            'expenseSheets' => $expenseSheets,
            'canExport' => auth()->user()->can('export', ExpenseSheet::class),
            'filters' => $request->only(['search', 'status', 'department', 'dateStart', 'dateEnd']),
            'departments' => $departments,
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
            'departments' => auth()->user()->departments()->with('users', 'heads')->get(),
            'authUser' => auth()->user()->only(['id', 'name', 'email']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $id)
    {
        try {
            \Log::info('ExpenseSheet store called', [
                'formId' => $id,
                'user_id' => auth()->id(),
                'has_costs' => $request->has('costs'),
                'is_draft' => $request->input('is_draft'),
                'all_keys' => array_keys($request->all()),
            ]);

            try {
                $validated = $request->validate([
                    'costs' => 'required|array|max:30',
                    'costs.*.cost_id' => 'required|exists:form_costs,id',
                    'costs.*.data' => 'required|array',
                    'costs.*.date' => 'required|date',
                    'costs.*.requirements' => 'nullable|array',
                    'department_id' => 'required|exists:departments,id',
                    'target_user_id' => 'nullable|exists:users,id',
                    'is_draft' => 'required|boolean',
                ]);
                \Log::info('Validation passed');
            } catch (\Illuminate\Validation\ValidationException $e) {
                \Log::error('Validation failed', [
                    'errors' => $e->errors(),
                    'request_data' => $request->except(['_token']),
                ]);
                throw $e;
            }

            // Convertir is_draft en booléen de manière fiable
            $isDraft = in_array($request->input('is_draft'), [1, '1', 'true', true], true);

            // Département + relations nécessaires (heads + users)
            $department = \App\Models\Department::with(['heads:id', 'users:id'])->findOrFail($validated['department_id']);
            $currentUserId = auth()->id();
            $targetUserId = $request->input('target_user_id');

            // Si on encode pour quelqu'un d'autre : il faut être head du service + la cible doit appartenir au service
            if ($targetUserId && (int) $targetUserId !== (int) $currentUserId) {
                $isHead = $department->heads->contains('id', $currentUserId);
                if (! $isHead) {
                    abort(403, "Vous devez être responsable du service pour encoder au nom d'un agent.");
                }
                $belongsToDept = $department->users->contains('id', (int) $targetUserId);
                if (! $belongsToDept) {
                    return back()
                        ->withErrors(['target_user_id' => "L'agent sélectionné n'appartient pas à ce service."])
                        ->withInput();
                }
            }

            // Création de la note de frais
            $expenseSheet = \App\Models\ExpenseSheet::create([
                'user_id' => $targetUserId ?: $currentUserId, // bénéficiaire
                'created_by' => $currentUserId,                  // créateur réel
                'status' => $isDraft ? 'Brouillon' : 'En attente',
                'total' => 0,
                'form_id' => $id,
                'department_id' => $validated['department_id'],
                'is_draft' => $isDraft,
            ]);

            $globalTotal = 0;

            foreach ($validated['costs'] as $costItem) {
                $formCost = \App\Models\FormCost::find($costItem['cost_id']);
                $type = $formCost->type;
                $date = $costItem['date'];

                // Récupération du ou des taux actifs
                $rates = $formCost->reimbursementRates()
                    ->where('start_date', '<=', $date)
                    ->where(function ($q) use ($date) {
                        $q->whereNull('end_date')->orWhere('end_date', '>=', $date);
                    })
                    ->orderByDesc('start_date')
                    ->get();

                if ($rates->count() === 0) {
                    continue;
                }

                if ($rates->count() > 1) {
                    // ⚠️ Sécurité : plusieurs taux actifs = erreur de config
                    $expenseSheet->delete();

                    return back()
                        ->withInput()
                        ->with('error', "Configuration invalide : plusieurs taux actifs le $date pour le coût \"{$formCost->name}\". Veuillez corriger.");
                }

                $rate = $rates->first();
                $transport = $rate->transport ?? 'car'; // transport stocké dans la table reimbursement_rates

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

                    if (! $origin || ! $destination) {
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
                            // Utilisation du mode issu du taux
                            'mode' => $transport === 'bike' ? 'bicycling' : 'driving',
                            'key' => env('GOOGLE_MAPS_API_KEY'),
                        ];

                        $response = \Illuminate\Support\Facades\Http::get('https://maps.googleapis.com/maps/api/directions/json', $params);
                        $json = $response->json();

                        if ($response->successful() && $json['status'] === 'OK' && isset($json['routes'][0]['legs'][0]['distance']['value'])) {
                            $googleKm += $json['routes'][0]['legs'][0]['distance']['value'];
                        }
                    }

                    $googleKm = round($googleKm / 1000, 2);
                    $googleDistance = $googleKm;
                    // Arrondir la distance totale à l'entier le plus proche avant le calcul
                    $distance = round($googleKm + $manualKm);
                    $total = round($distance * $rate->value, 2);

                    $route = [
                        'departure' => $origin,
                        'arrival' => $destination,
                        'google_km' => $googleKm,
                        'manual_km' => $manualKm,
                        'justification' => $data['justification'] ?? null,
                        'transport' => $transport, // on garde une trace
                    ];

                    // Enregistrer aussi les étapes si besoin
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

                // Gestion des requirements
                $requirements = [];
                if (isset($costItem['requirements'])) {
                    foreach ($costItem['requirements'] as $key => $requirement) {
                        // Déterminer le nom du requirement
                        // Si $key est numérique, c'est un ID, sinon c'est déjà le nom
                        if (is_numeric($key)) {
                            $requirementModel = \App\Models\FormCostRequirement::find($key);
                            $requirementName = $requirementModel ? $requirementModel->name : "Requirement $key";
                        } else {
                            // $key est déjà le nom du requirement
                            $requirementName = $key;
                        }

                        if (is_array($requirement) && isset($requirement['file']) && $requirement['file'] instanceof \Illuminate\Http\UploadedFile) {
                            $path = \Illuminate\Support\Facades\Storage::url(\Illuminate\Support\Facades\Storage::putFile($requirement['file']));
                            $requirements[$key] = ['file' => $path];
                        } elseif (is_array($requirement) && isset($requirement['value'])) {
                            $requirements[$requirementName] = ['value' => $requirement['value']];
                        }
                    }
                }

                $expenseSheet->costs()->create([
                    'form_cost_id' => $formCost->id,
                    'type' => $type,
                    'distance' => $distance,
                    'google_distance' => $googleDistance,
                    'route' => $route,
                    'total' => $total,
                    'date' => $date,
                    'amount' => $data['paidAmount'] ?? null,
                    'requirements' => json_encode($requirements),
                    'expense_sheet_id' => $expenseSheet->id,
                ]);

                $globalTotal += $total;
            }

            $expenseSheet->update(['total' => $globalTotal]);

            if ($globalTotal <= 0) {
                $expenseSheet->delete();
                \Log::error('Note de frais à 0€ détectée et supprimée (web)', [
                    'expense_sheet_id' => $expenseSheet->id,
                    'user_id' => auth()->id(),
                ]);

                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'Le total de la note de frais ne peut pas être nul ou négatif. Cela peut arriver si aucun taux de remboursement n\'est configuré pour les dates sélectionnées. Veuillez vérifier les coûts saisis et leurs dates.');
            }

            // Ne pas envoyer de notifications pour les brouillons
            if (! $isDraft) {
                $user = auth()->user();
                $department = $expenseSheet->department;
                $heads = $department->heads;

                if ($heads->contains($user) && $department->parent) {
                    $heads = $department->parent->heads;
                }

                $heads->each(function ($head) use ($expenseSheet) {
                    $head->notify(new \App\Notifications\ExpenseSheetToApproval($expenseSheet));
                });

                if (auth()->user()->id !== $expenseSheet->user->id) {
                    $expenseSheet->creator->notify(new \App\Notifications\ReceiptExpenseSheetForUser($expenseSheet));
                }

                $expenseSheet->user->notify(new \App\Notifications\ReceiptExpenseSheet($expenseSheet));
            }

            $message = $isDraft ? 'Brouillon enregistré.' : 'Note de frais enregistrée.';

            // API request: return JSON
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'expense_sheet' => $expenseSheet->load(['costs', 'department', 'user']),
                ], 201);
            }

            // Web request: redirect
            return redirect()->route('expense-sheet.show', $expenseSheet->id)->with('success', $message);

        } catch (\Exception $e) {
            \Log::error('ExpenseSheet store error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // API request: return JSON error
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }

            throw $e;
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $expenseSheet = ExpenseSheet::findOrFail($id);
        if (! auth()->user()->can('view', $expenseSheet)) {
            abort(403);
        }
        //        return $expenseSheet->load(['costs.formCost', 'costs.steps', 'user', 'department', 'costs.formCost.reimbursementRates']);
        $canApprove = auth()->user()->can('approve', $expenseSheet);
        $canReject = auth()->user()->can('reject', $expenseSheet);
        $canEdit = auth()->user()->can('edit', $expenseSheet);
        $canDestroy = auth()->user()->can('destroy', $expenseSheet);
        $canReturnBySRH = auth()->user()->can('returnBySRH', $expenseSheet);

        return Inertia::render('expenseSheet/Show', [
            'expenseSheet' => $expenseSheet->load(['costs.formCost', 'user', 'department', 'costs.formCost.reimbursementRates', 'validatedBy', 'creator']),
            'canApprove' => $canApprove,
            'canReject' => $canReject,
            'canEdit' => $canEdit,
            'canDestroy' => $canDestroy,
            'canReturnBySRH' => $canReturnBySRH,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $expenseSheet = ExpenseSheet::findOrFail($id);
        if (! auth()->user()->can('edit', $expenseSheet)) {
            abort(403);
        }
        // Charger tous les coûts disponibles avec leurs taux et prérequis
        $formCosts = FormCost::with(['reimbursementRates', 'requirements'])->where('form_id', $expenseSheet->form_id)->get();

        // Charger les coûts liés à cette note de frais avec leurs relations
        $expenseSheet->load(['costs.formCost.reimbursementRates', 'costs.formCost.requirements', 'department']);

        // Préparer les données pour la vue
        $expenseSheetData = [
            'id' => $expenseSheet->id,
            'department_id' => $expenseSheet->department_id,
            'user_id' => $expenseSheet->user_id,
            'costs' => $expenseSheet->costs->map(function ($cost) {
                $requirementsData = json_decode($cost->requirements, true) ?? [];

                // Extraire les données de route si elles existent
                $route = $cost->route ?? [];
                $steps = [];
                if (isset($route['steps']) && is_array($route['steps'])) {
                    $steps = array_map(function ($step) {
                        return is_array($step) ? ($step['address'] ?? '') : $step;
                    }, $route['steps']);
                }

                return [
                    'id' => $cost->form_cost_id,
                    'cost_id' => $cost->form_cost_id,
                    'name' => $cost->formCost->name,
                    'description' => $cost->formCost->description,
                    'type' => $cost->type,
                    'requirements' => $cost->formCost->requirements,
                    'reimbursement_rates' => $cost->formCost->reimbursementRates,
                    'data' => [
                        'paidAmount' => $cost->amount,
                        'departure' => $route['departure'] ?? '',
                        'arrival' => $route['arrival'] ?? '',
                        'steps' => $steps,
                        'manualKm' => $route['manual_km'] ?? 0,
                        'justification' => $route['justification'] ?? '',
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
            'departments' => auth()->user()->departments()->with('users', 'heads')->get(),
            'authUser' => auth()->user()->only(['id', 'name', 'email']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $expenseSheet = ExpenseSheet::findOrFail($id);
        if (! auth()->user()->can('edit', $expenseSheet)) {
            abort(403);
        }
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'target_user_id' => 'nullable|exists:users,id',
            'costs' => 'required|array|max:30',
            'costs.*.cost_id' => 'required|exists:form_costs,id',
            'costs.*.data' => 'required|array',
            'costs.*.date' => 'required|date',
            'costs.*.requirements' => 'nullable|array',
        ]);

        try {
            // Supprimer tous les coûts existants
            $expenseSheet->costs()->delete();

            // Réinitialiser l'approbation et remettre en attente pour resoumission
            $expenseSheet->update([
                'approved' => null,
                'status' => 'En attente',
                'refusal_reason' => null,
                'validated_by' => null,
                'validated_at' => null,
                'department_id' => $validated['department_id'],
                'user_id' => $validated['target_user_id'] ?? $expenseSheet->user_id,
                'created_by' => auth()->user()->id,
            ]);
            // Si c'est un brouillon, on garde le statut brouillon
            // Sinon on réinitialise l'approbation et on remet en attente pour resoumission
            if ($expenseSheet->is_draft) {
                $expenseSheet->update([
                    'approved' => null,
                    'status' => 'Brouillon',
                    'refusal_reason' => null,
                    'validated_by' => null,
                    'validated_at' => null,
                ]);
            } else {
                $expenseSheet->update([
                    'approved' => null,
                    'status' => 'En attente',
                    'refusal_reason' => null,
                    'validated_by' => null,
                    'validated_at' => null,
                    'is_draft' => false,
                ]);
            }

            $globalTotal = 0;

            foreach ($validated['costs'] as $costItem) {
                $formCost = FormCost::find($costItem['cost_id']);
                $type = $formCost->type;
                $date = $costItem['date'];

                // Récupération du ou des taux actifs
                $rates = $formCost->reimbursementRates()
                    ->where('start_date', '<=', $date)
                    ->where(function ($q) use ($date) {
                        $q->whereNull('end_date')->orWhere('end_date', '>=', $date);
                    })
                    ->orderByDesc('start_date')
                    ->get();

                if ($rates->count() === 0) {
                    continue;
                }

                if ($rates->count() > 1) {
                    $expenseSheet->delete();

                    return back()
                        ->withInput()
                        ->with('error', "Configuration invalide : plusieurs taux actifs le $date pour le coût \"{$formCost->name}\". Veuillez corriger.");
                }

                $rate = $rates->first();
                $transport = $rate->transport ?? 'car';

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

                    if (! $origin || ! $destination) {
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
                            'mode' => $transport === 'bike' ? 'bicycling' : 'driving',
                            'key' => env('GOOGLE_MAPS_API_KEY'),
                        ];

                        $response = \Illuminate\Support\Facades\Http::get('https://maps.googleapis.com/maps/api/directions/json', $params);
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
                        'transport' => $transport,
                    ];

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

                // Gestion des requirements
                $requirements = [];
                if (isset($costItem['requirements'])) {
                    foreach ($costItem['requirements'] as $key => $requirement) {
                        if (is_array($requirement) && isset($requirement['file']) && $requirement['file'] instanceof \Illuminate\Http\UploadedFile) {
                            // Nouveau fichier uploadé
                            $path = \Illuminate\Support\Facades\Storage::url(\Illuminate\Support\Facades\Storage::putFile($requirement['file']));
                            $requirements[$key] = ['file' => $path];
                        } elseif (is_array($requirement) && isset($requirement['existing_file'])) {
                            // Fichier existant conservé
                            $requirements[$key] = ['file' => $requirement['existing_file']];
                        } elseif (is_array($requirement) && isset($requirement['value'])) {
                            // Valeur texte
                            $requirements[$key] = ['value' => $requirement['value']];
                        }
                    }
                }

                $expenseSheet->costs()->create([
                    'form_cost_id' => $formCost->id,
                    'type' => $type,
                    'distance' => $distance,
                    'google_distance' => $googleDistance,
                    'route' => $route,
                    'total' => $total,
                    'date' => $date,
                    'amount' => $data['paidAmount'] ?? null,
                    'requirements' => json_encode($requirements),
                    'expense_sheet_id' => $expenseSheet->id,
                ]);

                $globalTotal += $total;
            }

            // Mettre à jour le total global
            $expenseSheet->update(['total' => $globalTotal]);

            if ($globalTotal <= 0) {
                $expenseSheet->delete();
                \Log::error('Note de frais à 0€ détectée et supprimée (web update)', [
                    'expense_sheet_id' => $expenseSheet->id,
                    'user_id' => auth()->id(),
                ]);

                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'Le total de la note de frais ne peut pas être nul ou négatif. Cela peut arriver si aucun taux de remboursement n\'est configuré pour les dates sélectionnées. Veuillez vérifier les coûts saisis et leurs dates.');
            }

            // Notifier les responsables de la resoumission (sauf pour les brouillons)
            if (! $expenseSheet->is_draft) {
                $department = $expenseSheet->department;
                $heads = $department->heads;

                if ($heads->contains(auth()->user()) && $department->parent) {
                    $heads = $department->parent->heads;
                }

                $heads->each(function ($head) use ($expenseSheet) {
                    $head->notify(new \App\Notifications\ExpenseSheetToApproval($expenseSheet));
                });
            }

            $message = $expenseSheet->is_draft
                ? 'Brouillon modifié avec succès.'
                : 'Note de frais modifiée et resoumise avec succès.';

            return redirect()->route('expense-sheet.show', $expenseSheet->id)->with('success', $message);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $expenseSheet = ExpenseSheet::findOrFail($id);

        $expenseSheet->delete();

        return redirect()->route('dashboard')->with('success', 'Note de frais supprimée.');
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
        if (! auth()->user()->can('approve', $expenseSheet) && $validated['approval'] === true) {
            abort(403);
        } elseif (! auth()->user()->can('reject', $expenseSheet) && $validated['approval'] === false) {
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

        return redirect()->route('expense-sheet.show', $expenseSheet->id)->with('success', 'Note de frais mise à jour.');
    }

    public function submitDraft($id)
    {
        $expenseSheet = ExpenseSheet::findOrFail($id);

        // Vérifier que c'est bien un brouillon
        if (! $expenseSheet->is_draft) {
            return back()->withErrors(['error' => 'Cette note de frais n\'est pas un brouillon.']);
        }

        // Vérifier les permissions
        if (! auth()->user()->can('edit', $expenseSheet)) {
            abort(403);
        }

        // Passer en statut "En attente" et retirer le flag brouillon
        $expenseSheet->update([
            'is_draft' => false,
            'status' => 'En attente',
        ]);

        // Envoyer les notifications
        $department = $expenseSheet->department;
        $heads = $department->heads;

        if ($heads->contains(auth()->user()) && $department->parent) {
            $heads = $department->parent->heads;
        }

        $heads->each(function ($head) use ($expenseSheet) {
            $head->notify(new \App\Notifications\ExpenseSheetToApproval($expenseSheet));
        });

        if (auth()->user()->id !== $expenseSheet->user->id) {
            $expenseSheet->creator->notify(new \App\Notifications\ReceiptExpenseSheetForUser($expenseSheet));
        }

        $expenseSheet->user->notify(new \App\Notifications\ReceiptExpenseSheet($expenseSheet));

        return redirect()->route('expense-sheet.show', $expenseSheet->id)->with('success', 'Note de frais soumise avec succès.');
    }

    public function generatePDF($id)
    {
        $expenseSheet = ExpenseSheet::with([
            'costs.formCost.reimbursementRates',
            'user', 'department', 'validatedBy', 'form',
        ])->findOrFail($id);

        if (! auth()->user()->can('view', $expenseSheet)) {
            abort(403);
        }

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('expenseSheet.pdf', [
            'expenseSheet' => $expenseSheet,
        ])->setPaper('a4', 'landscape')
            ->stream('note_de_frais_'.$id.'.pdf'); // inline
    }

    /**
     * Return expense sheet by SRH (admin) after final validation.
     */
    public function returnBySRH(Request $request, $id)
    {
        $expenseSheet = ExpenseSheet::with(['department.heads', 'user'])->findOrFail($id);

        if (! auth()->user()->can('returnBySRH', $expenseSheet)) {
            abort(403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|min:3',
        ]);

        // Remettre la note en statut "à corriger"
        $expenseSheet->update([
            'approved' => false,
            'status' => 'Renvoyé par le SRH',
            'refusal_reason' => $validated['reason'],
            'validated_by' => auth()->id(),
            'validated_at' => now(),
        ]);

        // Notifier l'agent (bénéficiaire de la note)
        $expenseSheet->user->notify(new \App\Notifications\SRHReturnExpenseSheet($expenseSheet));

        // Notifier le(s) responsable(s) du département
        $department = $expenseSheet->department;
        $heads = $department->heads;

        $heads->each(function ($head) use ($expenseSheet) {
            $head->notify(new \App\Notifications\SRHReturnExpenseSheet($expenseSheet));
        });

        return redirect()->route('expense-sheet.show', $expenseSheet->id)
            ->with('success', 'La note de frais a été renvoyée. L\'agent et son responsable ont été notifiés.');
    }

    public function duplicate($id)
    {
        $originalExpenseSheet = ExpenseSheet::with(['costs', 'department', 'user'])->findOrFail($id);

        // Vérifier les permissions
        if (! auth()->user()->can('view', $originalExpenseSheet)) {
            abort(403);
        }

        // Créer une nouvelle note de frais en brouillon
        $newExpenseSheet = ExpenseSheet::create([
            'user_id' => $originalExpenseSheet->user_id,
            'created_by' => auth()->id(),
            'status' => 'Brouillon',
            'total' => 0,
            'form_id' => $originalExpenseSheet->form_id,
            'department_id' => $originalExpenseSheet->department_id,
            'is_draft' => true,
        ]);

        $globalTotal = 0;

        // Dupliquer tous les coûts
        foreach ($originalExpenseSheet->costs as $originalCost) {
            $newExpenseSheet->costs()->create([
                'form_cost_id' => $originalCost->form_cost_id,
                'type' => $originalCost->type,
                'distance' => $originalCost->distance,
                'google_distance' => $originalCost->google_distance,
                'route' => $originalCost->route,
                'total' => $originalCost->total,
                'date' => $originalCost->date,
                'amount' => $originalCost->amount,
                'requirements' => null, // Ne pas dupliquer les requirements (fichiers/valeurs)
                'expense_sheet_id' => $newExpenseSheet->id,
            ]);

            $globalTotal += $originalCost->total;
        }

        // Mettre à jour le total
        $newExpenseSheet->update(['total' => $globalTotal]);

        return redirect()->route('expense-sheet.show', $newExpenseSheet->id)
            ->with('success', 'Note de frais dupliquée avec succès.');
    }
}
