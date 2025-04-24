<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the User can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->is_admin == true ? true : false;
    }

    /**
     * Determine whether the User can view the model.
     */
    public function view(User $user, User $managedUser): bool
    {
        return $user->is_admin == true ? true : false;
    }

    /**
     * Determine whether the User can create models.
     */
    public function create(User $user): bool
    {
        return $user->is_admin == true ? true : false;
    }

    /**
     * Determine whether the User can update the model.
     */
    public function update(User $user, User $managedUser): bool
    {
        return $user->is_admin == true ? true : false;
    }

    /**
     * Determine whether the User can delete the model.
     */
    public function delete(User $user, User $managedUser): bool
    {
        return $user->is_admin == true ? true : false;
    }

    /**
     * Determine whether the User can restore the model.
     */
    public function restore(User $user, User $managedUser): bool
    {
        return $user->is_admin == true ? true : false;
    }

    /**
     * Determine whether the User can permanently delete the model.
     */
    public function forceDelete(User $user, User $managedUser): bool
    {
        return $user->is_admin == true ? true : false;
    }
}
