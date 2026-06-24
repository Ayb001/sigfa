<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Enterprise;
use App\Models\Queue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EnterpriseDirectoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Enterprise::where('status', 'active');

        if ($request->filled('search')) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('city', 'like', "%{$request->search}%")
            );
        }

        if ($request->filled('sector')) {
            $query->where('sector', $request->sector);
        }

        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        $enterprises = $query->select(['id', 'name', 'sector', 'logo', 'city', 'contact_phone', 'default_language'])
            ->withCount(['branches', 'queues' => fn ($q) => $q->where('status', 'active')])
            ->paginate(20);

        $enterprises->getCollection()->transform(function ($e) {
            $e->logo_url = $e->logo ? Storage::url($e->logo) : null;
            return $e;
        });

        return response()->json($enterprises);
    }

    public function show(Enterprise $enterprise): JsonResponse
    {
        if ($enterprise->status !== 'active') {
            abort(404);
        }

        $enterprise->load([
            'branches' => fn ($q) => $q->where('status', 'active')->with([
                'queues' => fn ($q2) => $q2->where('status', 'active')
                    ->withCount(['tickets as waiting_count' => fn ($q3) => $q3->where('status', 'waiting')]),
            ]),
        ]);

        $enterprise->logo_url = $enterprise->logo ? Storage::url($enterprise->logo) : null;

        return response()->json($enterprise);
    }

    public function branches(Enterprise $enterprise): JsonResponse
    {
        if ($enterprise->status !== 'active') {
            abort(404);
        }

        $branches = Branch::where('tenant_id', $enterprise->id)
            ->where('status', 'active')
            ->with(['queues' => fn ($q) => $q->where('status', 'active')
                ->withCount(['tickets as waiting_count' => fn ($q2) => $q2->where('status', 'waiting')])])
            ->get();

        return response()->json($branches);
    }

    public function queues(Enterprise $enterprise, Branch $branch): JsonResponse
    {
        if ($branch->tenant_id !== $enterprise->id) {
            abort(404);
        }

        $queues = Queue::where('branch_id', $branch->id)
            ->where('tenant_id', $enterprise->id)
            ->where('status', 'active')
            ->withCount(['tickets as waiting_count' => fn ($q) => $q->where('status', 'waiting')])
            ->get()
            ->map(function ($queue) {
                $queue->estimated_wait_minutes = $queue->waiting_count * $queue->avg_service_time;
                return $queue;
            });

        return response()->json($queues);
    }
}
