<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * We limit this to the token-based login endpoint so most API routes
     * still go through CSRF protection if mistakenly placed under web.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Allow token-based login (and trailing slash variants) without CSRF
        'api/login',
        'api/login/*',

        // Optionally allow logout token revocation without CSRF if needed
        'api/v1/logout',
        'api/v1/logout/*',
    ];
}
