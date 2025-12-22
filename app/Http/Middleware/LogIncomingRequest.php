<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogIncomingRequest
{
    public function handle(Request $request, Closure $next)
    {
        $route = $request->route();
        $action = $route ? $route->getActionName() : null;
        $name = $route ? $route->getName() : null;

        // Log concise, structured debug info so it's easy to find related failures
        Log::channel('single')->info('INCOMING_REQUEST', [
            'method' => $request->method(),
            'path' => $request->path(),
            'host' => $request->getHttpHost(),
            'url' => $request->fullUrl(),
            'route_name' => $name,
            'route_action' => $action,
            'headers' => [
                'content-type' => $request->header('content-type'),
                'accept' => $request->header('accept'),
                'origin' => $request->header('origin'),
                'referer' => $request->header('referer'),
                'cookie' => $request->header('cookie'),
                'user-agent' => $request->header('user-agent'),
            ],
            'payload' => $this->redact($request->all()),
        ]);

        try {
            return $next($request);
        } catch (\Illuminate\Session\TokenMismatchException $e) {
            Log::channel('single')->error('TOKEN_MISMATCH', [
                'method' => $request->method(),
                'path' => $request->path(),
                'url' => $request->fullUrl(),
                'headers' => $request->headers->all(),
                'payload' => $this->redact($request->all()),
            ]);

            throw $e;
        }
    }

    private function redact(array $payload): array
    {
        $redacted = $payload;

        foreach (['password'] as $field) {
            if (isset($redacted[$field])) {
                $redacted[$field] = 'REDACTED';
            }
        }

        return $redacted;
    }
}
