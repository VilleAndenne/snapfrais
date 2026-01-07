<?php

namespace App\Policies;

use App\Models\ExpenseSheet;
use App\Models\User;

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
     *
     * Règles métier :
     * - Un responsable direct valide les notes des agents non-responsables de son service
     * - Le N+1 (responsable du parent) valide les notes des responsables de sous-services
     */
    public function shouldAppearInValidationList(User $user, ExpenseSheet $expenseSheet): bool
    {
        // Ne pas afficher les brouillons
        if ($expenseSheet->is_draft) {
            return false;
        }

        // Ne pas afficher les notes déjà traitées
        if (! is_null($expenseSheet->approved)) {
            return false;
        }

        // Ne jamais afficher ses propres notes dans la liste (même pour admin)
        if ($expenseSheet->user_id === $user->id) {
            return false;
        }

        // Département de la note de frais
        $noteDepartment = $expenseSheet->department;

        if (! $noteDepartment) {
            return false;
        }

        // L'auteur de la note est-il responsable de son département ?
        $authorIsHeadOfNoteDepartment = $noteDepartment->heads->contains($expenseSheet->user);

        // Cas 1 : L'utilisateur est responsable DIRECT du département de la note
        if ($noteDepartment->heads->contains($user)) {
            // Si l'auteur est aussi responsable du même département, ne pas afficher
            // (les co-responsables ne se valident pas entre eux)
            if ($authorIsHeadOfNoteDepartment) {
                return false;
            }

            // L'utilisateur est responsable direct, l'auteur est un agent → afficher
            return true;
        }

        // Cas 2 : L'utilisateur est responsable du PARENT (N+1)
        // Il ne voit que les notes des responsables de sous-services
        $parentDepartment = $noteDepartment->parent;
        if ($parentDepartment && $parentDepartment->heads->contains($user)) {
            // L'auteur doit être responsable de son département pour que le N+1 le valide
            if ($authorIsHeadOfNoteDepartment) {
                return true;
            }

            // L'auteur n'est pas responsable → c'est au responsable direct de valider, pas au N+1
            return false;
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
        if (! is_null($expenseSheet->approved)) {
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

        // Le propriétaire (bénéficiaire) peut modifier si :
        // - C'est un brouillon OU
        // - Le statut est "rejeté" (approved === 0/false)
        $isOwner = $expenseSheet->user_id === $user->id;

        // Le créateur peut modifier les brouillons qu'il a créés pour d'autres
        $isCreatorOfDraft = $expenseSheet->created_by === $user->id && $expenseSheet->is_draft;

        return ($isOwner && ($expenseSheet->is_draft || $expenseSheet->approved === 0))
            || $isCreatorOfDraft;
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

    /**
     * Vérifie si l'utilisateur (SRH/admin) peut renvoyer une note de frais déjà approuvée.
     */
    public function returnBySRH(User $user, ExpenseSheet $expenseSheet): bool
    {
        // Seuls les admins peuvent renvoyer une note
        if (! $user->is_admin) {
            return false;
        }

        // La note doit être approuvée pour pouvoir être renvoyée
        return $expenseSheet->approved == true;
    }
}
