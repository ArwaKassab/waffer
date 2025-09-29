<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => (float)$this->price,
            'new_price' => (float)($this->activeDiscount->new_price ?? $this->price),
            'has_active_discount' => (bool)$this->activeDiscount,
            'status' => $this->status,
            'quantity' => (float)$this->quantity,
            'unit' => $this->unit,
            'store_id' => $this->store_id,
            'image_url' => $this->image_url,
        ];
    }


}
