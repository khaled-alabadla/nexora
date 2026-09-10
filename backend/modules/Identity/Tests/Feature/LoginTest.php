<?php

declare(strict_types=1);

use App\Models\User;

it('signs a user in with valid credentials', function () {
    $user = User::factory()->create(['password' => 'Str0ng-passphrase!']);

    $response = $this->postJson(apiUrl('auth/login'), [
        'email' => $user->email,
        'password' => 'Str0ng-passphrase!',
    ]);

    $response->assertOk()->assertJsonPath('data.user.email', $user->email);
    $this->assertAuthenticatedAs($user);
});

it('rejects invalid credentials', function () {
    $user = User::factory()->create(['password' => 'Str0ng-passphrase!']);

    $this->postJson(apiUrl('auth/login'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertStatus(422)->assertJsonValidationErrorFor('email');

    $this->assertGuest();
});

it('throttles repeated failed attempts', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $ignored) {
        $this->postJson(apiUrl('auth/login'), [
            'email' => $user->email,
            'password' => 'wrong',
        ])->assertStatus(422);
    }

    $response = $this->postJson(apiUrl('auth/login'), [
        'email' => $user->email,
        'password' => 'wrong',
    ])->assertStatus(422);

    expect($response->json('errors.email.0'))->toContain('Too many');
});

it('requires authentication for protected endpoints', function () {
    $this->getJson(apiUrl('auth/me'))->assertUnauthorized();
});
