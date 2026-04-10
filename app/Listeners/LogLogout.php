<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;

class LogLogout
{
    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        activity('authentication')
            ->causedBy($event->user)
            ->event('logout')
            ->withProperties([
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'guard' => $event->guard,
            ])
            ->log('Déconnexion');
    }
}
