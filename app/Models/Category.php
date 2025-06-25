<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
    ];

    public function stores()
    {
        return $this->belongsToMany(User::class, 'store_category', 'category_id', 'store_id');
    }
}
