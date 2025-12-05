<?php

namespace App\Models\Auth;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use App\Models\Central\UsrProfile;
use App\Models\Auth\Tenant;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;


    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'profile_id',
        'avatar',
        'two_factor_enabled',
        'two_factor_type',
        'two_factor_secret',
        'two_factor_failed_attempts',
        'two_factor_locked_until',
        'whatsapp_token',
        'whatsapp_token_expires_at',
        'contact_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'profile_id' => 'integer',
            'two_factor_enabled' => 'boolean',
            'two_factor_failed_attempts' => 'integer',
            'two_factor_locked_until' => 'datetime',
            'whatsapp_token_expires_at' => 'datetime',
        ];
    }

    /**
     * Relación con el perfil de usuario.
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(UsrProfile::class, 'profile_id');
    }

    /**
     * Relación muchos a muchos con tenants.
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'user_tenants')
            ->withPivot('role', 'is_active', 'last_accessed_at')
            ->withTimestamps();
    }

    /**
     * Relación con códigos de autenticación de dos factores.
     */
    public function twoFactorCodes(): HasMany
    {
        return $this->hasMany(TwoFactorCode::class);
    }

    /**
     * Verifica si el usuario tiene 2FA habilitado.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_enabled;
    }

    /**
     * Verifica si el usuario está bloqueado por intentos fallidos de 2FA.
     */
    public function isTwoFactorLocked(): bool
    {
        if (!$this->two_factor_locked_until) {
            return false;
        }

        return Carbon::now()->lessThan($this->two_factor_locked_until);
    }

    /**
     * Resetea los intentos fallidos de 2FA.
     */
    public function resetTwoFactorAttempts(): void
    {
        $this->update([
            'two_factor_failed_attempts' => 0,
            'two_factor_locked_until' => null,
        ]);
    }

    /**
     * Incrementa los intentos fallidos de 2FA.
     */
    public function incrementTwoFactorAttempts(): void
    {
        $this->increment('two_factor_failed_attempts');

        // Bloquear después de 3 intentos fallidos por 15 minutos
        if ($this->two_factor_failed_attempts >= 3) {
            $this->update([
                'two_factor_locked_until' => Carbon::now()->addMinutes(15),
            ]);
        }
    }

    /**
     * Obtener tenants activos del usuario.
     */
    public function activeTenants()
    {
        return $this->tenants()->wherePivot('is_active', true);
    }

    /**
     * Verifica si el usuario tiene acceso a un tenant específico.
     */
    public function hasAccessToTenant(string $tenantId): bool
    {
        return $this->activeTenants()->where('tenants.id', $tenantId)->exists();
    }

    /**
     * Verifica si el usuario es Super Administrador.
     */
    public function isSuperAdmin(): bool
    {
        return $this->profile_id === 1 || $this->profile?->alias === 'super_admin';
    }

    /**
     * Scope para obtener solo Super Administradores.
     */
    public function scopeSuperAdmins($query)
    {
        return $query->whereHas('profile', function ($q) {
            $q->where('alias', 'super_admin');
        });
    }

    /**
     * Scope para obtener usuarios que NO son Super Administradores.
     */
    public function scopeNonSuperAdmins($query)
    {
        return $query->whereDoesntHave('profile', function ($q) {
            $q->where('alias', 'super_admin');
        });
    }

    /**
     * Obtener la URL del avatar del usuario.
     */
    public function getAvatarUrl(): string
    {
        if ($this->avatar && file_exists(storage_path('app/public/' . $this->avatar))) {
            return asset('storage/' . $this->avatar);
        }

        // Avatar por defecto usando iniciales
        return $this->getDefaultAvatarUrl();
    }

    /**
     * Obtener URL del avatar por defecto con iniciales.
     */
    public function getDefaultAvatarUrl(): string
    {
        $initials = $this->getInitials();
        return "https://ui-avatars.com/api/?name=" . urlencode($initials) . "&color=7F9CF5&background=EBF4FF&size=128";
    }

    /**
     * Obtener las iniciales del usuario.
     */
    public function getInitials(): string
    {
        $nameParts = explode(' ', trim($this->name));
        $initials = '';

        foreach ($nameParts as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
        }

        return substr($initials, 0, 2); // Máximo 2 iniciales
    }

    /**
     * Relación con el contacto (vnt_contacts).
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Central\VntContact::class, 'contact_id');
    }

    /**
     * Verifica si el usuario tiene avatar personalizado.
     */
    public function hasCustomAvatar(): bool
    {
        return !empty($this->avatar) && file_exists(storage_path('app/public/' . $this->avatar));
    }
}
