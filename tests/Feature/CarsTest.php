<?php

use App\Models\Car;
use App\Models\User;

beforeEach(function () {
    // RefreshDatabase is applied via Pest config for Feature tests
});

it('allows a user to create an ownership (user owns a car with plate)', function () {
    $user = User::factory()->create();

    // create a reference car model
    $car = Car::create([
        'brand' => 'Honda',
        'model' => 'Civic',
        'year' => 2020,
    ]);

    $this->actingAs($user)
        ->post("/cars/{$car->id}/owners", [
            'plate' => 'ABC-123',
        ])
        ->assertRedirect(route('users.show', $user->id));

    $this->assertDatabaseHas('car_user', [
        'car_id' => $car->id,
        'user_id' => $user->id,
        'plate' => 'ABC-123',
    ]);
});

it('forbids removing an ownership by a non-owner', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $car = Car::create([
        'brand' => 'Ford',
        'model' => 'Focus',
        'year' => 2018,
    ]);

    // attach ownership
    $car->users()->attach($owner->id, ['plate' => 'OWN-001']);

    $this->actingAs($other)
        ->delete("/cars/{$car->id}/owners")
        ->assertStatus(403);
});
