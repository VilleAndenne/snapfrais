<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the User can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->is_admin === true;
    }

    /**
     * Determine whether the User can view the model.
     */
    public function view(User $user, User $managedUser): bool
    {
        return $this->canManage($user, $managedUser);
    }

    /**
     * Determine whether the User can create models.
     */
    public function create(User $user): bool
    {
        return $user->is_admin === true;
    }

    /**
     * Determine whether the User can update the model.
     */
    public function update(User $user, User $managedUser): bool
    {
        return $this->canManage($user, $managedUser);
    }

    /**
     * Determine whether the User can delete the model.
     */
    public function delete(User $user, User $managedUser): bool
    {
        return $this->canManage($user, $managedUser);
    }

    /**
     * Determine whether the User can restore the model.
     */
    public function restore(User $user, User $managedUser): bool
    {
        return $this->canManage($user, $managedUser);
    }

    /**
     * Determine whether the User can permanently delete the model.
     */
    public function forceDelete(User $user, User $managedUser): bool
    {
        return $this->canManage($user, $managedUser);
    }

    /**
     * An admin may manage a user only when both share the organization resolved
     * for the current request. Platform operators (super_admin) are transverse.
     */
    private function canManage(User $user, User $managedUser): bool
    {
        if ($user->super_admin === true) {
            return true;
        }

        if ($user->is_admin !== true) {
            return false;
        }

        $organization = currentOrganization();

        // Hors contexte tenant (accès sans sous-domaine), on conserve le
        // comportement mono-organisation historique.
        if ($organization === null) {
            return true;
        }

        return $managedUser->belongsToOrganization($organization);
    }
}
