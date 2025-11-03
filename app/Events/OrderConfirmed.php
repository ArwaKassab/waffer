<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderConfirmed
{
    use Dispatchable, SerializesModels;

    public function __construct(public Order $order) {}
}


//namespace App\Events;
//
//use Illuminate\Foundation\Events\Dispatchable;
//use Illuminate\Queue\SerializesModels;
//use App\Models\Order;
//
//class NewOrderCreated
//{
//    use Dispatchable, SerializesModels;
//
//    public function __construct(
//        public Order $order,
//        public int   $storeUserId,   // مين لازم ينبلغ
//    )
//    {
//    }
//}
