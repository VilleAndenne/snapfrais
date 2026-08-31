<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    /**
     * Only an administrator of the organization (or a super admin) may change
     * its settings.
     */
    public function update(User $user, Organization $organization): bool
    {
        if ($user->super_admin) {
            return true;
        }

        return $user->is_admin && $user->belongsToOrganization($organization);
    }
}
