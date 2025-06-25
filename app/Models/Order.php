<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'area_id',
        'address_id',
        'total_product_price',
        'delivery_fee',
        'total_price',
        'date',
        'time',
        'status',
        'payment_method',
        'notes',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_items')
            ->withPivot('quantity', 'price', 'store_id')->withTimestamps();
    }

    public function discounts()
    {
        return $this->belongsToMany(Discount::class, 'order_discounts', 'order_id', 'discount_id');
    }

    public function offers()
    {
        return $this->belongsToMany(Offer::class, 'order_offers', 'order_id', 'offer_id');
    }
}
