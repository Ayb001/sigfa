<?php

namespace App\Http\Controllers\EnterpriseAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    private function tenantId(): int
    {
        return auth('staff')->user()->tenant_id;
    }

    public function index(Request $request): JsonResponse
    {
        $query = User::where('tenant_id', $this->tenantId())
            ->where('role', 'employee')
            ->with('branch');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId();

        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => 'required|email|unique:users,email',
            'phone'      => 'nullable|string',
            'password'   => 'required|string|min:8',
            'branch_id'  => 'required|exists:branches,id',
            'status'     => 'nullable|in:active,inactive',
        ]);

        // Ensure the branch belongs to this tenant
        $branch = Branch::where('id', $data['branch_id'])->where('tenant_id', $tenantId)->firstOrFail();

        $employee = User::create([
            ...$data,
            'tenant_id' => $tenantId,
            'role'      => 'employee',
        ]);

        $employee->load('branch');

        return response()->json($employee, 201);
    }

    public function show(User $employee): JsonResponse
    {
        $this->authorizeTenant($employee);
        $employee->load('branch');
        return response()->json($employee);
    }

    public function update(Request $request, User $employee): JsonResponse
    {
        $this->authorizeTenant($employee);
        $tenantId = $this->tenantId();

        $data = $request->validate([
            'first_name' => 'sometimes|string|max:100',
            'last_name'  => 'sometimes|string|max:100',
            'email'      => "sometimes|email|unique:users,email,{$employee->id}",
            'phone'      => 'nullable|string',
            'password'   => 'sometimes|string|min:8',
            'branch_id'  => 'sometimes|exists:branches,id',
            'status'     => 'sometimes|in:active,inactive',
        ]);

        if (isset($data['branch_id'])) {
            Branch::where('id', $data['branch_id'])->where('tenant_id', $tenantId)->firstOrFail();
        }

        $employee->update($data);
        $employee->load('branch');

        return response()->json($employee);
    }

    public function destroy(User $employee): JsonResponse
    {
        $this->authorizeTenant($employee);
        $employee->delete();
        return response()->json(['message' => 'Employé supprimé.']);
    }

    private function authorizeTenant(User $employee): void
    {
        if ($employee->tenant_id !== $this->tenantId() || $employee->role !== 'employee') {
            abort(403);
        }
    }
}
