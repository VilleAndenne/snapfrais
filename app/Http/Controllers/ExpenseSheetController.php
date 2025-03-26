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

    public function store(Request $request, $id)
    {
        $validated = $request->validate([
            'costs' => 'required|array|max:7',
            'costs.*.cost_id' => 'required|exists:form_costs,id',
            'costs.*.data' => 'required|array',
            'costs.*.requirements' => 'nullable|array',
        ]);

        $expenseSheet = ExpenseSheet::create([
            'user_id' => auth()->id(),
            'status' => 'En attente',
            'total' => 0,
            'form_id' => $id,
        ]);

        $globalTotal = 0;

        foreach ($validated['costs'] as $costItem) {
            $formCost = FormCost::find($costItem['cost_id']);
            $type = $formCost->type;

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

                    if (
                        $response->successful() &&
                        $json['status'] === 'OK' &&
                        isset($json['routes'][0]['legs'][0]['distance']['value'])
                    ) {
                        $googleKm += $json['routes'][0]['legs'][0]['distance']['value'];
                    } else {
                        logger()->warning('Erreur Google segment', [
                            'origin' => $segmentOrigin,
                            'destination' => $segmentDest,
                            'response' => $json,
                        ]);
                    }
                }

                $googleKm = round($googleKm / 1000, 2);
                $googleDistance = $googleKm;
                $distance = $googleKm + $manualKm;
                $total = $distance * $rate->value;

                $route = [
                    'departure' => $origin,
                    'arrival' => $destination,
                    'google_km' => $googleKm,
                    'manual_km' => $manualKm,
                    'justification' => $data['justification'] ?? null,
                ];
            } elseif ($type === 'fixed') {
                $total = $rate->value;
            } elseif ($type === 'percentage') {
                $paid = $data['paidAmount'] ?? 0;
                $total = $paid * ($rate->value / 100);
            }

            $createdCost = $expenseSheet->costs()->create([
                'form_cost_id' => $formCost->id,
                'type' => $type,
                'distance' => $distance,
                'google_distance' => $googleDistance,
                'route' => $route,
                'total' => $total,
            ]);

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

        $expenseSheet->update(['total' => $globalTotal]);

        return redirect()->route('dashboard')->with('success', 'Note de frais enregistrée.');
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
        $expenseSheet->load('costs.formCost.reimbursementRates', 'costs.formCost.requirements');

        return inertia('expenseSheet/EditExpenseSheet', [
            'form' => $expenseSheet,
            'costs' => FormCost::with('reimbursementRates', 'requirements')->get(),
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ExpenseSheet $expenseSheet)
    {
        $this->authorize('update', $expenseSheet);

        $validated = $request->validate([
            'costs' => 'required|array|max:7',
            'costs.*.cost_id' => 'required|exists:form_costs,id',
            'costs.*.data' => 'required|array',
            'costs.*.requirements' => 'nullable|array',
        ]);

        $expenseSheet->costs()->delete(); // On recrée tout
        $globalTotal = 0;

        foreach ($validated['costs'] as $costItem) {
            $formCost = FormCost::find($costItem['cost_id']);
            $type = $formCost->type;

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

            $data = $costItem['data'];
            $total = 0;
            $distance = null;
            $googleDistance = null;
            $route = null;
            $steps = [];

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

                    if (
                        $response->successful() &&
                        $json['status'] === 'OK' &&
                        isset($json['routes'][0]['legs'][0]['distance']['value'])
                    ) {
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
            } elseif ($type === 'fixed') {
                $total = round($rate->value, 2);
            } elseif ($type === 'percentage') {
                $paid = $data['paidAmount'] ?? 0;
                $total = round($paid * ($rate->value / 100), 2);
            }

            $createdCost = $expenseSheet->costs()->create([
                'form_cost_id' => $formCost->id,
                'type' => $type,
                'distance' => $distance,
                'google_distance' => $googleDistance,
                'route' => $route,
                'total' => $total,
            ]);

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

        $expenseSheet->update(['total' => $globalTotal]);

        return redirect()->route('dashboard')->with('success', 'Note de frais mise à jour.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ExpenseSheetController $expenseSheetController)
    {
        //
    }
}
