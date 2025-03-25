<?php

namespace App\Http\Controllers;

use App\Models\ExpenseSheet;
use App\Models\Form;
use App\Models\FormCost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


class ExpenseSheetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */

    public function create($id)
    {
        $form = Form::with('costs.reimbursementRates', 'costs.requirements')->findOrFail($id);

        return inertia('expenseSheet/CreateExpenseSheet', [
            'form' => $form,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */


    public function store(Request $request)
    {
        $validated = $request->validate([
            'costs' => 'required|array|max:7',
            'costs.*.cost_id' => 'required|exists:form_costs,id',
            'costs.*.data' => 'required|array',
            'costs.*.requirements' => 'nullable|array',
        ]);

        foreach ($validated['costs'] as $costItem) {
            $formCost = FormCost::findOrFail($costItem['cost_id']);
            $type = $formCost->type;
            $total = 0;
            $distance = null;
            $googleDistance = null;
            $route = null;

            // 🔁 Récupère le taux actif depuis la DB
            $rate = $formCost->reimbursementRates()
                ->where('start_date', '<=', now())
                ->where(function ($q) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', now());
                })
                ->orderByDesc('start_date')
                ->first();

            if (!$rate) {
                continue;
            }

            if ($type === 'km') {
                $data = $costItem['data'];
                $origin = $data['departure'] ?? null;
                $destination = $data['arrival'] ?? null;
                $steps = $data['steps'] ?? [];
                $manualKm = $data['manualKm'] ?? 0;

                if (!$origin || !$destination) {
                    continue; // on ignore ce coût mal rempli
                }

                $params = [
                    'origin' => $origin,
                    'destination' => $destination,
                    'mode' => 'driving',
                    'key' => env('GOOGLE_MAPS_API_KEY'),
                ];

                if (!empty($steps)) {
                    $params['waypoints'] = implode('|', $steps);
                }

                $response = Http::get("https://maps.googleapis.com/maps/api/directions/json", $params);

                $googleKm = 0;

                if ($response->successful() && isset($response['routes'][0]['legs'])) {
                    foreach ($response['routes'][0]['legs'] as $leg) {
                        $googleKm += $leg['distance']['value'] ?? 0;
                    }
                    $googleKm = round($googleKm / 1000, 2); // Convertir en km
                } else {
                    logger()->warning('Google Maps API error:', [
                        'response' => $response->json(),
                        'origin' => $origin,
                        'destination' => $destination,
                        'steps' => $steps
                    ]);
                    $googleKm = 0;
                }

                $googleDistance = $googleKm;
                $distance = $googleKm + $manualKm;
                $total = round($distance * $rate->value, 2);

                $route = [
                    'departure' => $origin,
                    'arrival' => $destination,
                    'steps' => $steps,
                    'google_km' => $googleKm,
                    'manual_km' => $manualKm,
                    'justification' => $data['justification'] ?? null,
                ];
            } elseif ($type === 'fixed') {
                $total = round($rate->value, 2);
            } elseif ($type === 'percentage') {
                $paid = $costItem['data']['paidAmount'] ?? 0;
                $total = round($paid * ($rate->value / 100), 2);
            }

            ExpenseSheet::create([
                'user_id' => auth()->id(),
                'type' => $type,
                'distance' => $distance,
                'google_distance' => $googleDistance,
                'route' => $route,
                'total' => $total,
                'status' => 'En attente',
            ]);
        }

        return redirect()->route('expense-sheets.index')->with('success', 'Notes de frais enregistrées.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ExpenseSheet $expenseSheet)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ExpenseSheet $expenseSheet)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ExpenseSheet $expenseSheet)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ExpenseSheetController $expenseSheetController)
    {
        //
    }
}
