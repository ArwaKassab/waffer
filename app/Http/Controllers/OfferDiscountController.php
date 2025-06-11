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
        $perPage = $request->query('per_page', 10);
        $areaId  = $request->area_id;

        if (!$areaId) {
            return response()->json([
                'message' => 'من فضلك حدد منطقتك أولًا.'
            ], 400);
        }

        $data = $this->service->getAllAvailableByArea($areaId, $perPage);

        return response()->json([
            'status'  => true,
            'message' => 'تم جلب العروض والخصومات المتاحة للمنطقة بنجاح.',
            'data'    => $data
        ]);
    }
}
