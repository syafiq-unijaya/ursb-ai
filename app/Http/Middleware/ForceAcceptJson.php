<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceAcceptJson
{
    public function handle(Request $request, Closure $next)
    {
        // Ensure API routes always expect JSON responses
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
