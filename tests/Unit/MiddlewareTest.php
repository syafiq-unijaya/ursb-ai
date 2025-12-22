<?php

use App\Http\Middleware\ForceAcceptJson;
use App\Http\Middleware\AllowCors;
use App\Http\Middleware\LogIncomingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

it('force accept json middleware sets accept header', function () {
    $request = Request::create('/foo', 'GET');

    $middleware = new ForceAcceptJson();

    $middleware->handle($request, function ($req) {
        return response('ok');
    });

    expect($request->headers->get('accept'))->toBe('application/json');
});

it('allow cors responds to preflight with 204 and headers', function () {
    $request = Request::create('/foo', 'OPTIONS');

    $middleware = new AllowCors();

    $response = $middleware->handle($request, function ($req) {
        return response('should-not-run');
    });

    expect($response->getStatusCode())->toBe(204);
    expect($response->headers->get('Access-Control-Allow-Origin'))->not->toBeNull();
    expect($response->headers->get('Access-Control-Allow-Methods'))->toContain('POST');
});

it('log incoming request calls log channel', function () {
    Log::spy();
    // Ensure channel(...) fluent calls don't return null when used with spy
    Log::shouldReceive('channel')->andReturnSelf();
    Log::shouldReceive('info')->once();

    $request = Request::create('/api/test', 'POST', ['foo' => 'bar']);

    $middleware = new LogIncomingRequest();

    $middleware->handle($request, function ($req) {
        return response('ok');
    });

    // Ensure the info logger was called at least once for INCOMING_REQUEST
    Log::shouldHaveReceived('channel')->with('single')->atLeast()->once();
});
