<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Queue extends Model
{
    protected $table = 'queues';

    protected $fillable = [
        'tenant_id', 'branch_id', 'name', 'prefix',
        'avg_service_time', 'priority_rules', 'opening_hours', 'status',
    ];

    protected $casts = [
        'priority_rules' => 'array',
        'opening_hours'  => 'array',
    ];

    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class, 'tenant_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function guichetSessions(): HasMany
    {
        return $this->hasMany(GuichetSession::class);
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class);
    }

    public function waitingTickets(): HasMany
    {
        return $this->tickets()->where('status', 'waiting')->orderBy('priority', 'desc')->orderBy('created_at');
    }
}
