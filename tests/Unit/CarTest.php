<?php

use App\Models\Car;
use App\Models\User;

it('can attach and read users pivot data', function () {
    $car = Car::factory()->create();
    $user = User::factory()->create();

    $car->users()->attach($user->id, ['plate' => 'ABC123']);

    $car->refresh();

    expect($car->users->count())->toBe(1);
    expect($car->users->first()->pivot->plate)->toBe('ABC123');
});
