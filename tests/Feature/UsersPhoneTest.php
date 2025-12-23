<?php

use App\Models\User;

test('phone is saved when creating a user via users.store', function () {
    $actor = User::factory()->create();
    $this->actingAs($actor);

    $response = $this->post(route('users.store'), [
        'name' => 'Alice Example',
        'email' => 'alice.example@example.com',
        'phone_no' => '123-456-7890',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('users.index'));

    $this->assertDatabaseHas('users', [
        'email' => 'alice.example@example.com',
        'phone_no' => '123-456-7890',
    ]);
});

test('phone is updated when patching a user via users.update', function () {
    $actor = User::factory()->create();
    $this->actingAs($actor);

    $user = User::factory()->create([
        'phone_no' => '000-000-0000',
    ]);

    $response = $this->patch(route('users.update', $user), [
        'name' => $user->name,
        'email' => $user->email,
        'phone_no' => '999-999-9999',
        // password left out to keep current
    ]);

    $response->assertRedirect(route('users.index'));

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'phone_no' => '999-999-9999',
    ]);
});
