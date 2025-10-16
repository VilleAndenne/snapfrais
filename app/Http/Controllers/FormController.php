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
            'costs.*.processing_department' => 'required|string|in:SRH,DSF',

            'costs.*.reimbursement_rates' => 'nullable|array',
            'costs.*.reimbursement_rates.*.start_date' => 'required_with:costs.*.reimbursement_rates|date',
            'costs.*.reimbursement_rates.*.end_date' => 'nullable|date|after_or_equal:costs.*.reimbursement_rates.*.start_date',
            'costs.*.reimbursement_rates.*.value' => 'required_with:costs.*.reimbursement_rates|numeric|min:0',

            // 🔸 nouveau : transport pour les taux (utile pour type=km)
            // on le laisse nullable côté validation, et on forcera une valeur par défaut au save
            'costs.*.reimbursement_rates.*.transport' => 'nullable|string|in:car,bike,other',

            'costs.*.requirements' => 'nullable|array',
            'costs.*.requirements.*.name' => 'required_with:costs.*.requirements|string|max:255',
            'costs.*.requirements.*.type' => 'required_with:costs.*.requirements|string|in:file,text',
        ]);

        // Créer le formulaire
        $form = Form::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
        ]);

        foreach ($validated['costs'] as $costData) {
            $cost = $form->costs()->create([
                'name' => $costData['name'],
                'description' => $costData['description'],
                'type' => $costData['type'],
                'processing_department' => $costData['processing_department'] ?? 'SRH',
            ]);

            // Créer les taux de remboursement
            if (!empty($costData['reimbursement_rates'])) {
                foreach ($costData['reimbursement_rates'] as $rateData) {
                    // 🔸 si le coût est "km", on prend le transport fourni ou 'car' par défaut
                    // 🔸 si le coût n'est PAS "km", on peut ignorer transport (mais la colonne peut avoir un défaut DB)
                    $transport = $costData['type'] === 'km'
                        ? ($rateData['transport'] ?? 'car')
                        : ($rateData['transport'] ?? 'car'); // garde 'car' par défaut si colonne NOT NULL avec default

                    $cost->reimbursementRates()->create([
                        'start_date' => $rateData['start_date'],
                        'end_date' => $rateData['end_date'] ?? null,
                        'value' => $rateData['value'],
                        'transport' => $transport, // 🔸 nouveau champ
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
// FormController.php

    public function edit(Form $form)
    {
        // Charger les relations
        $form->load('costs.reimbursementRates', 'costs.requirements');

        // ✅ Normaliser les structures pour l'UI et garantir un transport par défaut
        $form->costs->each(function ($cost) {
            // Toujours des tableaux
            if (!$cost->requirements) {
                $cost->requirements = [];
            }
            if (!$cost->reimbursementRates) {
                $cost->reimbursementRates = [];
            }

            // Ajouter un transport par défaut pour chaque taux
            $cost->reimbursementRates->transform(function ($rate) use ($cost) {
                // Si la colonne n'existe pas encore en DB, ajoute la migration avant d'utiliser ceci.
                $rate->transport = $rate->transport ?? 'car';
                return $rate;
            });
        });

        return inertia('forms/EditForm', [
            'form' => $form
        ]);
    }


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
            'costs.*.processing_department' => 'required|string|in:SRH,DSF',

            'costs.*.reimbursement_rates' => 'nullable|array',
            // ⚠️ Si ton nom de table est mal orthographié, corrige-le ici.
            // 'costs.*.reimbursement_rates.*.id' => 'nullable|exists:form_cost_reimbursement_rates,id',
            'costs.*.reimbursement_rates.*.id' => 'nullable|integer',

            'costs.*.reimbursement_rates.*.start_date' => 'required_with:costs.*.reimbursement_rates|date',
            'costs.*.reimbursement_rates.*.end_date' => [
                'nullable',
                'date',
                'after_or_equal:costs.*.reimbursement_rates.*.start_date',
            ],
            'costs.*.reimbursement_rates.*.value' => 'required_with:costs.*.reimbursement_rates|numeric|min:0',
            // 🔸 Nouveau : transport
            'costs.*.reimbursement_rates.*.transport' => 'nullable|string|in:car,bike,other',

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

        // ✅ Supprimer les coûts qui ne sont plus présents
        $incomingCostIds = collect($validated['costs'])->pluck('id')->filter()->toArray();
        $form->costs()->whereNotIn('id', $incomingCostIds)->delete();

        // ✅ Traiter chaque coût
        foreach ($validated['costs'] as $costData) {
            if (!empty($costData['id'])) {
                // Mise à jour
                $cost = $form->costs()->findOrFail($costData['id']);
                $cost->update([
                    'name'        => $costData['name'],
                    'description' => $costData['description'], // 🔧 on met aussi à jour la description
                    'type'        => $costData['type'],
                    'processing_department' => $costData['processing_department'] ?? 'SRH',
                ]);
            } else {
                // Création
                $cost = $form->costs()->create([
                    'name'        => $costData['name'],
                    'description' => $costData['description'],
                    'type'        => $costData['type'],
                    'processing_department' => $costData['processing_department'] ?? 'SRH',
                ]);
            }

            // ✅ Reimbursement rates (taux)
            $incomingRateIds = collect($costData['reimbursement_rates'] ?? [])->pluck('id')->filter()->toArray();
            if (!empty($incomingRateIds)) {
                $cost->reimbursementRates()->whereNotIn('id', $incomingRateIds)->delete();
            } else {
                // S'il n'y a plus de taux, on supprime tout
                $cost->reimbursementRates()->delete();
            }

            foreach ($costData['reimbursement_rates'] ?? [] as $rateData) {
                $payload = [
                    'start_date' => $rateData['start_date'],
                    'end_date'   => $rateData['end_date'] ?? null,
                    'value'      => $rateData['value'],
                    'transport'  => ($costData['type'] === 'km')
                        ? ($rateData['transport'] ?? 'car') // défaut pour km
                        : ($rateData['transport'] ?? 'car'), // valeur par défaut si colonne NOT NULL
                ];

                if (!empty($rateData['id'])) {
                    // Update
                    $rate = $cost->reimbursementRates()->findOrFail($rateData['id']);
                    $rate->update($payload);
                } else {
                    // Create
                    $cost->reimbursementRates()->create($payload);
                }
            }

            // ✅ Requirements (prérequis)
            $incomingRequirementIds = collect($costData['requirements'] ?? [])->pluck('id')->filter()->toArray();
            if (!empty($incomingRequirementIds)) {
                $cost->requirements()->whereNotIn('id', $incomingRequirementIds)->delete();
            } else {
                $cost->requirements()->delete();
            }

            foreach ($costData['requirements'] ?? [] as $requirementData) {
                $reqPayload = [
                    'name' => $requirementData['name'],
                    'type' => $requirementData['type'],
                ];

                if (!empty($requirementData['id'])) {
                    $requirement = $cost->requirements()->findOrFail($requirementData['id']);
                    $requirement->update($reqPayload);
                } else {
                    $cost->requirements()->create($reqPayload);
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
        $form = Form::findOrFail($id);

        $form->delete();

        return redirect()->back()->with('success', 'Formulaire supprimé avec succès.');
    }
}
