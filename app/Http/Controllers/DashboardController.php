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
        $user  = auth()->user();

        // Query de base pour MES notes (filtré par mois en cours)
        $baseQueryMyNotes = ExpenseSheet::with([
            'form',
            'costs',
            'department.heads',
            'department.parent.heads',
            'user'
        ])
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->orderBy('created_at', 'desc');

        // Mes propres notes du mois en cours
        $myExpenseSheets = (clone $baseQueryMyNotes)
            ->where('user_id', $user->id)
            ->get();

        // Query de base pour les notes à valider (SANS filtre de mois)
        $baseQueryToValidate = ExpenseSheet::with([
            'form',
            'costs',
            'department.heads',
            'department.parent.heads',
            'user'
        ])
            ->orderBy('created_at', 'desc');

        // Candidats à la validation : je suis head du département de la note OU du parent (N+1)
        $candidateToValidate = (clone $baseQueryToValidate)
            ->where(function ($q) use ($user) {
                $q->whereHas('department.heads', function ($h) use ($user) {
                    $h->where('users.id', $user->id);
                })
                    ->orWhereHas('department.parent.heads', function ($h) use ($user) {
                        $h->where('users.id', $user->id);
                    });
            })
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
