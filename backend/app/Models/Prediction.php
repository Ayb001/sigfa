<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prediction extends Model
{
    protected $fillable = [
        'tenant_id', 'queue_id', 'day_of_week', 'hour_of_day',
        'predicted_wait_minutes', 'predicted_volume', 'is_peak', 'computed_at',
    ];

    protected $casts = [
        'is_peak'      => 'boolean',
        'computed_at'  => 'datetime',
    ];

    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class, 'tenant_id');
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(Queue::class);
    }
}
