<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TermsAcceptanceController extends Controller
{
    /**
     * Show the terms acceptance page.
     */
    public function show(): Response
    {
        return Inertia::render('Legal/AcceptTerms');
    }

    /**
     * Record the user's acceptance of the latest terms.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'accepted' => ['accepted'],
        ]);

        $user = $request->user();
        $user->forceFill(['terms_accepted_at' => now()])->save();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
