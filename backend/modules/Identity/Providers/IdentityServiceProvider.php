<?php

declare(strict_types=1);

namespace Modules\Identity\Providers;

use App\Models\User;
use App\Support\Modules\ModuleServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

final class IdentityServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Identity';
    }

    public function boot(): void
    {
        parent::boot();

        $this->pointNotificationLinksAtTheSpa();
    }

    /**
     * The verification email is opened in a browser; the password-reset link is
     * consumed by the SPA. Both must land on the frontend, not the JSON API.
     */
    private function pointNotificationLinksAtTheSpa(): void
    {
        $frontend = rtrim((string) config('app.frontend_url'), '/');

        VerifyEmail::createUrlUsing(fn (User $notifiable): string => URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes((int) config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        ));

        ResetPassword::createUrlUsing(
            fn (User $notifiable, string $token): string => $frontend
                .'/reset-password?token='.$token
                .'&email='.urlencode($notifiable->getEmailForPasswordReset()),
        );
    }
}
