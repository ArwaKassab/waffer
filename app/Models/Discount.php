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
        'new_price',
        'start_date',
        'end_date',
        'status',
    ];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_discounts', 'discount_id', 'order_id');
    }
}
