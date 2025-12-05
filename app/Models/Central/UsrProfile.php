<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UsrProfile extends Model
{
    use SoftDeletes;

    protected $connection = 'central';
    protected $table = 'usr_profiles';

    protected $fillable = [
        'name',
        'alias',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'status' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // Relación con usuarios
    public function users()
    {
        return $this->hasMany(\App\Models\Auth\User::class, 'profile_id');
    }

    // Relación con permisos a través de la tabla pivot
    public function permissions()
    {
        return $this->belongsToMany(UsrPermission::class, 'usr_permissions_profiles', 'profileId', 'permissionId')
            ->withPivot(['creater', 'deleter', 'editer', 'show', 'created_at', 'updated_at', 'deleted_at'])
            ->withTimestamps();
    }

    // Relación directa con los permisos asignados
    public function permissionProfiles()
    {
        return $this->hasMany(UsrPermissionProfile::class, 'profileId');
    }

    // Scopes útiles
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeBySuperAdmin($query)
    {
        return $query->where('alias', 'super_admin');
    }
}
