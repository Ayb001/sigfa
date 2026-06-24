<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class JwtStaffMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (JWTException) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $claims = JWTAuth::getPayload()->toArray();

        if (($claims['guard'] ?? '') !== 'staff') {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (!empty($roles) && !in_array($user->role, $roles)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        auth('staff')->setUser($user);

        return $next($request);
    }
}
