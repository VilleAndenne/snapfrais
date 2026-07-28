<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ResolveOrganization
{
    /**
     * Resolve the current organization from the request subdomain, bind it for
     * the request lifecycle, and ensure the authenticated user belongs to it.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $this->resolveSlug($request);

        if ($slug === null) {
            return $next($request);
        }

        $organization = Organization::where('slug', $slug)->first();

        if ($organization === null) {
            abort(404);
        }

        setCurrentOrganization($organization);

        $user = $request->user();

        if ($user !== null && ! $user->super_admin && ! $user->belongsToOrganization($organization)) {
            abort(403, "Vous n'avez pas accès à cette organisation.");
        }

        return $next($request);
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
