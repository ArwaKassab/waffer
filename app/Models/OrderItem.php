<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_id',
        'product_id',
        'store_id',
        'quantity',
        'unit_price',
        'unit_price_after_discount',
        'total_price',
        'total_price_after_discount',
        'discount_value',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }


    public function store()
    {
        return $this->belongsTo(User::class);
    }
}
