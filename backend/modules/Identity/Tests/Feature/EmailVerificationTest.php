<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

it('sends a verification notification on registration', function () {
    Notification::fake();

    $this->postJson(apiUrl('auth/register'), [
        'name' => 'Ada',
        'email' => 'ada@example.com',
        'password' => 'Str0ng-passphrase!',
        'password_confirmation' => 'Str0ng-passphrase!',
        'company_name' => 'Engines',
    ])->assertCreated();

    Notification::assertSentTo(User::where('email', 'ada@example.com')->sole(), VerifyEmail::class);
});

it('verifies the email from a valid signed link and redirects to the SPA', function () {
    Event::fake([Verified::class]);
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute('verification.verify', Carbon::now()->addHour(), [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);

    $this->get($url)->assertRedirect(config('app.frontend_url').'/login?verified=1');

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatched(Verified::class);
});

it('rejects a tampered verification hash', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute('verification.verify', Carbon::now()->addHour(), [
        'id' => $user->id,
        'hash' => sha1('someone-else@example.com'),
    ]);

    $this->get($url)->assertForbidden();
    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
});

it('rejects an unsigned verification link', function () {
    $user = User::factory()->unverified()->create();

    $this->get(apiUrl("auth/email/verify/{$user->id}/".sha1($user->email)))
        ->assertForbidden();
});

it('resends the verification notification to an authenticated unverified user', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();
    $this->actingAs($user);

    $this->postJson(apiUrl('auth/email/verification-notification'))->assertOk();

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('does not resend when the email is already verified', function () {
    Notification::fake();
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->postJson(apiUrl('auth/email/verification-notification'))->assertOk();

    Notification::assertNothingSent();
});
