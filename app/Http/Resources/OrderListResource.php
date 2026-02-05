<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderListResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user' => $this->user ? [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'phone' => $this->user->phone_display,
            ] : null,
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
