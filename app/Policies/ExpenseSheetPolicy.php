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
        return $this->canModerate($user, $expenseSheet);
    }

    public function reject(User $user, ExpenseSheet $expenseSheet)
    {
        return $this->canModerate($user, $expenseSheet);
    }

    /**
     * Vérifie si l'utilisateur peut modérer (valider/rejeter) la note de frais.
     */
    protected function canModerate(User $user, ExpenseSheet $expenseSheet): bool
    {
        // Ne peut pas approuver/rejeter un brouillon
        if ($expenseSheet->is_draft) {
            return false;
        }

        // Déjà traité
        if (isset($expenseSheet->approved)) {
            return false;
        }

        // Administrateur → toujours autorisé (sauf pour sa propre note déjà gérée ci-dessus)
        if ($user->is_admin) {
            return true;
        }

        // L'utilisateur tente de valider sa propre note
        if ($expenseSheet->user_id === $user->id) {
            return false;
        }


        // Département lié à la note de frais
        $department = $expenseSheet->department;

        // Vérifie si l'utilisateur est responsable du département ou d’un parent
        while ($department) {
            // 1. L'utilisateur est responsable ici ?
            if ($department->heads->contains($user)) {

                // 2. L'auteur de la note est aussi responsable ici ?
                if ($department->heads->contains($expenseSheet->user)) {
                    return false; // même service, 2 responsables : rejeté
                }

                return true; // utilisateur responsable, auteur non-responsable = ok
            }

            $department = $department->parent;
        }

        return false;
    }


    /**
     * Vérifie si l'utilisateur peut modifier une note de frais spécifique.
     */
    public function edit(User $user, ExpenseSheet $expenseSheet)
    {
        // Admin peut toujours modifier
        if ($user->is_admin) {
            return true;
        }

        // Le propriétaire peut modifier si :
        // - C'est un brouillon OU
        // - Le statut est "rejeté" (approved === 0/false)
        return $expenseSheet->user_id === $user->id &&
               ($expenseSheet->is_draft || $expenseSheet->approved === 0);
    }

    /**
     * Vérifie si l'utilisateur peut voir une note de frais spécifique.
     */
    public function view(User $user, ExpenseSheet $expenseSheet)
    {
        if ($expenseSheet->user_id === $user->id) {
            return true;
        } elseif ($user->is_admin == true) {
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

    public function export(User $user)
    {
        return $user->is_admin == true;
    }

    public function destroy(User $user, ExpenseSheet $expenseSheet)
    {
        // Seul un administrateur peut supprimer une note de frais
        return $user->is_admin == true OR ($expenseSheet->user_id === $user->id && $expenseSheet->is_draft);
    }
}
