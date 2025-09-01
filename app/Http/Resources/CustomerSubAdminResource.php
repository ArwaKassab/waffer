<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerSubAdminResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'name'           => $this->name,
            'phone'          => $this->phone,
            'wallet_balance' => $this->wallet_balance,
            'is_banned'      => (bool) $this->is_banned,
            'total_orders'   => $this->orders()->count(),
            'addresses'      => $this->addresses->map(fn($a) => [
                'id'      => $a->id,
            ]),
        ];
    }
}
