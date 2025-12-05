<?php

namespace App\Models\Tenant\Items;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Tenant\Items\Items;

class Category extends Model

{
    use HasFactory;

    protected $connection = 'tenant';

    protected $table = 'inv_categories';

    protected $fillable = ['id','name', 'status', 'createdAt', 'updatedAt','deletedAt'];

}
