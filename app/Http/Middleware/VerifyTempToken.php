<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use App\Repositories\Contracts\UserRepositoryInterface;
class VerifyTempToken
{
    public function handle(Request $request, Closure $next)
    {
        $phone = $request->phone;

        if (!$phone) {
            return response()->json(['message' => 'رقم الهاتف مفقود'], 400);
        }

        $authHeader = $request->header('Authorization');

        if (!$authHeader || !Str::startsWith($authHeader, 'Bearer ')) {
            return response()->json(['message' => 'رمز التحقق مفقود'], 401);
        }

        $tempToken = Str::after($authHeader, 'Bearer ');

        // استخدام ال UserRepository لمعالجة الرقم إذا تحتاج
        $processedPhone = app()->make(\App\Repositories\Contracts\UserRepositoryInterface::class)->processPhoneNumber($phone);

        $cachedToken = Redis::get('reset_password_token:' . $processedPhone);

        if (!$cachedToken || $cachedToken !== $tempToken) {
            return response()->json(['message' => 'رمز الدخول المؤقت غير صحيح أو منتهي'], 401);
        }

        return $next($request);
    }
}
