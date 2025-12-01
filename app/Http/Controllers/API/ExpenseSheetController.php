<?php

namespace App\Http\Controllers\API;

use App\Models\ExpenseSheet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExpenseSheetController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        // Retourne uniquement les notes de frais de l'utilisateur connecté
        $expenseSheets = ExpenseSheet::with('form', 'costs', 'department.heads', 'user')
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->handleResponse($expenseSheets);
    }

    /**
     * Display all resource which user have access to.
     */
    public function all(): JsonResponse
    {
        $perPage = request()->input('per_page', 10);

        $expenseSheets = ExpenseSheet::with('form', 'costs', 'department.heads', 'user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(fn ($expenseSheet) => auth()->user()->can('view', $expenseSheet))
            ->values();

        // Paginer manuellement les résultats filtrés
        $page = request()->input('page', 1);
        $total = $expenseSheets->count();
        $items = $expenseSheets->forPage($page, $perPage)->values();

        return $this->handleResponse([
            'data' => $items,
            'current_page' => (int) $page,
            'per_page' => (int) $perPage,
            'total' => $total,
            'last_page' => (int) ceil($total / $perPage),
            'from' => ($page - 1) * $perPage + 1,
            'to' => min($page * $perPage, $total),
        ]);
    }

    /**
     * Display a listing of the resource to validate by the user.
     */
    public function validateIndex(): JsonResponse
    {
        $expenseSheets = ExpenseSheet::with('form', 'costs', 'department.heads', 'user')
            ->where('is_draft', false)
            ->whereNull('approved')
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(fn ($expenseSheet) => auth()->user()->can('shouldAppearInValidationList', $expenseSheet))
            ->values()
            ->all();

        return $this->handleResponse($expenseSheets);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $formId)
    {
        try {
            \Log::info('API ExpenseSheet store called', [
                'formId' => $formId,
                'user_id' => auth()->id(),
                'has_costs' => $request->has('costs'),
                'is_draft' => $request->input('is_draft'),
            ]);

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

            $isDraft = in_array($request->input('is_draft'), [1, '1', 'true', true], true);

            // Département + relations nécessaires (heads + users)
            $department = \App\Models\Department::with(['heads:id', 'users:id'])->findOrFail($validated['department_id']);
            $currentUserId = auth()->id();
            $targetUserId = $request->input('target_user_id');

            // Si on encode pour quelqu'un d'autre : il faut être head du service + la cible doit appartenir au service
            if ($targetUserId && (int) $targetUserId !== (int) $currentUserId) {
                $isHead = $department->heads->contains('id', $currentUserId);
                if (! $isHead) {
                    return $this->handleError("Vous devez être responsable du service pour encoder au nom d'un agent.", Response::HTTP_FORBIDDEN);
                }
                $belongsToDept = $department->users->contains('id', (int) $targetUserId);
                if (! $belongsToDept) {
                    return $this->handleError("L'agent sélectionné n'appartient pas à ce service.", Response::HTTP_UNPROCESSABLE_ENTITY);
                }
            }

            // Création de la note de frais
            $expenseSheet = \App\Models\ExpenseSheet::create([
                'user_id' => $targetUserId ?: $currentUserId,
                'created_by' => $currentUserId,
                'status' => $isDraft ? 'Brouillon' : 'En attente',
                'total' => 0,
                'form_id' => $formId,
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
                    \Log::warning("Aucun taux trouvé pour le coût {$formCost->name} à la date {$date}");

                    continue;
                }

                if ($rates->count() > 1) {
                    $expenseSheet->delete();

                    return $this->handleError("Configuration invalide : plusieurs taux actifs le $date pour le coût \"{$formCost->name}\". Veuillez corriger.", Response::HTTP_UNPROCESSABLE_ENTITY);
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
                    $distance = round($googleKm + $manualKm);
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
                        // Récupérer le nom du requirement depuis la base de données
                        $requirementModel = \App\Models\FormCostRequirement::find($key);
                        $requirementName = $requirementModel ? $requirementModel->name : "Requirement $key";

                        if (is_array($requirement) && isset($requirement['file']) && $requirement['file'] instanceof \Illuminate\Http\UploadedFile) {
                            $path = \Illuminate\Support\Facades\Storage::url(\Illuminate\Support\Facades\Storage::putFile($requirement['file']));
                            $requirements[$requirementName] = ['file' => $path];
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
                \Log::error('Note de frais à 0€ détectée et supprimée', [
                    'expense_sheet_id' => $expenseSheet->id,
                    'user_id' => $currentUserId,
                    'costs_count' => count($validated['costs']),
                ]);

                return $this->handleError('Le total de la note de frais ne peut pas être nul ou négatif. Cela peut arriver si aucun taux de remboursement n\'est configuré pour les dates sélectionnées. Veuillez vérifier les coûts saisis et leurs dates.', Response::HTTP_UNPROCESSABLE_ENTITY);
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

            return $this->handleResponse(
                $expenseSheet->load(['costs', 'department', 'user']),
                $message,
                Response::HTTP_CREATED
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('API ExpenseSheet validation failed', ['errors' => $e->errors()]);

            return $this->handleError('Erreur de validation', Response::HTTP_UNPROCESSABLE_ENTITY, $e->errors());
        } catch (\Exception $e) {
            \Log::error('API ExpenseSheet store error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->handleError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $expenseSheet = ExpenseSheet::findOrFail($id);
        if (! auth()->user()->can('view', $expenseSheet)) {
            abort(403);
        }
        //        return $expenseSheet->load(['costs.formCost', 'costs.steps', 'user', 'department', 'costs.formCost.reimbursementRates']);
        $canApprove = auth()->user()->can('approve', $expenseSheet);
        $canReject = auth()->user()->can('reject', $expenseSheet);
        $canEdit = auth()->user()->can('edit', $expenseSheet);

        return $this->handleResponse([
            'expenseSheet' => $expenseSheet->load(['costs.formCost', 'user', 'department', 'costs.formCost.reimbursementRates', 'validatedBy']),
            'canApprove' => $canApprove,
            'canReject' => $canReject,
            'canEdit' => $canEdit,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $expenseSheet = ExpenseSheet::findOrFail($id);

        // Vérifier les permissions
        if (! auth()->user()->can('edit', $expenseSheet)) {
            return $this->handleError('Vous n\'avez pas les droits pour modifier cette note de frais.', Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'is_draft' => 'required|boolean',
        ]);

        $wasDraft = $expenseSheet->is_draft;
        $expenseSheet->is_draft = $validated['is_draft'];

        // Si on soumet un brouillon (passage de draft à non-draft)
        if ($wasDraft && ! $validated['is_draft']) {
            $expenseSheet->status = 'En attente';
            $expenseSheet->save();

            // Envoyer les notifications
            $user = $expenseSheet->user;
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

            return $this->handleResponse(
                $expenseSheet->load(['costs.formCost', 'user', 'department']),
                'Note de frais soumise avec succès.'
            );
        }

        $expenseSheet->save();

        return $this->handleResponse(
            $expenseSheet->load(['costs.formCost', 'user', 'department']),
            'Note de frais mise à jour.'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Full update of the expense sheet including costs.
     */
    public function updateFull(Request $request, string $id): JsonResponse
    {
        try {
            $expenseSheet = ExpenseSheet::findOrFail($id);

            // Vérifier les permissions
            if (! auth()->user()->can('edit', $expenseSheet)) {
                return $this->handleError('Vous n\'avez pas les droits pour modifier cette note de frais.', Response::HTTP_FORBIDDEN);
            }

            \Log::info('API ExpenseSheet updateFull called', [
                'id' => $id,
                'user_id' => auth()->id(),
                'has_costs' => $request->has('costs'),
                'is_draft' => $request->input('is_draft'),
            ]);

            $validated = $request->validate([
                'costs' => 'required|array|max:30',
                'costs.*.cost_id' => 'required|exists:form_costs,id',
                'costs.*.data' => 'required|array',
                'costs.*.date' => 'required|date',
                'costs.*.id' => 'nullable|exists:expense_sheet_costs,id',
                'costs.*.requirements' => 'nullable|array',
                'department_id' => 'required|exists:departments,id',
                'is_draft' => 'required|boolean',
            ]);

            $isDraft = in_array($request->input('is_draft'), [1, '1', 'true', true], true);
            $wasDraft = $expenseSheet->is_draft;

            // Vérifier le département
            $department = \App\Models\Department::with(['heads:id', 'users:id'])->findOrFail($validated['department_id']);

            DB::beginTransaction();

            // Mise à jour des informations de base
            $expenseSheet->update([
                'department_id' => $validated['department_id'],
                'is_draft' => $isDraft,
                'status' => $isDraft ? 'Brouillon' : 'En attente',
            ]);

            // Supprimer les anciens coûts
            $expenseSheet->costs()->delete();

            $globalTotal = 0;

            // Recréer tous les coûts
            foreach ($validated['costs'] as $costIndex => $costItem) {
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
                    \Log::warning("Aucun taux trouvé pour le coût {$formCost->name} à la date {$date}");

                    continue;
                }

                if ($rates->count() > 1) {
                    DB::rollBack();

                    return $this->handleError("Configuration invalide : plusieurs taux actifs le $date pour le coût \"{$formCost->name}\". Veuillez corriger.", Response::HTTP_UNPROCESSABLE_ENTITY);
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
                    $distance = round($googleKm + $manualKm);
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
                        // Récupérer le nom du requirement depuis la base de données
                        $requirementModel = \App\Models\FormCostRequirement::find($key);
                        $requirementName = $requirementModel ? $requirementModel->name : "Requirement $key";

                        if (is_array($requirement) && isset($requirement['file']) && $requirement['file'] instanceof \Illuminate\Http\UploadedFile) {
                            // Nouveau fichier uploadé
                            $path = Storage::url(Storage::putFile($requirement['file']));
                            $requirements[$requirementName] = ['file' => $path];
                        } elseif (is_array($requirement) && isset($requirement['keep_existing']) && $requirement['keep_existing']) {
                            // Garder le fichier existant - récupérer depuis l'ancien coût
                            $oldCostId = $costItem['id'] ?? null;
                            if ($oldCostId) {
                                $oldCost = \App\Models\ExpenseSheetCost::find($oldCostId);
                                if ($oldCost && $oldCost->requirements) {
                                    $oldRequirements = is_string($oldCost->requirements)
                                        ? json_decode($oldCost->requirements, true)
                                        : $oldCost->requirements;
                                    if (isset($oldRequirements[$requirementName])) {
                                        $requirements[$requirementName] = $oldRequirements[$requirementName];
                                    }
                                }
                            }
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
                DB::rollBack();
                \Log::error('Note de frais à 0€ détectée', [
                    'expense_sheet_id' => $expenseSheet->id,
                    'user_id' => auth()->id(),
                    'costs_count' => count($validated['costs']),
                ]);

                return $this->handleError('Le total de la note de frais ne peut pas être nul ou négatif. Cela peut arriver si aucun taux de remboursement n\'est configuré pour les dates sélectionnées. Veuillez vérifier les coûts saisis et leurs dates.', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            DB::commit();

            // Envoyer les notifications si passage de brouillon à soumis
            if ($wasDraft && ! $isDraft) {
                $user = $expenseSheet->user;
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

            $message = $isDraft ? 'Brouillon mis à jour.' : 'Note de frais mise à jour et soumise.';

            return $this->handleResponse(
                $expenseSheet->load(['costs.formCost', 'user', 'department']),
                $message
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            \Log::error('API ExpenseSheet updateFull validation failed', ['errors' => $e->errors()]);

            return $this->handleError('Erreur de validation', Response::HTTP_UNPROCESSABLE_ENTITY, $e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('API ExpenseSheet updateFull error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->handleError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Approve or reject the specified resource.
     */
    public function approve(Request $request, $id): JsonResponse
    {
        $expenseSheet = ExpenseSheet::findOrFail($id);

        $validated = $request->validate([
            'approval' => 'required|boolean',
            'reason' => 'required_if:approval,false',
        ]);

        if (! auth()->user()->can('approve', $expenseSheet) && $validated['approval'] === true) {
            return $this->handleError('Vous n\'avez pas les droits pour approuver cette note de frais.', Response::HTTP_FORBIDDEN);
        } elseif (! auth()->user()->can('reject', $expenseSheet) && $validated['approval'] === false) {
            return $this->handleError('Vous n\'avez pas les droits pour rejeter cette note de frais.', Response::HTTP_FORBIDDEN);
        }

        $expenseSheet->approved = $validated['approval'];
        $expenseSheet->refusal_reason = $validated['reason'] ?? null;
        $expenseSheet->validated_by = auth()->id();
        $expenseSheet->validated_at = now();
        $expenseSheet->save();

        if ($validated['approval']) {
            $expenseSheet->user->notify(new \App\Notifications\ApprovalExpenseSheet($expenseSheet));

            // Générer et envoyer le PDF pour les coûts DSF
            $dsfService = new \App\Services\DsfReimbursementService;
            if ($dsfService->hasDsfCosts($expenseSheet)) {
                $dsfService->generateAndSendReimbursementPdf($expenseSheet);
            }
        } else {
            $expenseSheet->user->notify(new \App\Notifications\RejectionExpenseSheet($expenseSheet));
        }

        $message = $validated['approval'] ? 'Note de frais approuvée.' : 'Note de frais rejetée.';

        return $this->handleResponse(
            $expenseSheet->load(['costs.formCost', 'user', 'department', 'validatedBy']),
            $message
        );
    }
}
