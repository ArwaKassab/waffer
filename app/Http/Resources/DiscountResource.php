<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DiscountResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'start_date' => $this->start_date?->toDateString(),
            'end_date'   => $this->end_date?->toDateString(),

            'product' => [
                'id'       => $this->product?->id,
                'name'     => $this->product?->name,
                'image'    => $this->product?->image_url ?? $this->product?->image,
                'details'  => $this->product?->details,
                'isAvailable' =>  $this->product->status === 'available',
                'old_price'=> $this->product?->price,
                'new_price'=> (float) $this->new_price,
            ],

            'store' => [
                'id'      => $this->product?->store?->id,
                'name'    => $this->product?->store?->name,
                'area_id' => $this->product?->store?->area_id,
                'is_open_now' => $this->product?->store?->is_open_now,
                'image'   => $this->product?->store?->image_url ?? $this->product?->store?->image,
            ],
        ];
    }
}
