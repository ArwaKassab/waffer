<?php

// app/Http/Middleware/RefreshVisitorId.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cookie;

class RefreshVisitorId
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $visitorId = $request->cookie('visitor_id') ?? (string) \Illuminate\Support\Str::uuid();

        \Illuminate\Support\Facades\Cookie::queue(
            cookie('visitor_id', $visitorId, 60*24*30, '/', null, true, true, false, 'Lax')
        );

        return $response;
    }

}

