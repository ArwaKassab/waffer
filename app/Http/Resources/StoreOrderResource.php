<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StoreOrderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'order_id'      => $this->id,
            'items_count'   => $this->items->count(),

            // ✅ أحسن نستخدم optional لتفادي الـ null
            'order_time'    => optional($this->created_at)->format('Y-m-d H:i'),

            'has_rejected_items' => (bool) ($this->has_rejected_items ?? false),

            'reject_reason' => ($this->has_rejected_items ?? false)
                ? ($this->store_reject_reason ?? null)
                : null,

            'items' => $this->items->map(function ($item) {
                $product = $item->product;

                return [
                    'product_id'                => $product->id,
                    'product_name'              => $product->name,
                    'image'                     => $product->image_url,
                    'quantity'                  => (int) $item->quantity,
                    'unit_price'                => (float) $item->unit_price,
                    'unit_price_with_discount'  => (float) $item->unit_price_after_discount,
                    'discount_value'            => (float) $item->discount_value,
                    'total_price'               => (float) $item->total_price,
                    'total_price_after_discount'=> (float) $item->total_price_after_discount,
                ];
            }),

            'store_total_invoice' => (float) $this->items->sum('total_price_after_discount'),
        ];
    }
}
