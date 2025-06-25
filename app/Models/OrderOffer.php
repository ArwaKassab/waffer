<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderOffer extends Model
{
    use SoftDeletes;

    protected $table = 'order_offers';

    protected $fillable = [
        'order_id',
        'offer_id',
        'offer_fee',
    ];

    // العلاقة مع الطلب (Order)
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // العلاقة مع العرض (Offer)
    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }
}
