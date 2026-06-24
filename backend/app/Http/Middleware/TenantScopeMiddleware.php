<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Ensures non-superadmin staff can only access their own tenant's data.
 * Call after JwtStaffMiddleware.
 */
class TenantScopeMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth('staff')->user();

        if ($user && !$user->isSuperAdmin() && !$user->tenant_id) {
            return response()->json(['message' => 'Tenant not assigned'], 403);
        }

        return $next($request);
    }
}
