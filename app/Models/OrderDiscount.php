<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderDiscount extends Model
{
    use SoftDeletes;

    protected $table = 'order_discounts';

    protected $fillable = [
        'order_id',
        'discount_id',
        'discount_fee',
    ];

    // العلاقة مع الطلب (Order)
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // العلاقة مع الخصم (Discount)
    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }
}
