<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discount extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'title',
        'description',
        'product_id',
        'start_date',
        'end_date',
        'new_price',
        'status',
    ];

    protected $casts = [
        'start_date' => 'datetime:Y-m-d',
        'end_date'   => 'datetime:Y-m-d',
        'new_price'  => 'float',
    ];


    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_discounts', 'discount_id', 'order_id')
            ->withPivot('discount_fee')
            ->withTimestamps();
    }



}
