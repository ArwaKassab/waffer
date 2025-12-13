<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Services\VisitorService;
use App\Services\AreaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cookie;


class AreaController extends Controller
{
    protected AreaService $areaService;

    public function __construct(AreaService $areaService)
    {
        $this->areaService = $areaService;
    }


//    public function setArea(Request $request)
//    {
//        $request->validate([
//            'area_id' => 'required|integer',
//        ]);
//
//        $visitorId = $request->cookie('visitor_id');
//
//        if (!$visitorId) {
//            $visitorId = (string) \Illuminate\Support\Str::uuid();
//        }
//
//        if (auth('sanctum')->check()) {
//            $user = $request->user();
//            $user->area_id = $request->area_id;
//            $user->save();
//
//            $response = response()->json([
//                'message'    => 'تم تحديد المنطقة بنجاح',
//                'area_id'    => $user->area_id,
//                'visitor_id' => $visitorId,
//            ]);
//        } else {
//            \App\Services\VisitorService::setArea($visitorId, $request->area_id);
//
//            $response = response()->json([
//                'message'    => 'تم تحديد المنطقة بنجاح',
//                'area_id'    => $request->area_id,
//                'visitor_id' => $visitorId,
//            ]);
//        }
//
//        return $response->cookie(
//            'visitor_id',
//            $visitorId,
//            60 * 24 * 30,
//            '/', null, false, false, false, 'Strict'
//        );
//    }

    public function setArea(Request $request)
    {
        $validated = $request->validate([
            'area_id' => 'required|integer|exists:areas,id',
        ]);

        // احصل على visitor_id من الكوكي أو أنشئ واحدًا جديدًا
        $visitorId = $request->cookie('visitor_id') ?: (string) Str::uuid();

        // إن كان المستخدم مسجلاً (Sanctum)
        if ($request->user()) {
            $user = $request->user();
            $user->area_id = (int) $validated['area_id'];
            $user->save();

            $response = response()->json([
                'message'    => 'تم تحديد المنطقة بنجاح',
                'area_id'    => $user->area_id,
                'visitor_id' => $visitorId,
            ]);
        } else {
            // زائر
            \App\Services\VisitorService::setArea($visitorId, (int) $validated['area_id']);

            $response = response()->json([
                'message'    => 'تم تحديد المنطقة بنجاح',
                'area_id'    => (int) $validated['area_id'],
                'visitor_id' => $visitorId,
            ]);
        }

        $domain   = config('session.domain');
        $secure   = config('session.secure', app()->isProduction());
        $sameSite = config('session.same_site', 'Lax');
        $httpOnly = true;
        $minutes  = 60 * 24 * 30;


        Cookie::queue(
            cookie('visitor_id', $visitorId, $minutes, '/', $domain, $secure, $httpOnly, false, $sameSite)
        );

        return $response;
    }


    public function index()
    {
        $areas = $this->areaService->getAllAreas();

        return response()->json([
            'data' => $areas
        ]);
    }

    public function showFee(Area $area)
    {
        $area->makeVisible(['delivery_fee', 'free_delivery_from']); // احتياط لو كانوا hidden
        return response()->json([
            'data' => $area->only(['id', 'name', 'delivery_fee', 'free_delivery_from'])
        ]);
    }
}
