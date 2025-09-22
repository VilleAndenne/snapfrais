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

        $baseQuery = ExpenseSheet::with([
            'form',
            'costs',
            'department.heads',
            'department.parent.heads',
            'user'
        ])
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->orderBy('created_at', 'desc');

        // Mes propres notes
        $myExpenseSheets = (clone $baseQuery)
            ->where('user_id', $user->id)
            ->get();

        // Candidats à la validation : je suis head du département de la note OU du parent (N+1)
        $candidateToValidate = (clone $baseQuery)
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
            return Gate::allows('approve', $sheet);
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
