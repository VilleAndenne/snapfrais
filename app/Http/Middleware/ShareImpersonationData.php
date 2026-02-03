<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Mirror\Facades\Mirror;
use Symfony\Component\HttpFoundation\Response;

class ShareImpersonationData
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            Inertia::share([
                'impersonating' => Mirror::isImpersonating(),
                'impersonatedUser' => Mirror::isImpersonating() ? [
                    'id' => auth()->user()->id,
                    'name' => auth()->user()->name,
                    'email' => auth()->user()->email,
                ] : null,
            ]);
        }

        return $next($request);
    }
}
