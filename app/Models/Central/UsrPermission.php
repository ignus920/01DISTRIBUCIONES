<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UsrPermission extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'usr_permissions';

    protected $fillable = [
        'name',
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

    // Relación con perfiles a través de la tabla pivot
    public function profiles()
    {
        return $this->belongsToMany(UsrProfile::class, 'usr_permissions_profiles', 'permissionId', 'profileId')
            ->withPivot(['creater', 'deleter', 'editer', 'show', 'created_at', 'updated_at', 'deleted_at'])
            ->withTimestamps();
    }

    // Scopes útiles
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}