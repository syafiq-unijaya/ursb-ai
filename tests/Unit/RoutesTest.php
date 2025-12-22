<?php

use Illuminate\Support\Facades\Route;

it('registers api routes outside of web middleware', function () {
    $routes = collect(Route::getRoutes())->map(fn($r) => [
        'uri' => $r->uri(),
        'middleware' => $r->gatherMiddleware(),
    ]);

    $apiLogin = $routes->first(fn($r) => $r['uri'] === 'api/login');

    expect($apiLogin)->not->toBeNull();
    expect(in_array('web', $apiLogin['middleware']) )->toBeFalse();
});
