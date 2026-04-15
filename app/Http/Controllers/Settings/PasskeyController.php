<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StorePasskeyRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyRegisterOptionsAction;
use Spatie\LaravelPasskeys\Actions\StorePasskeyAction;
use Spatie\LaravelPasskeys\Models\Passkey;
use Throwable;

class PasskeyController extends Controller
{
    public function edit(Request $request): Response
    {
        $passkeys = $request->user()->passkeys()
            ->latest()
            ->get()
            ->map(fn (Passkey $passkey): array => [
                'id' => $passkey->id,
                'name' => $passkey->name,
                'last_used_at' => $passkey->last_used_at,
                'created_at' => $passkey->created_at,
            ]);

        return Inertia::render('settings/Passkeys', [
            'passkeys' => $passkeys,
        ]);
    }

    public function generateOptions(Request $request, GeneratePasskeyRegisterOptionsAction $action): HttpResponse
    {
        return response($action->execute($request->user()))
            ->header('Content-Type', 'application/json');
    }

    public function store(StorePasskeyRequest $request, StorePasskeyAction $action): RedirectResponse
    {
        $data = $request->validated();

        try {
            $action->execute(
                $request->user(),
                $data['passkey'],
                $data['options'],
                $request->getHost(),
                ['name' => $data['name']],
            );
        } catch (Throwable $exception) {
            Log::error('Passkey registration failed', [
                'exception' => $exception,
                'host' => $request->getHost(),
                'rp_id' => config('passkeys.relying_party.id'),
            ]);

            throw ValidationException::withMessages([
                'name' => "Impossible d'enregistrer cette clé d'accès (".class_basename($exception).': '.$exception->getMessage().').',
            ]);
        }

        return redirect()->route('passkeys.edit');
    }

    public function destroy(Request $request, Passkey $passkey): RedirectResponse
    {
        abort_unless((int) $passkey->authenticatable_id === (int) $request->user()->id, 403);

        $passkey->delete();

        return redirect()->route('passkeys.edit');
    }
}
