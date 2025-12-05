<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UsrPermissionProfile extends Model
{
    use SoftDeletes;

    protected $connection = 'central';
    protected $table = 'usr_permissions_profiles';

    protected $fillable = [
        'creater',
        'deleter',
        'editer',
        'show',
        'profileId',
        'permissionId',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'creater' => 'boolean',
            'deleter' => 'boolean',
            'editer' => 'boolean',
            'show' => 'boolean',
            'profileId' => 'integer',
            'permissionId' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // Relación con perfil
    public function profile()
    {
        return $this->belongsTo(UsrProfile::class, 'profileId');
    }

    // Relación con permiso
    public function permission()
    {
        return $this->belongsTo(UsrPermission::class, 'permissionId');
    }

    // Scopes útiles
    public function scopeByProfile($query, $profileId)
    {
        return $query->where('profileId', $profileId);
    }

    public function scopeByPermission($query, $permissionId)
    {
        return $query->where('permissionId', $permissionId);
    }

    public function scopeWithAccess($query, $accessType)
    {
        return $query->where($accessType, 1);
    }

    // Métodos helper para verificar permisos
    public function canCreate(): bool
    {
        return $this->creater === true;
    }

    public function canDelete(): bool
    {
        return $this->deleter === true;
    }

    public function canEdit(): bool
    {
        return $this->editer === true;
    }

    public function canShow(): bool
    {
        return $this->show === true;
    }
}