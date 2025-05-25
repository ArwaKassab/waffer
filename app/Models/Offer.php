<?php

namespace App\Models;

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Offer extends Model
{
    use SoftDeletes;

    public function products()
    {
        return $this->belongsToMany(Product::class, 'offer_product', 'offer_id', 'product_id');
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_offers', 'offer_id', 'order_id');
    }
}
