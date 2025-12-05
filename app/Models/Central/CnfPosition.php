<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CnfPosition extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'central';
    protected $table = 'cnf_positions';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'integer';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'status',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * The model's default attribute values.
     *
     * @var array
     */
    protected $attributes = [
        'status' => 1,
    ];

    /**
     * Scope a query to only include active positions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope a query to only include inactive positions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 0);
    }

    /**
     * Check if the position is active.
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->status === 1;
    }

    /**
     * Check if the position is inactive.
     *
     * @return bool
     */
    public function isInactive()
    {
        return $this->status === 0;
    }

    // --- Relaciones ---

    /**
     * Relación con los contactos que tienen esta posición
     */
    public function contacts()
    {
        return $this->hasMany(VntContact::class, 'positionId');
    }

    /**
     * Contactos activos con esta posición
     */
    public function activeContacts()
    {
        return $this->hasMany(VntContact::class, 'positionId')->where('status', 1);
    }
}
