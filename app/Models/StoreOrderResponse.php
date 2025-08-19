<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreOrderResponse extends Model
{
    protected $fillable = ['order_id', 'store_id', 'status', 'reason', 'responded_at'];

    public function store()
    {
        return $this->belongsTo(User::class, 'store_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
