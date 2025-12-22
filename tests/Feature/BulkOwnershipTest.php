<?php

use App\Models\Car;
use App\Models\User;

it('allows adding multiple ownerships in one request', function () {
    $user = User::factory()->create();

    $car1 = Car::create(['brand' => 'Honda', 'model' => 'Civic', 'year' => 2020]);
    $car2 = Car::create(['brand' => 'Toyota', 'model' => 'Corolla', 'year' => 2019]);

    $this->actingAs($user)
        ->post(route('users.owners.store', $user->id), [
            'owners' => [
                ['car_id' => $car1->id, 'plate' => 'BULK-1'],
                ['car_id' => $car2->id, 'plate' => 'BULK-2'],
            ],
        ])
        ->assertRedirect(route('users.show', $user->id));

    $this->assertDatabaseHas('car_user', ['car_id' => $car1->id, 'user_id' => $user->id, 'plate' => 'BULK-1']);
    $this->assertDatabaseHas('car_user', ['car_id' => $car2->id, 'user_id' => $user->id, 'plate' => 'BULK-2']);
});