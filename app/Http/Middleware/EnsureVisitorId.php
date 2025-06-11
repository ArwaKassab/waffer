<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureVisitorId
{
    public function handle(Request $request, Closure $next)
    {
        if (auth('sanctum')->check()) {
            return $next($request);
        }

        if ($request->cookie('visitor_id')) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Unauthorized. You must be a visitor or logged in.'
        ], 401);
    }
}
