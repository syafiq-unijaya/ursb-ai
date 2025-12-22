<?php

use App\Models\Car;
use App\Models\User;
use Illuminate\Support\Str;

it('allows authenticated clients to access cars via token', function () {
    // create user and some cars
    $user = User::factory()->create([
        'password' => bcrypt('secret'),
    ]);

    Car::factory()->count(3)->create();

    // login to get token
    $login = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'secret',
        'device_name' => 'test-client',
    ]);

    $login->assertStatus(200);

    $token = $login->json('token');
    expect($token)->not->toBeNull();

    // access cars
    $resp = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/cars');

    $resp->assertStatus(200)->assertJsonStructure(['data', 'links', 'meta']);
});
