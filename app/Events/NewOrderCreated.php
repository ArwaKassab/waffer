<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewOrderCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * نجبر الإيفينت يشتغل بعد ما تكمّل الـ transaction
     */
    public bool $afterCommit = true;

    public Order $order;

    public function __construct(Order $order)
    {

        $this->order = $order;
    }
}
