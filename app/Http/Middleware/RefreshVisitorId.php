<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cookie;

class RefreshVisitorId
{
    public function handle($request, Closure $next)
    {
        $response  = $next($request);

        // إعدادات الكوكي
        $domain   = config('session.domain');
        $secure   = config('session.secure', app()->isProduction());
        $sameSite = config('session.same_site', 'Lax');
        $httpOnly = true;


        $idCookie   = 'visitor_id';
        $gateCookie = 'visitor_rr';


        $visitorId = $request->cookie($idCookie) ?: (string) Str::uuid();


        $shouldRenew = $request->method() !== 'GET' || !$request->hasCookie($gateCookie);

        if ($shouldRenew) {
            Cookie::queue(cookie($idCookie, $visitorId, 60 * 24 * 30, '/', $domain, $secure, $httpOnly, false, $sameSite));

            Cookie::queue(cookie($gateCookie, '1', 60 * 24 * 23, '/', $domain, $secure, $httpOnly, false, $sameSite));
        }


        $request->attributes->set('visitor_id', $visitorId);

        return $response;
    }



}

