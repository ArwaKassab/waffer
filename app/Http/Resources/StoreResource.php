<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StoreResource extends JsonResource
{
    public function toArray($request)
    {
        $workHours = null;

        if ($this->open_hour && $this->close_hour) {
            $workHours = substr($this->open_hour, 0, 5) . '-' . substr($this->close_hour, 0, 5);
        }

        return [
            'id'            => $this->id,
            'name'          => $this->name,
//            'user_name'     => $this->user_name,
            'phone' => $this->formatPhone($this->phone),
            'status'        => (bool)$this->status,

            'open_hour'     => $this->open_hour,
            'close_hour'    => $this->close_hour,
            'work_hours'    => $workHours,

            'image_url'     => $this->image_url,

            'area' => $this->area ? [
                'id'   => $this->area->id,
                'name' => $this->area->name,
            ] : null,

            'categories' => $this->whenLoaded('categories', function () {
                return $this->categories->map(function ($cat) {
                    return [
                        'id'   => $cat->id,
                        'name' => $cat->name,
                    ];
                });
            }),

            'note'       => $this->note,
            'created_at' => optional($this->created_at)->format('Y-m-d H:i'),
        ];
    }
    private function formatPhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        // إذا كان يبدأ بـ 00963 → استبدله بـ 0
        if (str_starts_with($phone, '00963')) {
            return '0' . substr($phone, 5);
        }

        return $phone;
    }

}
