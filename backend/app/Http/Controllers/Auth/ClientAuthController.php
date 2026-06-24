<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class ClientAuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'first_name'          => 'required|string|max:100',
            'last_name'           => 'required|string|max:100',
            'email'               => 'required|email|unique:clients,email',
            'phone'               => 'nullable|string|max:20',
            'password'            => 'required|string|min:8|confirmed',
            'language_preference' => 'nullable|in:fr,en',
        ]);

        $client = Client::create([
            'first_name'          => $request->first_name,
            'last_name'           => $request->last_name,
            'email'               => $request->email,
            'phone'               => $request->phone,
            'password'            => $request->password,
            'language_preference' => $request->language_preference ?? 'fr',
        ]);

        $token = JWTAuth::fromUser($client);

        return response()->json([
            'token'      => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'client'     => $this->clientPayload($client),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $client = Client::where('email', $request->email)->first();

        if (!$client || !Hash::check($request->password, $client->password)) {
            throw ValidationException::withMessages([
                'email' => ['Ces identifiants ne correspondent pas à nos enregistrements.'],
            ]);
        }

        if ($client->status !== 'active') {
            return response()->json(['message' => 'Compte désactivé.'], 403);
        }

        // Update FCM token if provided
        if ($request->has('fcm_token')) {
            $client->update(['fcm_token' => $request->fcm_token]);
        }

        $token = JWTAuth::fromUser($client);

        return response()->json([
            'token'      => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'client'     => $this->clientPayload($client),
        ]);
    }

    public function logout(): JsonResponse
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json(['message' => 'Déconnecté avec succès.']);
    }

    public function me(): JsonResponse
    {
        return response()->json($this->clientPayload(auth('client')->user()));
    }

    public function updateLanguage(Request $request): JsonResponse
    {
        $request->validate(['language' => 'required|in:fr,en']);
        $client = auth('client')->user();
        $client->update(['language_preference' => $request->language]);
        return response()->json(['language_preference' => $request->language]);
    }

    public function updateFcmToken(Request $request): JsonResponse
    {
        $request->validate(['fcm_token' => 'required|string']);
        auth('client')->user()->update(['fcm_token' => $request->fcm_token]);
        return response()->json(['message' => 'Token FCM mis à jour.']);
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

    private function clientPayload(Client $client): array
    {
        return [
            'id'                  => $client->id,
            'first_name'          => $client->first_name,
            'last_name'           => $client->last_name,
            'email'               => $client->email,
            'phone'               => $client->phone,
            'language_preference' => $client->language_preference,
        ];
    }
}
