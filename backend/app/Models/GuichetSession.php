<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GuichetSession extends Model
{
    protected $fillable = [
        'tenant_id', 'branch_id', 'queue_id', 'employee_id',
        'status', 'started_at', 'paused_at', 'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'paused_at'  => 'datetime',
        'ended_at'   => 'datetime',
    ];

    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class, 'tenant_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(Queue::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function currentTicket(): HasOne
    {
        return $this->hasOne(Ticket::class)->where('status', 'called')->latest('called_at');
    }

    public function getDurationSecondsAttribute(): int
    {
        $start = $this->started_at ?? $this->created_at;
        $end   = $this->ended_at ?? now();
        return (int) $start->diffInSeconds($end);
    }

    public function getAvgIdleTimeAttribute(): float
    {
        return $this->tickets()
            ->whereNotNull('idle_time_seconds')
            ->avg('idle_time_seconds') ?? 0;
    }
}
