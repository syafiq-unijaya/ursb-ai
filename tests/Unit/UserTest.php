<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('creates personal access tokens for a user', function () {
    $user = User::factory()->create();

    $token = $user->createToken('unit-test-device')->plainTextToken;

    expect(is_string($token))->toBeTrue();

    $exists = DB::table('personal_access_tokens')
        ->where('tokenable_id', $user->id)
        ->where('name', 'unit-test-device')
        ->exists();

    expect($exists)->toBeTrue();
});
