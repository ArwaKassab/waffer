<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConfirmedOrderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'order_id' => $this->resource['order_id'],
            'status' => $this->resource['status'],
            'address_id' => $this->resource['address_id'],
            'product_total' => $this->resource['product_total'],
            'discount_fee' => $this->resource['discount_fee'],
            'total_after_discount' => $this->resource['total_after_discount'],
            'delivery_fee' => $this->resource['delivery_fee'],
            'final_total' => $this->resource['final_total'],
            'items' => $this->resource['items'],
            'message' => $this->resource['message'],
        ];
    }
}
