<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class StoreAdminShowResource extends JsonResource
{
    public function toArray($request)
    {
        $workHours = null;
        if (!empty($this->open_hour) && !empty($this->close_hour)) {
            $from = Carbon::parse($this->open_hour)->format('H:i');
            $to   = Carbon::parse($this->close_hour)->format('H:i');
            $workHours = "{$from}-{$to}";
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'user_name' => $this->user_name,
            'phone' => $this->phone_display,
            'is_open_now' => (bool) $this->is_open_now,
            'status' => (bool) $this->status,
            'open_hour' => $this->open_hour,
            'close_hour' => $this->close_hour,
            'work_hours' => $workHours,
            'image_url' => $this->image_url,

            'area' => [
                'id' => optional($this->area)->id,
                'name' => optional($this->area)->name,
            ],

            'categories' => $this->categories?->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                ])->values() ?? [],

            'note' => $this->note,
            // ✅ منتجات المتجر
            'products' => $this->products?->map(function ($p) {
                    $hasDiscount = (bool) $p->activeDiscount;

                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'status' => $p->status,
                        'quantity' => (float) $p->quantity,
                        'unit' => $p->unit,
                        'details' => $p->details,

                        'price' => (float) $p->price,
                        'has_active_discount' => $hasDiscount,
                        'new_price' => (float) ($p->activeDiscount?->new_price ?? $p->price),

                        'image_url' => $p->image_url,
                        'created_at' => optional($p->created_at)->format('Y-m-d H:i'),
                    ];
                })->values() ?? [],
        ];
    }
}
