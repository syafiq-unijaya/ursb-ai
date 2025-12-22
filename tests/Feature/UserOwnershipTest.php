<?php

use App\Models\Car;
use App\Models\User;

it('allows a user to add ownership from their edit page', function () {
    $user = User::factory()->create();

    $car = Car::create([
        'brand' => 'Honda',
        'model' => 'Accord',
        'year' => 2021,
    ]);

    $this->actingAs($user)
        ->post("/cars/{$car->id}/owners", [
            'user_id' => $user->id,
            'plate' => 'XYZ-999',
        ])
        ->assertRedirect(route('users.show', $user->id));

    $this->assertDatabaseHas('car_user', [
        'car_id' => $car->id,
        'user_id' => $user->id,
        'plate' => 'XYZ-999',
    ]);
});

it('forbids adding ownership for another user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $car = Car::create([
        'brand' => 'Toyota',
        'model' => 'RAV4',
        'year' => 2019,
    ]);

    $this->actingAs($user)
        ->post("/cars/{$car->id}/owners", [
            'user_id' => $other->id,
            'plate' => 'AAA-111',
        ])
        ->assertStatus(403);
});
