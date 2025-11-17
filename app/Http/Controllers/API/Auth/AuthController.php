<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Models\MobileDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'push_token' => 'nullable|string',
            'platform' => 'nullable|string|in:ios,android',
        ]);

        if (! Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        if (isset($credentials['push_token']) && isset($credentials['platform'])) {
            // Rechercher par token uniquement pour permettre le changement de compte
            MobileDevice::updateOrCreate(
                [
                    'token' => $credentials['push_token'],
                ],
                [
                    'user_id' => $user->id,
                    'platform' => $credentials['platform'],
                ]
            );
        }

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function verify(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return response()->json([
            'message' => 'User is authenticated',
            'user' => $user,
        ]);
    }
}
