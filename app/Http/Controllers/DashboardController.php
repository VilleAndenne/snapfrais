<?php

namespace App\Http\Controllers;

use App\Models\ExpenseSheet;
use App\Models\Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function index()
    {
        $forms = Form::all();

        // Charger toutes les expenseSheets avec relations utiles
        $allExpenseSheets = ExpenseSheet::with('form', 'costs', 'department.heads', 'user')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->orderBy('created_at', 'desc')
            ->get();

        // Garder uniquement celles que l'utilisateur peut approuver (selon la policy)
        $expenseToValidate = $allExpenseSheets->filter(function ($sheet) {
            return Gate::allows('approve', $sheet);
        })->values();

        $myExpenseSheets = $allExpenseSheets->filter(function ($sheet) {
            return $sheet->user_id === auth()->id();
        })->values();

        $isHead = auth()->user()->isHead();

        return inertia('Dashboard', [
            'forms' => $forms,
            'expenseSheets' => $myExpenseSheets,
            'expenseToValidate' => $expenseToValidate,
            'isHead' => $isHead,
        ]);
    }

}
