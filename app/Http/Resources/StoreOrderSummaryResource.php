<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StoreOrderSummaryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'order_id' => $this->id,
            'items_count' => $this->items_count ?? 0,
            'order_time' => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}
