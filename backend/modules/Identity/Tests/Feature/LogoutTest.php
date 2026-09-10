<?php

declare(strict_types=1);

use App\Models\User;

it('logs the user out and ends the session', function () {
    $user = User::factory()->create(['password' => 'Str0ng-passphrase!']);

    $this->postJson(apiUrl('auth/login'), [
        'email' => $user->email,
        'password' => 'Str0ng-passphrase!',
    ])->assertOk();

    $this->assertAuthenticated();

    $this->postJson(apiUrl('auth/logout'))->assertNoContent();

    // Drop cached guard state so the next request re-resolves auth from the
    // (now invalidated) session, exactly as a fresh HTTP request would.
    $this->app['auth']->forgetGuards();
    $this->getJson(apiUrl('auth/me'))->assertUnauthorized();
});

it('rejects logout when not authenticated', function () {
    $this->postJson(apiUrl('auth/logout'))->assertUnauthorized();
});
