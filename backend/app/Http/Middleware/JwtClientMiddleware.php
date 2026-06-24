<?php

namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class JwtClientMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $token = JWTAuth::setRequest($request)->parseToken();
            $payload = $token->getPayload()->toArray();
        } catch (JWTException) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (($payload['guard'] ?? '') !== 'client') {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $client = Client::find($payload['sub']);

        if (!$client) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        auth('client')->setUser($client);

        return $next($request);
    }
}
