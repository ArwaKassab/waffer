<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderListResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,

            // معلومات المستخدم
            'user' => $this->user ? [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'phone' => str_starts_with($this->user->phone, '00963')
                    ? '0' . substr($this->user->phone, 4)
                    : $this->user->phone,
            ] : null,

            // معلومات الطلب الأساسية
            'area_id'              => $this->area_id,
            'address_id'           => $this->address_id,
            'total_product_price'  => $this->total_product_price,
            'discount_fee'         => $this->discount_fee,
            'totalAfterDiscount'   => $this->totalAfterDiscount,
            'delivery_fee'         => $this->delivery_fee,
            'total_price'          => $this->total_price,
            'date'                 => $this->date,
            'time'                 => $this->time,
            'status'               => $this->status,
            'payment_method'       => $this->payment_method,
            'notes'                => $this->notes,
            'created_at'           => $this->created_at->toDateTimeString(),
        ];
    }
}
