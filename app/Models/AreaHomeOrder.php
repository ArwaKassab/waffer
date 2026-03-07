<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AreaHomeOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'area_id', 'entity_type', 'entity_id', 'sort_order', 'is_active'
    ];
}
