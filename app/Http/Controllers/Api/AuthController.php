<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = \App\Models\User::where('email', $credentials['email'])->first();
        if (! $user || ! Hash::check($credentials['password'], $user->password) || ! $user->actif) {
            throw ValidationException::withMessages(['email' => ['Identifiants invalides.']]);
        }

        $user->update(['derniere_connexion' => now()]);
        $token = $user->createToken($credentials['device_name'] ?? 'mobile')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user->load('role')]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json($request->user()->load('role', 'departement', 'site'));
    }

    public function notifications(Request $request): JsonResponse
    {
        return response()->json($request->user()->notifications()->latest()->paginate(20));
    }

    public function markNotificationRead(Request $request, string $notification): JsonResponse
    {
        $item = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();

        return response()->json(['message' => 'Notification marquée comme lue.']);
    }
}
