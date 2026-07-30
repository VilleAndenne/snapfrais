<?php

namespace App\Http\Controllers;

use App\Http\Middleware\ResolveOrganization;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrganizationSwitchController extends Controller
{
    /**
     * Switch the active organization for a super_admin, persisting the choice in
     * the session so it survives across requests without changing the URL.
     */
    public function update(Request $request, Organization $organization): RedirectResponse
    {
        abort_unless((bool) $request->user()?->super_admin, 403);

        $request->session()->put(ResolveOrganization::SESSION_KEY, $organization->getKey());

        return back(fallback: route('dashboard'))
            ->with('success', "Organisation active : {$organization->organization_name}.");
    }
}
