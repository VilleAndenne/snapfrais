<?php

namespace App\Http\Controllers\API;

use App\Models\MobileDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class MobileDeviceController extends BaseController
{
    /**
     * Register or update a mobile device push token.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'push_token' => 'required|string',
            'platform' => 'required|string|in:ios,android',
        ]);

        $user = Auth::user();

        // Rechercher par token uniquement pour permettre le changement de compte
        $device = MobileDevice::updateOrCreate(
            [
                'token' => $validated['push_token'],
            ],
            [
                'user_id' => $user->id,
                'platform' => $validated['platform'],
            ]
        );

        return $this->handleResponse($device, 'Device registered successfully');
    }

    /**
     * Unregister a mobile device push token.
     */
    public function unregister(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'push_token' => 'required|string',
        ]);

        $user = Auth::user();

        $deleted = MobileDevice::where('user_id', $user->id)
            ->where('token', $validated['push_token'])
            ->delete();

        if ($deleted) {
            return $this->handleResponse(null, 'Device unregistered successfully');
        }

        return $this->handleError('Device not found', Response::HTTP_NOT_FOUND);
    }

    /**
     * Get all registered devices for the authenticated user.
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        $devices = $user->mobileDevices;

        return $this->handleResponse($devices);
    }
}
