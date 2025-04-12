<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AuthenticatedSessionController extends Controller
{
    public function store(LoginRequest $request): JsonResponse
    {
        // Generate Sanctum token for the user
        $user = User::findOrFail($request->input('email'));

        $token = $user->createToken('auth_token')->plainTextToken;
        $request->session()->regenerate();

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }
}
