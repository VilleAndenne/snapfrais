<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PaymentDetailsRequest;
use Illuminate\Http\RedirectResponse;

class PaymentDetailsController extends Controller
{
    /**
     * Enregistre les coordonnées de remboursement de l'agent. Alimenté aussi bien
     * par la page de profil que par la fenêtre affichée à l'ouverture de
     * l'application tant que ces données manquent.
     */
    public function update(PaymentDetailsRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return back()->with('success', 'Vos coordonnées de remboursement ont été enregistrées.');
    }
}
