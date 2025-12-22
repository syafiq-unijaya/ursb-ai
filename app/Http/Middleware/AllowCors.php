<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AllowCors
{
    public function handle(Request $request, Closure $next)
    {
        // Handle preflight
        if ($request->getMethod() === 'OPTIONS') {
            $response = response('', 204);
        } else {
            $response = $next($request);
        }

        // Allow all origins for now; tighten in production by using env var
        $response->headers->set('Access-Control-Allow-Origin', env('CORS_ALLOWED_ORIGIN', '*'));
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN');
        $response->headers->set('Access-Control-Expose-Headers', 'Authorization');

        return $response;
    }
}
