<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::with('enterprise')->whereIn('role', ['enterprise_admin', 'employee']);

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        return response()->json($query->paginate(30));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_id'  => 'required|exists:enterprises,id',
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => 'required|email|unique:users,email',
            'phone'      => 'nullable|string',
            'password'   => 'required|string|min:8',
            'role'       => 'required|in:enterprise_admin,employee',
        ]);

        $user = User::create($data);

        return response()->json($user, 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'first_name' => 'sometimes|string|max:100',
            'last_name'  => 'sometimes|string|max:100',
            'email'      => "sometimes|email|unique:users,email,{$user->id}",
            'phone'      => 'nullable|string',
            'status'     => 'sometimes|in:active,inactive',
            'role'       => 'sometimes|in:enterprise_admin,employee',
        ]);

        $user->update($data);

        return response()->json($user);
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->isSuperAdmin()) {
            return response()->json(['message' => 'Impossible de supprimer un super-administrateur.'], 403);
        }
        $user->delete();
        return response()->json(['message' => 'Utilisateur supprimé.']);
    }
}
