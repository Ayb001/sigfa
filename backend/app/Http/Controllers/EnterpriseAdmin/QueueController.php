<?php

namespace App\Http\Controllers\EnterpriseAdmin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Queue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    private function tenantId(): int
    {
        return auth('staff')->user()->tenant_id;
    }

    public function index(Request $request): JsonResponse
    {
        $query = Queue::where('tenant_id', $this->tenantId())->with('branch');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        return response()->json($query->withCount(['tickets as waiting_count' => fn ($q) => $q->where('status', 'waiting')])->get());
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId();

        $data = $request->validate([
            'branch_id'        => 'required|exists:branches,id',
            'name'             => 'required|string|max:255',
            'prefix'           => 'required|string|max:5',
            'avg_service_time' => 'required|integer|min:1',
            'priority_rules'   => 'nullable|array',
            'opening_hours'    => 'nullable|array',
            'status'           => 'nullable|in:active,inactive',
        ]);

        Branch::where('id', $data['branch_id'])->where('tenant_id', $tenantId)->firstOrFail();

        $queue = Queue::create([...$data, 'tenant_id' => $tenantId]);
        $queue->load('branch');

        return response()->json($queue, 201);
    }

    public function show(Queue $queue): JsonResponse
    {
        $this->authorizeTenant($queue);
        $queue->load(['branch', 'waitingTickets.client']);
        return response()->json($queue);
    }

    public function update(Request $request, Queue $queue): JsonResponse
    {
        $this->authorizeTenant($queue);

        $data = $request->validate([
            'name'             => 'sometimes|string|max:255',
            'prefix'           => 'sometimes|string|max:5',
            'avg_service_time' => 'sometimes|integer|min:1',
            'priority_rules'   => 'nullable|array',
            'opening_hours'    => 'nullable|array',
            'status'           => 'sometimes|in:active,inactive',
        ]);

        $queue->update($data);

        return response()->json($queue);
    }

    public function destroy(Queue $queue): JsonResponse
    {
        $this->authorizeTenant($queue);
        $queue->delete();
        return response()->json(['message' => 'File d\'attente supprimée.']);
    }

    private function authorizeTenant(Queue $queue): void
    {
        if ($queue->tenant_id !== $this->tenantId()) {
            abort(403);
        }
    }
}
