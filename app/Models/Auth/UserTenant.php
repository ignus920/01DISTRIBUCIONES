<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTenant extends Model
{
    protected $fillable = [
        'user_id',
        'tenant_id',
        'role',
        'is_active',
        'last_accessed_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_accessed_at' => 'datetime',
    ];

    /**
     * Relación con el usuario.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con el tenant.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Auth::class);
    }

    /**
     * Scope para obtener solo accesos activos.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Actualiza el último acceso al tenant.
     */
    public function touchLastAccessed(): void
    {
        $this->update(['last_accessed_at' => now()]);
    }
}
