<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class StaffAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Ces identifiants ne correspondent pas à nos enregistrements.'],
            ]);
        }

        if ($user->status !== 'active') {
            return response()->json(['message' => 'Compte désactivé.'], 403);
        }

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'token'      => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user'       => [
                'id'                  => $user->id,
                'first_name'          => $user->first_name,
                'last_name'           => $user->last_name,
                'email'               => $user->email,
                'role'                => $user->role,
                'tenant_id'           => $user->tenant_id,
                'branch_id'           => $user->branch_id,
                'language_preference' => $user->language_preference,
            ],
        ]);
    }

    public function logout(): JsonResponse
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json(['message' => 'Déconnecté avec succès.']);
    }

    public function me(): JsonResponse
    {
        $user = auth('staff')->user();
        return response()->json([
            'id'                  => $user->id,
            'first_name'          => $user->first_name,
            'last_name'           => $user->last_name,
            'email'               => $user->email,
            'role'                => $user->role,
            'tenant_id'           => $user->tenant_id,
            'branch_id'           => $user->branch_id,
            'language_preference' => $user->language_preference,
            'enterprise'          => $user->enterprise ? [
                'id'   => $user->enterprise->id,
                'name' => $user->enterprise->name,
                'logo' => $user->enterprise->logo,
            ] : null,
        ]);
    }

    public function updateLanguage(Request $request): JsonResponse
    {
        $request->validate(['language' => 'required|in:fr,en']);
        $user = auth('staff')->user();
        $user->update(['language_preference' => $request->language]);
        return response()->json(['language_preference' => $request->language]);
    }

    public function refresh(): JsonResponse
    {
        $token = JWTAuth::refresh(JWTAuth::getToken());
        return response()->json([
            'token'      => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ]);
    }
}
