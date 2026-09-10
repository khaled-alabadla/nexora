<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

it('sends a reset link for a known address', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->postJson(apiUrl('auth/password/forgot'), ['email' => $user->email])->assertOk();

    Notification::assertSentTo($user, ResetPassword::class);
});

it('does not disclose whether an address is registered', function () {
    Notification::fake();

    $this->postJson(apiUrl('auth/password/forgot'), ['email' => 'nobody@example.com'])
        ->assertOk()
        ->assertJsonPath('message', 'If that email is registered, a reset link is on its way.');

    Notification::assertNothingSent();
});

it('resets the password with a valid token', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $this->postJson(apiUrl('auth/password/reset'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'Brand-New-Passphrase!1',
        'password_confirmation' => 'Brand-New-Passphrase!1',
    ])->assertOk();

    expect(Hash::check('Brand-New-Passphrase!1', $user->refresh()->password))->toBeTrue();
});

it('rejects an invalid reset token', function () {
    $user = User::factory()->create();

    $this->postJson(apiUrl('auth/password/reset'), [
        'token' => 'not-a-real-token',
        'email' => $user->email,
        'password' => 'Brand-New-Passphrase!1',
        'password_confirmation' => 'Brand-New-Passphrase!1',
    ])->assertStatus(422)->assertJsonValidationErrorFor('email');
});

it('points the reset link at the SPA', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->postJson(apiUrl('auth/password/forgot'), ['email' => $user->email])->assertOk();

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        $mail = $notification->toMail($user)->toArray();

        return str_contains((string) ($mail['actionUrl'] ?? ''), config('app.frontend_url').'/reset-password?token=');
    });
});
