<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Models\User;

class DetectArea
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('customer/set-area')) {
            return $next($request);
        }

        $user = auth('sanctum')->user();
        if ($user instanceof User) {
            $areaId = $user->area_id;

            if ($areaId) {
                $request->merge(['area_id' => $areaId]);
                return $next($request);
            }
        }

        $visitorId = $request->cookie('visitor_id');
        if ($visitorId) {
            $areaId = \App\Services\VisitorService::getArea($visitorId);
            if ($areaId) {
                $request->merge(['area_id' => $areaId]);
                return $next($request);
            }
        }

        return response()->json([
            'message' => 'من فضلك حدد منطقتك أولًا باستخدام /set-area'
        ], 400);
    }
}
