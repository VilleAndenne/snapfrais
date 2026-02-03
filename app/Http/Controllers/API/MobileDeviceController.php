<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MobileDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MobileDeviceController extends Controller
{
    /**
     * Register or update a mobile device push token.
     */
    public function register(Request $request)
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

        return response()->json([
            'message' => 'Device registered successfully',
            'device' => $device,
        ]);
    }

    /**
     * Unregister a mobile device push token.
     */
    public function unregister(Request $request)
    {
        $validated = $request->validate([
            'push_token' => 'required|string',
        ]);

        $user = Auth::user();

        $deleted = MobileDevice::where('user_id', $user->id)
            ->where('token', $validated['push_token'])
            ->delete();

        if ($deleted) {
            return response()->json([
                'message' => 'Device unregistered successfully',
            ]);
        }

        return response()->json([
            'message' => 'Device not found',
        ], 404);
    }

    /**
     * Get all registered devices for the authenticated user.
     */
    public function index()
    {
        $user = Auth::user();

        $devices = $user->mobileDevices;

        return response()->json([
            'devices' => $devices,
        ]);
    }
}
