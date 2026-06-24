<?php

namespace App\Http\Controllers\EnterpriseAdmin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    private function tenantId(): int
    {
        return auth('staff')->user()->tenant_id;
    }

    public function index(): JsonResponse
    {
        $branches = Branch::where('tenant_id', $this->tenantId())
            ->withCount(['queues', 'employees'])
            ->get();

        return response()->json($branches);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'address'       => 'nullable|string',
            'city'          => 'nullable|string',
            'phone'         => 'nullable|string',
            'opening_hours' => 'nullable|array',
            'status'        => 'nullable|in:active,inactive',
        ]);

        $data['tenant_id'] = $this->tenantId();
        $branch = Branch::create($data);

        return response()->json($branch, 201);
    }

    public function show(Branch $branch): JsonResponse
    {
        $this->authorizeTenant($branch);
        $branch->load(['queues', 'employees']);
        return response()->json($branch);
    }

    public function update(Request $request, Branch $branch): JsonResponse
    {
        $this->authorizeTenant($branch);

        $data = $request->validate([
            'name'          => 'sometimes|string|max:255',
            'address'       => 'nullable|string',
            'city'          => 'nullable|string',
            'phone'         => 'nullable|string',
            'opening_hours' => 'nullable|array',
            'status'        => 'nullable|in:active,inactive',
        ]);

        $branch->update($data);

        return response()->json($branch);
    }

    public function destroy(Branch $branch): JsonResponse
    {
        $this->authorizeTenant($branch);
        $branch->delete();
        return response()->json(['message' => 'Branche supprimée.']);
    }

    private function authorizeTenant(Branch $branch): void
    {
        if ($branch->tenant_id !== $this->tenantId()) {
            abort(403);
        }
    }
}
