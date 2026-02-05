<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderListResource extends JsonResource
{
    public function toArray($request)
    {
        $user = $this->whenLoaded('user');

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => [
                'id' => $user?->id,
                'name' => $user?->name ?? 'مستخدم محذوف',
                'phone' => $user?->trashed() ? $user->phone_shadow : $user->phone,
                'user_deleted' => $user?->trashed() ?? false,
            ],


            'area_id' => $this->area_id,
            'address_id' => $this->address_id,
            'total_product_price' => $this->total_product_price,
            'discount_fee' => $this->discount_fee,
            'totalAfterDiscount' => $this->totalAfterDiscount,
            'delivery_fee' => $this->delivery_fee,
            'total_price' => $this->total_price,
            'date' => $this->date,
            'time' => $this->time,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
