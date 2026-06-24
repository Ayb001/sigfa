<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enterprise extends Model
{
    protected $fillable = [
        'name', 'sector', 'logo', 'address', 'city',
        'contact_email', 'contact_phone', 'default_language', 'status',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'tenant_id');
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class, 'tenant_id');
    }

    public function queues(): HasMany
    {
        return $this->hasMany(Queue::class, 'tenant_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'tenant_id');
    }

    public function guichetSessions(): HasMany
    {
        return $this->hasMany(GuichetSession::class, 'tenant_id');
    }
}
