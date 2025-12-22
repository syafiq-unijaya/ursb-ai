<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Session\TokenMismatchException;

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
        // Keep this list minimal; API routes are loaded outside the `web`
        // middleware group so CSRF is not applied to them.
    ];

    public function handle($request, \Closure $next)
    {
        try {
            return parent::handle($request, $next);
        } catch (TokenMismatchException $e) {
            Log::channel('single')->error('TOKEN_MISMATCH_CAUGHT', [
                'path' => $request->path(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'headers' => [
                    'content-type' => $request->header('content-type'),
                    'accept' => $request->header('accept'),
                    'cookie' => $request->header('cookie'),
                    'origin' => $request->header('origin'),
                    'referer' => $request->header('referer'),
                    'user-agent' => $request->header('user-agent'),
                ],
                'payload' => $this->redact($request->all()),
            ]);

            throw $e;
        }
    }

    protected function redact(array $payload): array
    {
        if (isset($payload['password'])) {
            $payload['password'] = 'REDACTED';
        }

        return $payload;
    }
}
