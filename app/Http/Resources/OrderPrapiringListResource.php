<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderPrapiringListResource extends JsonResource
{
    public function toArray($request)
    {

        $stores = $this->whenLoaded('items')->groupBy('store_id')->map(function ($items, $storeId) {
            $store = $items->first()->store;
            $status = $items->pluck('status')->unique();
            $status = $status->count() === 1 ? $status->first() : 'لم يتم تجهيز كامل المنتجات بعد';

            return [
                'id' => $store?->id,
                'name' => $store?->name ?? 'متجر محذوف',
                'status' => $status,
            ];
        })->values();

        return [
            'order_id' => $this->id,
            'stores' => $stores,
        ];
    }
}
