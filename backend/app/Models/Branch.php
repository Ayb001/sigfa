<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $fillable = [
        'tenant_id', 'name', 'address', 'city', 'phone', 'opening_hours', 'status',
    ];

    protected $casts = [
        'opening_hours' => 'array',
    ];

    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class, 'tenant_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(User::class)->where('role', 'employee');
    }

    public function queues(): HasMany
    {
        return $this->hasMany(Queue::class);
    }

    public function guichetSessions(): HasMany
    {
        return $this->hasMany(GuichetSession::class);
    }
}
