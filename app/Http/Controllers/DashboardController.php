<?php

namespace App\Http\Controllers;

use App\Models\ExpenseSheet;
use App\Models\Form;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function index()
    {
        $forms = Form::all();
        $user = auth()->user();

        // Query de base pour MES notes (filtré par mois en cours)
        $baseQueryMyNotes = ExpenseSheet::with([
            'form',
            'costs',
            'department.heads',
            'department.parent.heads',
            'user',
        ])
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->orderBy('created_at', 'desc');

        // Mes propres notes du mois en cours
        $myExpenseSheets = (clone $baseQueryMyNotes)
            ->where('user_id', $user->id)
            ->get();

        // Candidats à la validation : je suis head du département de la note OU du parent (N+1)
        $candidateToValidate = ExpenseSheet::query()
            ->withValidationRelations()
            ->pendingValidationBy($user)
            ->orderBy('created_at', 'desc')
            ->get();

        $expenseToValidate = $candidateToValidate->filter(function ($sheet) {
            return Gate::allows('shouldAppearInValidationList', $sheet);
        })->values();

        $isHead = $user->isHead();

        return inertia('Dashboard', [
            'forms' => $forms,
            'expenseSheets' => $myExpenseSheets,
            'expenseToValidate' => $expenseToValidate,
            'isHead' => $isHead,
        ]);
    }
}
