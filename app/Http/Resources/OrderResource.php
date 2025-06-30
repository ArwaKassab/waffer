<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'order_id' => $this->id,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'total_price' => $this->total_price,
            'items' => $this->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                ];
            }),
            'created_at' => $this->created_at->toDateString(),
            'created_time' => $this->created_at->toTimeString(),
        ];
    }
}
