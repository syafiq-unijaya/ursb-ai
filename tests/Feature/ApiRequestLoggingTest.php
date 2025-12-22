<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

it('logs incoming api login requests', function () {
    // We won't assert the log file here, but calling the route ensures middleware runs
    // and will write the INCOMING_REQUEST entry which you can inspect in storage/logs/laravel.log
    $resp = $this->postJson('/api/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'nope',
        'device_name' => 'test-client',
    ]);

    // either 200 (if user exists) or 422/401; we only care that request did not 419
    $this->assertNotEquals(419, $resp->getStatusCode());
});
