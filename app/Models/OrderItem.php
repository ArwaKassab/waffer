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
        'price',
    ];

    // العلاقة مع الطلب
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // العلاقة مع المنتج
    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }


    // العلاقة مع المتجر (المستخدم من نوع store)
    public function store()
    {
        return $this->belongsTo(User::class, 'store_id');
    }
}
