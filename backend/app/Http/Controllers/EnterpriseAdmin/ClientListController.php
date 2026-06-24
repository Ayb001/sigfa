<?php

namespace App\Http\Controllers\EnterpriseAdmin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientListController extends Controller
{
    private function tenantId(): int
    {
        return auth('staff')->user()->tenant_id;
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId();

        // Clients who have ever taken a ticket at this enterprise
        $query = Client::whereHas('tickets', fn ($q) => $q->where('tenant_id', $tenantId));

        if ($request->filled('search')) {
            $query->where(fn ($q) => $q
                ->where('first_name', 'like', "%{$request->search}%")
                ->orWhere('last_name',  'like', "%{$request->search}%")
                ->orWhere('email',      'like', "%{$request->search}%")
                ->orWhere('phone',      'like', "%{$request->search}%")
            );
        }

        $clients = $query
            ->withCount(['tickets as visit_count' => fn ($q) => $q->where('tenant_id', $tenantId)])
            ->withMax(['tickets as last_visit'    => fn ($q) => $q->where('tenant_id', $tenantId)], 'created_at')
            ->paginate(30);

        return response()->json($clients);
    }

    public function show(int $clientId): JsonResponse
    {
        $tenantId = $this->tenantId();

        $client = Client::findOrFail($clientId);

        $tickets = Ticket::where('tenant_id', $tenantId)
            ->where('client_id', $clientId)
            ->with('queue:id,name,prefix')
            ->latest()
            ->paginate(20);

        return response()->json(['client' => $client, 'tickets' => $tickets]);
    }
}
