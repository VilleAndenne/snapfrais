<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Session;
use Spatie\LaravelPasskeys\Events\PasskeyUsedToAuthenticateEvent;

class RedirectUnverifiedPasskeyUser
{
    public function handle(PasskeyUsedToAuthenticateEvent $event): void
    {
        $user = $event->passkey->authenticatable;

        if ($user === null) {
            return;
        }

        if ($user->getAttribute('email_verified_at') === null) {
            Session::put('passkeys.redirect', route('verification.notice'));
        }
    }
}
