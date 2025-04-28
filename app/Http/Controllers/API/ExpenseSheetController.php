<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ExpenseSheet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
                ->filter(fn($expenseSheet) => auth()->user()->can('view', $expenseSheet)),
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
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
