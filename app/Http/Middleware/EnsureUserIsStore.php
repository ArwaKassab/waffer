<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsStore
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->type !== 'store') {
            return response()->json([
                'message' => 'Only store users can access this resource.'
            ], 403);
        }

        return $next($request);
    }
}
