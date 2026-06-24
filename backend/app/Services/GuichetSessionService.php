<?php

namespace App\Services;

use App\Models\GuichetSession;
use App\Models\Queue;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GuichetSessionService
{
    public function open(User $employee, int $queueId): GuichetSession
    {
        $queue = Queue::where('id', $queueId)
            ->where('tenant_id', $employee->tenant_id)
            ->where('branch_id', $employee->branch_id)
            ->where('status', 'active')
            ->firstOrFail();

        // Only one active session per employee
        GuichetSession::where('employee_id', $employee->id)
            ->where('status', 'active')
            ->update(['status' => 'ended', 'ended_at' => now()]);

        return GuichetSession::create([
            'tenant_id'   => $employee->tenant_id,
            'branch_id'   => $employee->branch_id,
            'queue_id'    => $queue->id,
            'employee_id' => $employee->id,
            'status'      => 'active',
            'started_at'  => now(),
        ]);
    }

    public function pause(GuichetSession $session): GuichetSession
    {
        $session->update(['status' => 'paused', 'paused_at' => now()]);
        return $session->fresh();
    }

    public function resume(GuichetSession $session): GuichetSession
    {
        $session->update(['status' => 'active', 'paused_at' => null]);
        return $session->fresh();
    }

    public function close(GuichetSession $session): GuichetSession
    {
        // Cancel any ticket still in "called" state for this session
        Ticket::where('guichet_session_id', $session->id)
            ->where('status', 'called')
            ->update(['status' => 'waiting', 'called_at' => null, 'guichet_session_id' => null]);

        $session->update(['status' => 'ended', 'ended_at' => now()]);
        return $session->fresh();
    }

    public function callNext(GuichetSession $session): Ticket
    {
        if ($session->status !== 'active') {
            throw new \RuntimeException('La session est en pause ou terminée.');
        }

        // Compute idle time: now - last served ticket's served_at
        $lastServed = Ticket::where('guichet_session_id', $session->id)
            ->where('status', 'served')
            ->latest('served_at')
            ->first();

        $idleSeconds = $lastServed ? (int) $lastServed->served_at->diffInSeconds(now()) : null;

        // Get next waiting ticket: priority first, then FIFO
        $next = Ticket::where('queue_id', $session->queue_id)
            ->where('tenant_id', $session->tenant_id)
            ->where('status', 'waiting')
            ->orderByRaw("FIELD(priority, 'priority', 'normal')")
            ->orderBy('created_at')
            ->lockForUpdate()
            ->first();

        if (!$next) {
            throw new ModelNotFoundException('Aucun ticket en attente.');
        }

        // Update positions of remaining tickets
        Ticket::where('queue_id', $session->queue_id)
            ->where('tenant_id', $session->tenant_id)
            ->where('status', 'waiting')
            ->where('id', '!=', $next->id)
            ->orderBy('created_at')
            ->get()
            ->each(fn ($t, $i) => $t->update(['position' => $i + 1]));

        $next->update([
            'status'             => 'called',
            'called_at'          => now(),
            'guichet_session_id' => $session->id,
            'idle_time_seconds'  => $idleSeconds,
        ]);

        return $next->load('client');
    }

    public function markServed(Ticket $ticket, GuichetSession $session): Ticket
    {
        $this->authorizeTicket($ticket, $session);
        $ticket->update(['status' => 'served', 'served_at' => now()]);
        return $ticket->fresh();
    }

    public function skip(Ticket $ticket, GuichetSession $session): Ticket
    {
        $this->authorizeTicket($ticket, $session);
        $ticket->update(['status' => 'skipped', 'skipped_at' => now()]);
        return $ticket->fresh();
    }

    private function authorizeTicket(Ticket $ticket, GuichetSession $session): void
    {
        if ($ticket->guichet_session_id !== $session->id || $ticket->status !== 'called') {
            throw new \RuntimeException('Ticket invalide pour cette session.');
        }
    }
}
