<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class AttachUserArea
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth('sanctum')->user();

        if ($user instanceof User) {
            $areaId = $user->area_id;

            if ($areaId) {
                $request->merge(['area_id' => $areaId,'user' => $user]);
            }
        }

        return $next($request);
    }
}
