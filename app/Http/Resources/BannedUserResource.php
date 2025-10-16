<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BannedUserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'name'  => (string) $this->name,
            'phone' => (string) $this->phone,
        ];
    }
}
