<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'address_details' => $this->address_details,
            'latitude'        => $this->latitude,
            'longitude'       => $this->longitude,
            'is_default'      => $this->is_default,
            'area_id'         => $this->area_id,
            // يمكن إضافة اسم المنطقة إذا حبيت، مثل $this->area->name بشرط العلاقة في موديل Address
        ];
    }
}

