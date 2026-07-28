<?php

namespace App\Notifications\Concerns;

use App\Models\Organization;

trait LinksToOrganization
{
    /**
     * Build an absolute link on the organization's own host.
     *
     * Notifications are queued and therefore run without a request context,
     * so `url()` would otherwise fall back to the bare APP_URL host. When the
     * organization is known we route the link to its own domain/subdomain;
     * otherwise we degrade gracefully to the application default.
     */
    protected function organizationUrl(?Organization $organization, string $path): string
    {
        return $organization?->url($path) ?? url($path);
    }
}
