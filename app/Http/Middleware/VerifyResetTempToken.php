<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class VerifyResetTempToken
{
    public function handle(Request $request, Closure $next)
    {
        $tempId = $request->input('temp_id');
        $token  = $request->header('X-Reset-Token') ?: $request->input('reset_token');

        if (!$tempId || !$token) {
            return response()->json(['message' => 'رموز التحقق مطلوبة.'], 401);
        }

        $cached = Cache::get('reset:passed:' . $tempId);
        if (!$cached) {
            return response()->json(['message' => 'انتهت صلاحية التحقق.'], 410);
        }

        $data = json_decode($cached, true) ?: [];
        if (($data['reset_token'] ?? null) !== $token) {
            return response()->json(['message' => 'رمز التحقق غير صالح.'], 401);
        }

        return $next($request);
    }
}
