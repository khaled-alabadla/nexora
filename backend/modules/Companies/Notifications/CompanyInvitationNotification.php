<?php

declare(strict_types=1);

namespace Modules\Companies\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Companies\Models\Company;
use Modules\Companies\Models\Role;

final class CompanyInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Company $company,
        private readonly Role $role,
        private readonly string $token,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim((string) config('app.frontend_url'), '/')
            .'/invitations/'.$this->token.'/accept';

        return (new MailMessage)
            ->subject("You've been invited to join {$this->company->name} on Nexora")
            ->line("You've been invited to join {$this->company->name} as {$this->role->name}.")
            ->action('Accept invitation', $url)
            ->line('This invitation expires in 7 days. If you were not expecting it, you can ignore this email.');
    }
}
