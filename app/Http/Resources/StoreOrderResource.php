<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StoreOrderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'order_id' => $this->id,
            'items_count' => $this->items->count(),
            'order_time' => $this->created_at->format('Y-m-d H:i'),
            'items' => $this->items->map(function ($item) {
                $product = $item->product;

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'image' => $product->image,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'unit_price_with_discount' => $item->unit_price_after_discount,
                    'discount_value' => $item->discount_value,
                    'total_price' => $item->total_price,
                    'total_price_after_discount' => $item->total_price_after_discount,
                ];
            }),
            'store_total_invoice' => $this->items->sum('total_price_after_discount'),
        ];
    }
}
