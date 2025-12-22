<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CarController as ApiCarController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Middleware\AllowCors;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are intended for external clients. They are protected by
| Laravel Sanctum and a small CORS middleware that allows external origins
| to access the endpoints. Install and configure Sanctum (instructions
| in README/notes below) before using these routes.
|
*/

Route::prefix('api')
    ->middleware(['api', App\Http\Middleware\ForceAcceptJson::class, App\Http\Middleware\LogIncomingRequest::class, AllowCors::class])
    ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
    ->group(function () {
    // Authentication: issue/revoke tokens
    // Explicitly remove CSRF middleware for the login endpoint to avoid 419 from external clients
    Route::post('/login', [AuthController::class, 'login'])->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

    // Protected routes require a valid Sanctum token under /api/v1
    Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::get('/cars', [ApiCarController::class, 'index']);
        Route::get('/cars/{car}', [ApiCarController::class, 'show']);
        // Add create/update/destroy endpoints if you want clients to modify cars
        Route::post('/cars', [ApiCarController::class, 'store']);
        Route::put('/cars/{car}', [ApiCarController::class, 'update']);
        Route::delete('/cars/{car}', [ApiCarController::class, 'destroy']);
    });
});
