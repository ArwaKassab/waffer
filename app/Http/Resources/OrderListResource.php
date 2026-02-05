<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderListResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user' => [
                'name'  => $this->user_id->name,
                'phone' => str_starts_with($this->user_id->phone, '00963')
                    ? '0' . substr($this->user->phone, 4)
                    : $this->user->phone,
            ],
            'total_price' => $this->total_price,
            'date'        => $this->date,
            'time'        => $this->time,
        ];
    }
}
