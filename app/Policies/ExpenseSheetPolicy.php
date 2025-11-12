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
     * Vérifie si la note de frais doit apparaître dans la liste "à valider" de l'utilisateur.
     * Cette méthode est utilisée pour filtrer l'affichage, pas pour les permissions.
     */
    public function shouldAppearInValidationList(User $user, ExpenseSheet $expenseSheet): bool
    {
        // Ne pas afficher les brouillons
        if ($expenseSheet->is_draft) {
            return false;
        }

        // Ne pas afficher les notes déjà traitées
        if (!is_null($expenseSheet->approved)) {
            return false;
        }

        // Ne jamais afficher ses propres notes dans la liste (même pour admin)
        if ($expenseSheet->user_id === $user->id) {
            return false;
        }

        // Département lié à la note de frais
        $department = $expenseSheet->department;

        // Vérifie si l'utilisateur est responsable du département ou d'un parent
        while ($department) {
            // 1. L'utilisateur est responsable ici ?
            if ($department->heads->contains($user)) {
                // 2. L'auteur de la note est aussi responsable ici ?
                if ($department->heads->contains($expenseSheet->user)) {
                    return false; // même service, 2 responsables : ne pas afficher
                }

                // Afficher si l'utilisateur est responsable (admin ou non)
                return true;
            }

            $department = $department->parent;
        }

        return false;
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

        // Déjà traité - vérifie si approved n'est pas null
        if (!is_null($expenseSheet->approved)) {
            return false;
        }

        // Administrateur → toujours autorisé (même pour ses propres notes)
        if ($user->is_admin) {
            return true;
        }

        // L'utilisateur tente de valider sa propre note (non-admin)
        if ($expenseSheet->user_id === $user->id) {
            return false;
        }

        // Département lié à la note de frais
        $department = $expenseSheet->department;

        // Vérifie si l'utilisateur est responsable du département ou d'un parent
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
        $department = $expenseSheet->department;

        while ($department) {
            // Si l'utilisateur est responsable du service
            if ($department->heads->contains($user)) {
                // Si l'auteur est aussi responsable du même service, accès refusé
                if ($department->heads->contains($expenseSheet->user)) {
                    return false;
                }
                return true;
            }
            $department = $department->parent;
        }

        return false;
    }

    public function export(User $user)
    {
        return $user->is_admin == true;
    }

    public function destroy(User $user, ExpenseSheet $expenseSheet)
    {
        // Seul un administrateur ou le propriétaire d'un brouillon non approuvé peut supprimer une note de frais
        if ($expenseSheet->approved == true) {
            return false;
        }
        return $user->is_admin == true || ($expenseSheet->user_id === $user->id && $expenseSheet->is_draft);
    }
}
