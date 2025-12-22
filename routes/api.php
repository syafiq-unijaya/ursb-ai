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

Route::middleware([AllowCors::class])->group(function () {
    // Authentication: issue/revoke tokens
    Route::post('/login', [AuthController::class, 'login']);

    // Protected routes require a valid Sanctum token
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
