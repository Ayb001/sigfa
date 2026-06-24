<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\GuichetSession;
use App\Models\Ticket;
use App\Services\GuichetSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuichetSessionController extends Controller
{
    public function __construct(private readonly GuichetSessionService $service) {}

    public function mySession(): JsonResponse
    {
        $session = GuichetSession::where('employee_id', auth('staff')->id())
            ->whereIn('status', ['active', 'paused'])
            ->with(['queue:id,name,prefix', 'branch:id,name', 'currentTicket.client:id,first_name,last_name,phone'])
            ->withCount(['tickets as served_today' => fn ($q) => $q->where('status', 'served')])
            ->latest()
            ->first();

        return response()->json($session);
    }

    public function open(Request $request): JsonResponse
    {
        $request->validate(['queue_id' => 'required|exists:queues,id']);

        $session = $this->service->open(auth('staff')->user(), $request->queue_id);
        $session->load(['queue:id,name,prefix', 'branch:id,name']);

        return response()->json($session, 201);
    }

    public function pause(GuichetSession $session): JsonResponse
    {
        $this->authorizeSession($session);
        return response()->json($this->service->pause($session));
    }

    public function resume(GuichetSession $session): JsonResponse
    {
        $this->authorizeSession($session);
        return response()->json($this->service->resume($session));
    }

    public function close(GuichetSession $session): JsonResponse
    {
        $this->authorizeSession($session);
        return response()->json($this->service->close($session));
    }

    public function callNext(GuichetSession $session): JsonResponse
    {
        $this->authorizeSession($session);

        try {
            $ticket = $this->service->callNext($session);
            return response()->json(['ticket' => $ticket]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['message' => 'Aucun ticket en attente.'], 404);
        }
    }

    public function markServed(GuichetSession $session, Ticket $ticket): JsonResponse
    {
        $this->authorizeSession($session);

        try {
            return response()->json($this->service->markServed($ticket, $session));
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function skip(GuichetSession $session, Ticket $ticket): JsonResponse
    {
        $this->authorizeSession($session);

        try {
            return response()->json($this->service->skip($ticket, $session));
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function sessionHistory(): JsonResponse
    {
        $sessions = GuichetSession::where('employee_id', auth('staff')->id())
            ->with(['queue:id,name,prefix', 'branch:id,name'])
            ->withCount(['tickets as served_count' => fn ($q) => $q->where('status', 'served')])
            ->latest()
            ->paginate(20);

        return response()->json($sessions);
    }

    private function authorizeSession(GuichetSession $session): void
    {
        if ($session->employee_id !== auth('staff')->id()) {
            abort(403);
        }
    }
}
