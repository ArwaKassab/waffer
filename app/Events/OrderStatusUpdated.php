<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;

class OrderStatusUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public \App\Models\Order $order,
        public int $customerUserId,
    ) {}
}


