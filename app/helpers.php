<?php

use App\Models\Organization;

if (! function_exists('currentOrganization')) {
    /**
     * Retrieve the organization resolved for the current request, if any.
     */
    function currentOrganization(): ?Organization
    {
        return app()->bound('tenant.organization')
            ? app('tenant.organization')
            : null;
    }
}

if (! function_exists('setCurrentOrganization')) {
    /**
     * Bind (or clear) the organization for the current request lifecycle.
     */
    function setCurrentOrganization(?Organization $organization): void
    {
        if ($organization === null) {
            app()->forgetInstance('tenant.organization');

            return;
        }

        app()->instance('tenant.organization', $organization);
    }
}
