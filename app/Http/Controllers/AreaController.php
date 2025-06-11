<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\VisitorService;
use App\Services\AreaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class AreaController extends Controller
{
    protected AreaService $areaService;

    public function __construct(AreaService $areaService)
    {
        $this->areaService = $areaService;
    }

    public function setArea(Request $request)
    {
        $request->validate([
            'area_id' => 'required|integer',
        ]);

        $visitorId = $request->cookie('visitor_id');

        if (!$visitorId) {
            $visitorId = (string) \Illuminate\Support\Str::uuid();
        }

        if (auth('sanctum')->check()) {
            $user = $request->user();
            $user->area_id = $request->area_id;
            $user->save();

            $response = response()->json([
                'message'    => 'تم تحديد المنطقة بنجاح',
                'area_id'    => $user->area_id,
                'visitor_id' => $visitorId,
            ]);
        } else {
            \App\Services\VisitorService::setArea($visitorId, $request->area_id);

            $response = response()->json([
                'message'    => 'تم تحديد المنطقة بنجاح',
                'area_id'    => $request->area_id,
                'visitor_id' => $visitorId,
            ]);
        }

        return $response->cookie(
            'visitor_id',
            $visitorId,
            60 * 24 * 30,
            '/', null, false, false, false, 'Strict'
        );
    }


    public function index()
    {
        $areas = $this->areaService->getAllAreas();

        return response()->json([
            'data' => $areas
        ]);
    }
}
