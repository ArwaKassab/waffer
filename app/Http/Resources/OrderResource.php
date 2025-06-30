<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request)
    {
        $product_total = (float) $this->total_product_price;
        $discount_fee = $this->orderDiscounts->sum('discount_fee');
        $total_after_discount = $product_total - $discount_fee;
        $final_total = $total_after_discount + (float) $this->delivery_fee;

        return [
            'order_id' => $this->id,
            'status' => $this->status,
            'address_id' => $this->address_id,
            'product_total' => $product_total,
            'discount_fee' => $discount_fee,
            'total_after_discount' => $total_after_discount,
            'delivery_fee' => $this->delivery_fee,
            'final_total' => $final_total,
            'payment_method' => $this->payment_method,
            'notes' => $this->notes,
            'date' => $this->date,
            'time' => $this->time,
            'area' => [
                'id' => optional($this->area)->id,
                'name' => optional($this->area)->name,
            ],
            'items' => $this->items->map(function ($item) {
                return [
                    'product_id' => optional($item->product)->id,
                    'product_name' => optional($item->product)->name,
                    'store_id' => optional($item->store)->id,
                    'store_name' => optional($item->store)->name,
                    'quantity' => $item->quantity,
                    'unit_price' => optional($item->product)->price,
                    'total_price' => $item->price,
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

