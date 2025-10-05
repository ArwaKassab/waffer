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
        'discount_fee',
        'totalAfterDiscount',
        'total_price',
        'date',
        'time',
        'status',
        'payment_method',
        'notes',
        'store_total_invoice',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }


    public function orderDiscounts()
    {
        return $this->hasMany(OrderDiscount::class);
    }

    public function offers()
    {
        return $this->belongsToMany(Offer::class, 'order_offers', 'order_id', 'offer_id');
    }

    public function storeOrderResponses()
    {
        return $this->hasMany(StoreOrderResponse::class, 'order_id');
    }

    public function address()
    {
        return $this->belongsTo(Address::class, 'address_id')->withTrashed();
    }

}
