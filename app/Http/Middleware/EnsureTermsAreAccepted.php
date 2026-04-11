<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTermsAreAccepted
{
    /**
     * Routes that must remain accessible even when the user has not accepted
     * the latest version of the terms (otherwise the user could not consult
     * the terms or log out).
     *
     * @var list<string>
     */
    protected array $exceptRoutes = [
        'terms.accept',
        'terms.accept.store',
        'terms.show',
        'logout',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        if ($user->hasAcceptedCurrentTerms()) {
            return $next($request);
        }

        if (in_array($request->route()?->getName(), $this->exceptRoutes, true)) {
            return $next($request);
        }

        return redirect()->route('terms.accept');
    }
}
