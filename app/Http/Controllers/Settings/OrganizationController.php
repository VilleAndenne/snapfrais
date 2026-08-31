<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    /**
     * Show the settings of the organization resolved for the current request.
     */
    public function edit(Request $request): Response
    {
        $organization = $this->currentOrganizationOrFail($request);

        return Inertia::render('settings/Organization', [
            'organization' => [
                'id' => $organization->id,
                'organizationName' => $organization->organization_name,
                'dsf_recipient_email' => $organization->dsf_recipient_email,
            ],
        ]);
    }

    /**
     * Update the settings of the organization resolved for the current request.
     */
    public function update(Request $request): RedirectResponse
    {
        $organization = $this->currentOrganizationOrFail($request);

        $validated = $request->validate([
            'dsf_recipient_email' => ['nullable', 'email', 'max:255'],
        ], [
            'dsf_recipient_email.email' => 'Veuillez saisir une adresse email valide.',
        ]);

        $organization->update($validated);

        return to_route('organization.edit')->with('success', 'Paramètres de l\'organisation mis à jour.');
    }

    /**
     * Resolve the current organization and authorize the user against it.
     * Aborts with a 404 outside any organization context (bare application host).
     */
    private function currentOrganizationOrFail(Request $request): Organization
    {
        $organization = currentOrganization();

        abort_if($organization === null, 404);
        abort_if(! $request->user()->can('update', $organization), 403);

        return $organization;
    }
}
