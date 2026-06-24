<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Queue;
use App\Models\Ticket;

class TicketService
{
    public function take(Client $client, int $queueId, string $priority = 'normal'): Ticket
    {
        $queue = Queue::where('id', $queueId)->where('status', 'active')->firstOrFail();

        // A client can only hold one active ticket per queue at a time
        $existing = Ticket::where('client_id', $client->id)
            ->where('queue_id', $queueId)
            ->whereIn('status', ['waiting', 'called'])
            ->first();

        if ($existing) {
            throw new \RuntimeException('Vous avez déjà un ticket actif dans cette file d\'attente.');
        }

        $position  = Ticket::where('queue_id', $queueId)->where('status', 'waiting')->count() + 1;
        $number    = $this->generateTicketNumber($queue, $position);

        $ticket = Ticket::create([
            'tenant_id'     => $queue->tenant_id,
            'queue_id'      => $queueId,
            'client_id'     => $client->id,
            'ticket_number' => $number,
            'status'        => 'waiting',
            'priority'      => $priority,
            'position'      => $position,
        ]);

        return $ticket->load(['queue.branch', 'queue.enterprise']);
    }

    public function cancel(Ticket $ticket, Client $client): Ticket
    {
        if ($ticket->client_id !== $client->id) {
            throw new \RuntimeException('Ce ticket ne vous appartient pas.');
        }

        if (!in_array($ticket->status, ['waiting'])) {
            throw new \RuntimeException('Ce ticket ne peut plus être annulé.');
        }

        $ticket->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        // Recompute positions for remaining tickets
        Ticket::where('queue_id', $ticket->queue_id)
            ->where('status', 'waiting')
            ->orderBy('created_at')
            ->get()
            ->each(fn ($t, $i) => $t->update(['position' => $i + 1]));

        return $ticket->fresh();
    }

    public function liveStatus(Ticket $ticket, Client $client): array
    {
        if ($ticket->client_id !== $client->id) {
            throw new \RuntimeException('Ce ticket ne vous appartient pas.');
        }

        $ticket->load('queue');
        $waitingAhead = $ticket->position > 0 ? $ticket->position - 1 : 0;

        return [
            'ticket'                  => $ticket,
            'waiting_ahead'           => $waitingAhead,
            'estimated_wait_minutes'  => $waitingAhead * $ticket->queue->avg_service_time,
            'queue_length'            => Ticket::where('queue_id', $ticket->queue_id)->where('status', 'waiting')->count(),
        ];
    }

    private function generateTicketNumber(Queue $queue, int $position): string
    {
        $date    = now()->format('ymd');
        $count   = Ticket::where('queue_id', $queue->id)->whereDate('created_at', today())->count() + 1;
        return $queue->prefix . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}
