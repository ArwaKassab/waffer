<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request)
    {
        $product_total = (float) $this->total_product_price;
        $discount_fee = (float) $this->orderDiscounts->sum('discount_fee');
        $total_after_discount = $product_total - $discount_fee;
        $final_total = $total_after_discount + (float) $this->delivery_fee;

        return [
            'order_id' => $this->id,
            'status' => $this->status,
            'order_time' => $this->created_at->format('Y-m-d H:i'),
            'items_count' => $this->items->count(),
            'product_total' => $product_total,
            'discount_fee' => $discount_fee,
            'total_after_discount' => $total_after_discount,
            'delivery_fee' => (float) $this->delivery_fee,
            'final_total' => $final_total,
            'payment_method' => $this->payment_method,
            'notes' => $this->notes,
            'address'=>$this->address_id,
            'area' => [
                'id' => optional($this->area)->id,
                'name' => optional($this->area)->name,
            ],
            'items' => $this->items->map(function ($item) {
                $product = optional($item->product);
                $store = optional($item->store);

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'store_id' => $store->id,
                    'store_name' => $store->name,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'unit_price_after_discount' => (float) $item->unit_price_after_discount,
                    'total_price' => (float) $item->total_price,
                    'total_price_after_discount' => (float) $item->total_price_after_discount,
                    'discount_value' => (float) $item->discount_value,
                    'status' => $item->status,
                ];
            }),
            'discounts' => $this->orderDiscounts->map(function ($discount) {
                return [
                    'discount_id' => optional($discount->discount)->id,
                    'discount_title' => optional($discount->discount)->title,
                    'discount_fee' => (float) $discount->discount_fee,
                ];
            }),
        ];
    }
}
