<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ExpenseSheet;

class ExpenseSheetPolicy
{
    /**
     * Vérifie si l'utilisateur peut approuver la note de frais.
     */
    public function approve(User $user, ExpenseSheet $expenseSheet)
    {
        if (isset($expenseSheet->approved)) {
            return false;
        }
        // Récupérer le service lié à l'expenseSheet
        $department = $expenseSheet->department;

        // Vérifier si l'utilisateur est responsable du service ou d'un service parent
        while ($department) {
            if ($department->heads->contains($user)) {
                return true;
            }
            $department = $department->parent;
        }

        // Si aucun service parent ne donne l'autorisation, renvoyer false
        return false;
    }

    /**
     * Vérifie si l'utilisateur peut rejeter la note de frais.
     */
    public function reject(User $user, ExpenseSheet $expenseSheet)
    {
        if (isset($expenseSheet->approved)) {
            return false;
        }
        // Récupérer le service lié à l'expenseSheet
        $department = $expenseSheet->department;

        // Vérifier si l'utilisateur est responsable du service ou d'un service parent
        while ($department) {
            if ($department->heads->contains($user)) {
                return true;
            }
            $department = $department->parent;
        }

        // Si aucun service parent ne donne l'autorisation, renvoyer false
        return false;
    }

    /**
     * Vérifie si l'utilisateur peut modifier une note de frais spécifique.
     */
    public function edit(User $user, ExpenseSheet $expenseSheet)
    {
        // Can edit if the user is the owner of the expense sheet and the status is 'draft' or 'rejected'
        return ($user->id === $expenseSheet->user_id && $expenseSheet->approved === false);
    }

    /**
     * Vérifie si l'utilisateur peut voir une note de frais spécifique.
     */
    public function view(User $user, ExpenseSheet $expenseSheet)
    {
        if ($expenseSheet->user_id === $user->id) {
            return true;
        }
        // Récupérer le service lié à l'expenseSheet
        $department = $expenseSheet->department;

        // Vérifier si l'utilisateur est responsable du service ou d'un service parent
        while ($department) {
            if ($department->heads->contains($user)) {
                return true;
            }
            $department = $department->parent;
        }

        // Si aucun service parent ne donne l'autorisation, renvoyer false
        return false;
    }
}
