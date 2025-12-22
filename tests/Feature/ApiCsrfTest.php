<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows POST /api/login without CSRF and returns a token', function () {
    $user = User::factory()->create(['password' => bcrypt('secret')]);

    $resp = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'secret',
        'device_name' => 'test-client',
    ]);

    $resp->assertStatus(200)->assertJsonStructure(['token']);
});

it('allows POST /api/login/ (trailing slash) without CSRF and returns a token', function () {
    $user = User::factory()->create(['password' => bcrypt('secret')]);

    $resp = $this->postJson('/api/login/', [
        'email' => $user->email,
        'password' => 'secret',
        'device_name' => 'test-client',
    ]);

    $resp->assertStatus(200)->assertJsonStructure(['token']);
});

it('accepts x-www-form-urlencoded POST to /api/login without CSRF', function () {
    $user = User::factory()->create(['password' => bcrypt('secret')]);

    $resp = $this->post('/api/login', [
        'email' => $user->email,
        'password' => 'secret',
        'device_name' => 'test-client',
    ]);

    $resp->assertStatus(200)->assertJsonStructure(['token']);
});


it('returns 401 for unauthenticated POST to /api/v1/cars (not 419)', function () {
    $resp = $this->postJson('/api/v1/cars', [
        'brand' => 'X', 'model' => 'Y', 'variant' => 'Z', 'year' => 2020,
    ]);

    // Should be 401 (unauthenticated) rather than a CSRF 419 response
    $resp->assertStatus(401);
});
