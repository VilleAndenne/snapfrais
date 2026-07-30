<?php

namespace App\Http\Controllers;

use App\Http\Middleware\ResolveOrganization;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrganizationSwitchController extends Controller
{
    /**
     * Switch the active organization, persisting the choice in the session so it
     * survives across requests without changing the URL. A super_admin may reach
     * any organization; a platform admin only the ones they belong to.
     */
    public function update(Request $request, Organization $organization): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user !== null && $user->canSwitchToOrganization($organization), 403);

        $request->session()->put(ResolveOrganization::SESSION_KEY, $organization->getKey());

        return back(fallback: route('dashboard'))
            ->with('success', "Organisation active : {$organization->organization_name}.");
    }
}
