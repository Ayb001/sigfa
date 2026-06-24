<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    protected $fillable = [
        'tenant_id', 'queue_id', 'client_id', 'guichet_session_id',
        'ticket_number', 'status', 'priority', 'position',
        'called_at', 'served_at', 'skipped_at', 'cancelled_at', 'idle_time_seconds',
    ];

    protected $casts = [
        'called_at'    => 'datetime',
        'served_at'    => 'datetime',
        'skipped_at'   => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class, 'tenant_id');
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(Queue::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function guichetSession(): BelongsTo
    {
        return $this->belongsTo(GuichetSession::class);
    }

    public function pushNotifications(): HasMany
    {
        return $this->hasMany(PushNotification::class);
    }

    public function getWaitTimeSecondsAttribute(): ?int
    {
        if (!$this->called_at) {
            return null;
        }
        return (int) $this->created_at->diffInSeconds($this->called_at);
    }

    public function getServiceTimeSecondsAttribute(): ?int
    {
        if (!$this->called_at || !$this->served_at) {
            return null;
        }
        return (int) $this->called_at->diffInSeconds($this->served_at);
    }
}
