<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    use SoftDeletes;
    protected $fillable = ['name', 'image'];


    protected $appends = ['image'];

    public function stores()
    {
        return $this->belongsToMany(User::class, 'store_category', 'category_id', 'store_id');
    }
    public function getImageAttribute($value)
    {

        return $value ? Storage::url($value) : null;
    }
}
