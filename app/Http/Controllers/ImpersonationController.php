<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Mirror\Facades\Mirror;

class ImpersonationController extends Controller
{
    /**
     * Start impersonating a user.
     */
    public function start(User $user): RedirectResponse
    {
        if (! auth()->user()->canImpersonate()) {
            abort(403, 'Vous n\'avez pas la permission d\'impersonner des utilisateurs.');
        }

        if (! $user->canBeImpersonated()) {
            abort(403, 'Cet utilisateur ne peut pas être impersonné.');
        }

        Mirror::start($user);

        return redirect()->route('dashboard');
    }

    /**
     * Stop impersonating.
     */
    public function stop(): RedirectResponse
    {
        Mirror::stop();

        return redirect()->route('users.index');
    }
}
