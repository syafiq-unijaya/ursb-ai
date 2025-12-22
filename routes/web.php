<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\OwnershipController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__ . '/settings.php';

// API routes are registered separately in App\Providers\ApiRouteServiceProvider
// to ensure they are not wrapped in the `web` middleware group (avoids CSRF issues).
Route::middleware('auth')->group(function () {
    Route::resource('users', UserController::class);
    Route::resource('cars', CarController::class);

    // Ownership: attach/detach cars to authenticated user
    Route::post('/cars/{car}/owners', [OwnershipController::class, 'store'])->name('cars.owners.store');
    Route::delete('/cars/{car}/owners', [OwnershipController::class, 'destroy'])->name('cars.owners.destroy');

    // Bulk attach ownerships to a user (multiple car_id + plate pairs)
    Route::post('/users/{user}/owners', [OwnershipController::class, 'storeMany'])->name('users.owners.store');
});
