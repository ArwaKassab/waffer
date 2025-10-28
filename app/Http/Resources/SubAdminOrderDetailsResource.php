<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubAdminOrderDetailsResource extends JsonResource
{
    public function toArray($request)
    {
        $items = $this->whenLoaded('items', $this->items, collect());

        $totalBeforeDelivery = is_null($this->totalAfterDiscount)
            ? (float) $this->total_product_price - (float) $this->discount_fee
            : (float) $this->totalAfterDiscount;

        $deliveryFee = (float) $this->delivery_fee;

        $totalAfterDelivery = is_null($this->total_price)
            ? $totalBeforeDelivery + $deliveryFee
            : (float) $this->total_price;

        return [
            'order' => [
                'id'             => $this->id,
                'status'         => (string) $this->status,
                'payment_method' => (string) $this->payment_method,
                'date'           => (string) $this->date,
                'time'           => (string) $this->time,
                'created_at'     => optional($this->created_at)->format('Y-m-d H:i'),
                'notes'          => $this->notes,

                // المطلوب فقط
                'delivery_fee'          => $deliveryFee,
                'total_before_delivery' => $totalBeforeDelivery,
                'total_after_delivery'  => $totalAfterDelivery,
            ],

            'customer' => $this->whenLoaded('user', function () {
                return [
                    'id'    => data_get($this->user, 'id'),
                    'name'  => data_get($this->user, 'name'),
                    'phone' => data_get($this->user, 'phone'),
                ];
            }),

            'area' => $this->whenLoaded('area', function () {
                return [
                    'id'   => data_get($this->area, 'id'),
                    'name' => data_get($this->area, 'name'),
                ];
            }),

            'address' => new AddressResource($this->whenLoaded('address')),

            'items' => $items->map(function ($item) {
                $product = $item->relationLoaded('product') ? $item->product : null;
                $store   = $item->relationLoaded('store')   ? $item->store   : null;

                return [
                    'id'         => $item->id,
                    'status'     => (string) $item->status,
                    'quantity'   => (int) $item->quantity,
                    'unit_price'                 => (float) $item->unit_price,
                    'unit_price_after_discount'  => (float) $item->unit_price_after_discount,
                    'discount_value'             => (float) $item->discount_value,
                    'total_price'                => (float) $item->total_price,
                    'total_price_after_discount' => (float) $item->total_price_after_discount,

                    'product' => [
                        'id'        => data_get($product, 'id'),
                        'name'      => data_get($product, 'name'),
                        'image_url' => data_get($product, 'image_url'),
                    ],
                    'store' => $store ? [
                        'id' => data_get($store, 'id'),
                    ] : null,
                ];
            }),
        ];
    }
}
