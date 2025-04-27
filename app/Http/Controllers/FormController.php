<?php

namespace App\Http\Controllers;

use App\Models\Form;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FormController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('forms/IndexForms', [
            'forms' => Form::all(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('forms/CreateForm');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'costs' => 'required|array|min:1',
            'costs.*.name' => 'required|string|max:255',
            'costs.*.description' => 'required|string|max:255',
            'costs.*.type' => 'required|string|in:km,fixed,percentage',
            'costs.*.reimbursement_rates' => 'nullable|array',
            'costs.*.reimbursement_rates.*.start_date' => 'required_with:costs.*.reimbursement_rates|date',
            'costs.*.reimbursement_rates.*.end_date' => 'nullable|date|after_or_equal:costs.*.reimbursement_rates.*.start_date',
            'costs.*.reimbursement_rates.*.value' => 'required_with:costs.*.reimbursement_rates|numeric|min:0',

            'costs.*.requirements' => 'nullable|array',
            'costs.*.requirements.*.name' => 'required_with:costs.*.requirements|string|max:255',
            'costs.*.requirements.*.type' => 'required_with:costs.*.requirements|string|in:file,text',
        ]);

        // Créer le formulaire
        $form = Form::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
            'organization_id' => auth()->user()->organization_id,
        ]);

        foreach ($validated['costs'] as $costData) {
            $cost = $form->costs()->create([
                'name' => $costData['name'],
                'description' => $costData['description'],
                'type' => $costData['type'],
            ]);

            // Créer les taux de remboursement
            if (!empty($costData['reimbursement_rates'])) {
                foreach ($costData['reimbursement_rates'] as $rateData) {
                    $cost->reimbursementRates()->create([
                        'start_date' => $rateData['start_date'],
                        'end_date' => $rateData['end_date'],
                        'value' => $rateData['value'],
                    ]);
                }
            }

            // Créer les prérequis
            if (!empty($costData['requirements'])) {
                foreach ($costData['requirements'] as $requirementData) {
                    $cost->requirements()->create([
                        'name' => $requirementData['name'],
                        'type' => $requirementData['type'],
                    ]);
                }
            }
        }

        return redirect()->route('forms.index')->with('success', 'Formulaire créé avec succès!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Form $form)
    {
        $form->load('costs.reimbursementRates', 'costs.requirements');

        // ✅ S'assurer que le tableau requirements est toujours défini
        $form->costs->each(function ($cost) {
            if (!$cost->requirements) {
                $cost->requirements = [];
            }

            if (!$cost->reimbursementRates) {
                $cost->reimbursementRates = [];
            }
        });

        return inertia('forms/EditForm', [
            'form' => $form
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Form $form)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'costs' => 'required|array|min:1',
            'costs.*.id' => 'nullable|exists:form_costs,id',
            'costs.*.name' => 'required|string|max:255',
            'costs.*.description' => 'required|string|max:255',
            'costs.*.type' => 'required|string|in:km,fixed,percentage',

            'costs.*.reimbursement_rates' => 'nullable|array',
            'costs.*.reimbursement_rates.*.id' => 'nullable|exists:form_cost_remboursiement_rates,id',
            'costs.*.reimbursement_rates.*.start_date' => 'required_with:costs.*.reimbursement_rates|date',
            'costs.*.reimbursement_rates.*.end_date' => [
                'nullable',
                'date',
                'after_or_equal:costs.*.reimbursement_rates.*.start_date',
                function ($attribute, $value, $fail) {
                    if ($value && now()->gt($value)) {
                        $fail('La date de fin doit être dans le futur.');
                    }
                }
            ],
            'costs.*.reimbursement_rates.*.value' => 'required_with:costs.*.reimbursement_rates|numeric|min:0',

            'costs.*.requirements' => 'nullable|array',
            'costs.*.requirements.*.id' => 'nullable|exists:form_cost_requirements,id',
            'costs.*.requirements.*.name' => 'required_with:costs.*.requirements|string|max:255',
            'costs.*.requirements.*.type' => 'required_with:costs.*.requirements|string|in:file,text',
        ]);

        // ✅ Mettre à jour le formulaire
        $form->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
        ]);

        // ✅ Récupérer les IDs des coûts envoyés dans la requête
        $incomingCostIds = collect($validated['costs'])->pluck('id')->filter()->toArray();

        // ✅ Supprimer les coûts qui ne sont plus dans la requête
        $form->costs()->whereNotIn('id', $incomingCostIds)->delete();

        // ✅ Traiter chaque coût
        foreach ($validated['costs'] as $costData) {
            if (!empty($costData['id'])) {
                // ✅ Mise à jour du coût existant
                $cost = $form->costs()->find($costData['id']);
                $cost->update([
                    'name' => $costData['name'],
                    'type' => $costData['type'],
                ]);
            } else {
                // ✅ Création d'un nouveau coût
                $cost = $form->costs()->create([
                    'name' => $costData['name'],
                    'type' => $costData['type'],
                    'description' => $costData['description'],
                ]);
            }

            // ✅ Mise à jour des taux de remboursement
            $incomingRateIds = collect($costData['reimbursement_rates'])->pluck('id')->filter()->toArray();
            $cost->reimbursementRates()->whereNotIn('id', $incomingRateIds)->delete();

            foreach ($costData['reimbursement_rates'] as $rateData) {
                if (!empty($rateData['id'])) {
                    // ✅ Mise à jour d'un taux existant
                    $rate = $cost->reimbursementRates()->find($rateData['id']);
                    $rate->update([
                        'start_date' => $rateData['start_date'],
                        'end_date' => $rateData['end_date'],
                        'value' => $rateData['value'],
                    ]);
                } else {
                    // ✅ Création d'un nouveau taux
                    $cost->reimbursementRates()->create([
                        'start_date' => $rateData['start_date'],
                        'end_date' => $rateData['end_date'],
                        'value' => $rateData['value'],
                    ]);
                }
            }

            // ✅ Mise à jour des requirements (prérequis)
            $incomingRequirementIds = collect($costData['requirements'])->pluck('id')->filter()->toArray();
            $cost->requirements()->whereNotIn('id', $incomingRequirementIds)->delete();

            foreach ($costData['requirements'] as $requirementData) {
                $content = [];
                if (!empty($requirementData['id'])) {
                    // ✅ Mise à jour d'un requirement existant
                    $requirement = $cost->requirements()->find($requirementData['id']);

                    // Vérifier si le requirement est un fichier
                    if ($requirementData['type'] === 'file' && isset($requirementData['file']) && $requirementData['file'] instanceof \Illuminate\Http\UploadedFile) {
                        $path = $requirementData['file']->store('requirements', 'public');
                        $content = ['file' => $path];
                    } elseif ($requirementData['type'] === 'text' && isset($requirementData['value'])) {
                        $content = ['value' => $requirementData['value']];
                    }

                    $requirement->update([
                        'name' => $requirementData['name'],
                        'type' => $requirementData['type'],
                        'content' => json_encode($content),
                    ]);
                } else {
                    // ✅ Création d'un nouveau requirement
                    if ($requirementData['type'] === 'file' && isset($requirementData['file']) && $requirementData['file'] instanceof \Illuminate\Http\UploadedFile) {
                        $path = $requirementData['file']->store('requirements', 'public');
                        $content = ['file' => $path];
                    } elseif ($requirementData['type'] === 'text' && isset($requirementData['value'])) {
                        $content = ['value' => $requirementData['value']];
                    }

                    $cost->requirements()->create([
                        'name' => $requirementData['name'],
                        'type' => $requirementData['type'],
                        'content' => json_encode($content),
                    ]);
                }
            }
        }

        return redirect()->route('forms.index')->with('success', 'Formulaire mis à jour avec succès!');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
