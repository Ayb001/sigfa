<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    private string $serverKey;
    private const FCM_URL = 'https://fcm.googleapis.com/fcm/send';

    public function __construct()
    {
        $this->serverKey = config('services.fcm.server_key', '');
    }

    /** Send to a single device token. Silently ignores if key not configured. */
    public function send(string $token, string $title, string $body, array $data = []): void
    {
        if (empty($this->serverKey) || $this->serverKey === 'your_fcm_server_key_here') {
            return;
        }

        try {
            Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type'  => 'application/json',
            ])->timeout(5)->post(self::FCM_URL, [
                'to'           => $token,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                    'sound' => 'default',
                ],
                'data'         => $data,
                'priority'     => 'high',
            ]);
        } catch (\Throwable $e) {
            Log::warning('FCM send failed: ' . $e->getMessage());
        }
    }

    /** Send "your turn" notification to the client whose ticket was just called. */
    public function notifyTicketCalled(string $token, string $ticketNumber, string $queueName): void
    {
        $this->send(
            $token,
            '📢 C\'est votre tour !',
            "Ticket {$ticketNumber} — Veuillez vous présenter au guichet ({$queueName}).",
            ['type' => 'ticket_called', 'ticket_number' => $ticketNumber]
        );
    }

    /** Send "you are next" warning (one position ahead). */
    public function notifyTicketApproaching(string $token, string $ticketNumber): void
    {
        $this->send(
            $token,
            '⏰ Préparez-vous !',
            "Ticket {$ticketNumber} — Vous êtes le prochain en file.",
            ['type' => 'ticket_approaching', 'ticket_number' => $ticketNumber]
        );
    }
}
