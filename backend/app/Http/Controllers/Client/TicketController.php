<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(private readonly TicketService $service) {}

    public function take(Request $request): JsonResponse
    {
        $request->validate([
            'queue_id' => 'required|exists:queues,id',
            'priority' => 'nullable|in:normal,priority',
        ]);

        try {
            $ticket = $this->service->take(
                auth('client')->user(),
                $request->queue_id,
                $request->priority ?? 'normal'
            );
            return response()->json($ticket, 201);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function myTickets(): JsonResponse
    {
        $tickets = Ticket::where('client_id', auth('client')->id())
            ->with(['queue:id,name,prefix,avg_service_time', 'queue.branch:id,name', 'queue.enterprise:id,name,logo'])
            ->latest()
            ->paginate(20);

        return response()->json($tickets);
    }

    public function show(Ticket $ticket): JsonResponse
    {
        $client = auth('client')->user();

        if ($ticket->client_id !== $client->id) {
            abort(403);
        }

        try {
            return response()->json($this->service->liveStatus($ticket, $client));
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    public function cancel(Ticket $ticket): JsonResponse
    {
        try {
            return response()->json($this->service->cancel($ticket, auth('client')->user()));
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function activeTickets(): JsonResponse
    {
        $tickets = Ticket::where('client_id', auth('client')->id())
            ->whereIn('status', ['waiting', 'called'])
            ->with(['queue:id,name,prefix,avg_service_time', 'queue.branch:id,name', 'queue.enterprise:id,name,logo'])
            ->get()
            ->map(function ($ticket) {
                $waitingAhead = max(0, $ticket->position - 1);
                $ticket->waiting_ahead            = $waitingAhead;
                $ticket->estimated_wait_minutes   = $waitingAhead * $ticket->queue->avg_service_time;
                return $ticket;
            });

        return response()->json($tickets);
    }
}
