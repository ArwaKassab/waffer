<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplaintResource extends JsonResource
{
    // لإلغاء "data" wrapper لهذا الـ Resource فقط
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        // تأكدي أنك عاملة eager load لـ user في الريبو: ->with('user:id,name,phone')
        $user = $this->whenLoaded('user');

        return [
            'id'         => $this->id,
            'user_id'    => $this->user_id,
            'user_name'  => $user?->name,
            'user_phone' => $user?->phone,
            'type'       => $this->type,

            'date'       => optional($this->created_at)->format('Y-m-d'),
            'time'       => optional($this->created_at)->format('H:i:s'),
            'created_at' => optional($this->created_at)->format('Y-m-d H:i:s'),
            'message' => $this->when($request->routeIs('subadmin.complaints.show'), $this->message),

        ];
    }
}
