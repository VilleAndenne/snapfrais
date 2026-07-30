<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ResolveOrganization
{
    /**
     * Session key holding the organization a super_admin switched to, letting
     * them operate on another organization without changing the request host.
     */
    public const SESSION_KEY = 'active_organization_id';

    /**
     * Resolve the current organization from the request subdomain, bind it for
     * the request lifecycle, and ensure the authenticated user belongs to it.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $organization = $this->resolveActiveOrganization($request, $user);

        if ($organization === null) {
            return $next($request);
        }

        setCurrentOrganization($organization);

        if ($user !== null && ! $user->super_admin && ! $user->belongsToOrganization($organization)) {
            abort(403, "Vous n'avez pas accès à cette organisation.");
        }

        return $next($request);
    }

    /**
     * Determine the active organization for the request. A super_admin's session
     * override takes precedence — it is how they switch organization without
     * changing the URL — otherwise the organization is resolved from the host.
     */
    private function resolveActiveOrganization(Request $request, ?User $user): ?Organization
    {
        if ($user?->super_admin) {
            $override = $this->resolveOverride($request);

            if ($override !== null) {
                return $override;
            }
        }

        return $this->resolveOrganization($request);
    }

    /**
     * Resolve the organization a super_admin switched to, clearing the session
     * key if it points to an organization that no longer exists.
     */
    private function resolveOverride(Request $request): ?Organization
    {
        $overrideId = $request->session()->get(self::SESSION_KEY);

        if ($overrideId === null) {
            return null;
        }

        $organization = Organization::find($overrideId);

        if ($organization === null) {
            $request->session()->forget(self::SESSION_KEY);
        }

        return $organization;
    }

    /**
     * Resolve the organization for the request.
     *
     * Priority to an exact match on the organization's own `domain`, then a
     * fallback to the `{slug}.{APP_URL host}` subdomain convention. A subdomain
     * that matches no organization aborts with 404; the bare application host
     * (no subdomain, no matching domain) resolves to null (no tenant context).
     */
    private function resolveOrganization(Request $request): ?Organization
    {
        $host = $request->getHost();

        $organization = Organization::where('domain', $host)->first();

        if ($organization !== null) {
            return $organization;
        }

        $slug = $this->resolveSlug($request);

        if ($slug === null) {
            return null;
        }

        $organization = Organization::where('slug', $slug)->first();

        if ($organization === null) {
            abort(404);
        }

        return $organization;
    }

    /**
     * Extract the organization slug from the host subdomain, if present.
     *
     * `ville.snapfrais.be` (app host `snapfrais.be`) resolves to `ville`.
     * The bare application host resolves to `null` (no organization context).
     */
    private function resolveSlug(Request $request): ?string
    {
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';
        $host = $request->getHost();

        if ($host === $appHost || ! Str::endsWith($host, '.'.$appHost)) {
            return null;
        }

        $slug = Str::before($host, '.'.$appHost);

        return $slug === '' ? null : $slug;
    }
}
