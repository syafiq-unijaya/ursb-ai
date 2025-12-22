<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class ApiRouteServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Load API routes outside of the `web` middleware group so CSRF/session
        // middleware do not apply. These routes use the `api` middleware group.
        // Load the routes file directly. The routes defined in routes/api.php
        // include their own prefix/middleware chain, so we just include the file.
        Route::group([], base_path('routes/api.php'));
    }
}
