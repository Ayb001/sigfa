<?php

namespace App\Http\Controllers\SSE;

use App\Http\Controllers\Controller;
use App\Models\GuichetSession;
use App\Models\Queue;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QueueStreamController extends Controller
{
    /**
     * Agent SSE stream: current ticket, queue length, session status.
     */
    public function agentStream(Request $request): StreamedResponse
    {
        $employee = auth('staff')->user();

        return response()->stream(function () use ($employee) {
            while (true) {
                if (connection_aborted()) {
                    break;
                }

                $session = GuichetSession::where('employee_id', $employee->id)
                    ->whereIn('status', ['active', 'paused'])
                    ->with(['currentTicket.client:id,first_name,last_name', 'queue:id,name,prefix'])
                    ->withCount(['tickets as served_today' => fn ($q) => $q->where('status', 'served')])
                    ->latest()
                    ->first();

                $queueLength = $session
                    ? Ticket::where('queue_id', $session->queue_id)->where('status', 'waiting')->count()
                    : 0;

                $payload = json_encode([
                    'session'      => $session,
                    'queue_length' => $queueLength,
                    'ts'           => now()->toIso8601String(),
                ]);

                echo "data: {$payload}\n\n";
                ob_flush();
                flush();

                sleep(2);
            }
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }

    /**
     * Admin SSE stream: all active/paused sessions for the tenant.
     */
    public function adminStream(Request $request): StreamedResponse
    {
        $tenantId = auth('staff')->user()->tenant_id;

        return response()->stream(function () use ($tenantId) {
            while (true) {
                if (connection_aborted()) {
                    break;
                }

                $sessions = GuichetSession::where('tenant_id', $tenantId)
                    ->whereIn('status', ['active', 'paused'])
                    ->with([
                        'employee:id,first_name,last_name',
                        'branch:id,name',
                        'queue:id,name,prefix',
                        'currentTicket.client:id,first_name,last_name',
                    ])
                    ->withCount(['tickets as served_today' => fn ($q) => $q->where('status', 'served')])
                    ->get()
                    ->map(fn ($s) => [
                        'id'               => $s->id,
                        'employee'         => $s->employee,
                        'branch'           => $s->branch,
                        'queue'            => $s->queue,
                        'status'           => $s->status,
                        'started_at'       => $s->started_at,
                        'duration_seconds' => $s->duration_seconds,
                        'current_ticket'   => $s->currentTicket,
                        'served_today'     => $s->served_today,
                    ]);

                echo "data: " . json_encode(['sessions' => $sessions, 'ts' => now()->toIso8601String()]) . "\n\n";
                ob_flush();
                flush();

                sleep(2);
            }
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }

    /**
     * Client SSE stream: live position and wait estimate for a specific ticket.
     */
    public function clientTicketStream(Request $request, int $ticketId): StreamedResponse
    {
        $client = auth('client')->user();

        return response()->stream(function () use ($client, $ticketId) {
            while (true) {
                if (connection_aborted()) {
                    break;
                }

                $ticket = Ticket::where('id', $ticketId)
                    ->where('client_id', $client->id)
                    ->with('queue:id,avg_service_time,prefix')
                    ->first();

                if (!$ticket) {
                    echo "event: error\ndata: " . json_encode(['message' => 'Ticket non trouvé.']) . "\n\n";
                    ob_flush();
                    flush();
                    break;
                }

                $waitingAhead = max(0, $ticket->position - 1);

                echo "data: " . json_encode([
                    'status'                 => $ticket->status,
                    'position'               => $ticket->position,
                    'waiting_ahead'          => $waitingAhead,
                    'estimated_wait_minutes' => $waitingAhead * $ticket->queue->avg_service_time,
                    'ts'                     => now()->toIso8601String(),
                ]) . "\n\n";

                ob_flush();
                flush();

                if (in_array($ticket->status, ['served', 'skipped', 'cancelled'])) {
                    break;
                }

                sleep(2);
            }
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }
}
