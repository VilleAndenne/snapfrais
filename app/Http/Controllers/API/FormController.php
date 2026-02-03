<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Form;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FormController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'forms' => Form::all(),
        ]);
    }

    /**
     * Display a specified resource.
     */
    public function show($id): JsonResponse
    {
        $form = Form::with('costs.reimbursementRates', 'costs.requirements')->findOrFail($id);

        return response()->json([
            'form' => $form,
            'departments' => auth()->user()->departments()->with('heads')->get(),
        ]);
    }

}
