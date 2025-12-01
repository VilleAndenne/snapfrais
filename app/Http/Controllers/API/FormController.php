<?php

namespace App\Http\Controllers\API;

use App\Models\Form;
use Illuminate\Http\JsonResponse;

class FormController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        return $this->handleResponse(Form::all());
    }

    /**
     * Display a specified resource.
     */
    public function show($id): JsonResponse
    {
        $form = Form::with('costs.reimbursementRates', 'costs.requirements')->findOrFail($id);

        return $this->handleResponse([
            'form' => $form,
            'departments' => auth()->user()->departments()->with('heads')->get(),
        ]);
    }
}
