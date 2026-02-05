<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ad extends Model
{
    use SoftDeletes;

    protected $fillable = ['image'];

    public function areas()
    {
        return $this->belongsToMany(
            Area::class,
            'area_ad',
            'ad_id',
            'area_id'
        )->withTimestamps();
    }

}
