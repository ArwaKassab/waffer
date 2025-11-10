<?php

namespace App\Http\Controllers;

use App\Services\OfferDiscountService;
use Illuminate\Http\Request;

class OfferDiscountController extends Controller
{
    protected $service;

    public function __construct(OfferDiscountService $service)
    {
        $this->service = $service;
    }



    public function available(Request $request)
    {
        $perPage = (int) $request->query('per_page', 10);
        $areaId  = (int) $request->query('area_id');

        if (!$areaId) {
            return response()->json(['message' => 'من فضلك حدد منطقتك أولًا.'], 400);
        }

        $paginator = $this->service->getAvailableDiscountsByArea($areaId, $perPage);

        if (is_array($paginator)) {
            $paginator = collect($paginator);
        }

        // حوّلي العناصر داخل الـ paginator
        $paginator->getCollection()->transform(function ($d) {
            return [
                'id'         => $d->id,
                'start_date' => $d->start_date?->toDateString(), // بدون وقت
                'end_date'   => $d->end_date?->toDateString(),   // بدون وقت
                'product' => [
                    'id'    => $d->product?->id,
                    'name'  => $d->product?->name,
                    'image' => $d->product?->image_url ?? $d->product?->image,
                    'details'=> $d->product?->details,
                    'new_price'  => (float) $d->new_price,

                ],
                'store' => [
                    'id'      => $d->product?->store?->id,
                    'name'    => $d->product?->store?->name,
                    'area_id' => $d->product?->store?->area_id,
                    'image'   => $d->product?->store?->image_url ?? $d->product?->store?->image,
                ],
            ];
        });

        return response()->json([
            'status'  => true,
            'message' => 'تم جلب العروض والخصومات المتاحة للمنطقة بنجاح.',
            'data'    => $paginator, // يبقى Paginator مع الميتاداتا
        ]);
    }


}
