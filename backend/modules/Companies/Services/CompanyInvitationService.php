<?php

declare(strict_types=1);

namespace Modules\Companies\Services;

use App\Models\User;
use App\Support\Tenancy\CompanyContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Companies\Models\Company;
use Modules\Companies\Models\CompanyInvitation;
use Modules\Companies\Models\CompanyUser;
use Modules\Companies\Models\Role;
use Modules\Companies\Notifications\CompanyInvitationNotification;

/**
 * Invitations to join the active company. Creation and revocation run inside a
 * bound CompanyContext; acceptance runs with no active company and is bound to
 * the invitation token alone.
 */
final class CompanyInvitationService
{
    private const TTL_DAYS = 7;

    public function __construct(private readonly CompanyContext $context) {}

    /**
     * @throws ValidationException
     */
    public function invite(User $inviter, string $email, Role $role): CompanyInvitation
    {
        $company = $this->context->company();
        $email = Str::lower(trim($email));

        if ($role->slug === Role::OWNER) {
            throw ValidationException::withMessages([
                'role' => 'Owner cannot be granted by invitation.',
            ]);
        }

        if ($this->alreadyMember($company, $email)) {
            throw ValidationException::withMessages([
                'email' => 'That person is already a member of this company.',
            ]);
        }

        $plainToken = Str::random(48);

        $invitation = new CompanyInvitation;
        $invitation->fill([
            'role_id' => $role->getKey(),
            'invited_by' => $inviter->getKey(),
            'email' => $email,
            'token' => hash('sha256', $plainToken),
            'expires_at' => Carbon::now()->addDays(self::TTL_DAYS),
        ]);

        try {
            $invitation->save();
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'email' => 'An invitation for that address is already pending.',
            ]);
        }

        Notification::route('mail', $email)->notify(
            new CompanyInvitationNotification($company, $role, $plainToken)
        );

        return $invitation->load('role');
    }

    public function revoke(CompanyInvitation $invitation): void
    {
        $invitation->delete();
    }

    /**
     * Accept the invitation identified by the emailed token. The signed-in user
     * must own the invited email address.
     *
     * @throws ModelNotFoundException invalid / unknown token
     * @throws ValidationException expired, already used, or wrong account
     */
    public function accept(string $plainToken, User $user): Company
    {
        /** @var CompanyInvitation|null $invitation */
        $invitation = CompanyInvitation::withoutCompanyScope(
            fn () => CompanyInvitation::query()
                ->with(['role', 'company'])
                ->where('token', hash('sha256', $plainToken))
                ->first()
        );

        if ($invitation === null) {
            throw new ModelNotFoundException('Unknown invitation.');
        }

        if ($invitation->accepted_at !== null) {
            throw ValidationException::withMessages(['token' => 'This invitation has already been used.']);
        }

        if ($invitation->isExpired()) {
            throw ValidationException::withMessages(['token' => 'This invitation has expired.']);
        }

        if (! hash_equals(Str::lower($invitation->email), Str::lower($user->email))) {
            throw ValidationException::withMessages([
                'token' => 'This invitation was issued to a different email address.',
            ]);
        }

        return DB::transaction(function () use ($invitation, $user): Company {
            CompanyInvitation::withoutCompanyScope(function () use ($invitation, $user): void {
                CompanyUser::query()->updateOrCreate(
                    ['company_id' => $invitation->company_id, 'user_id' => $user->getKey()],
                    ['role_id' => $invitation->role_id],
                );

                $invitation->forceFill(['accepted_at' => Carbon::now()])->save();
            });

            if ($user->current_company_id === null) {
                $user->forceFill(['current_company_id' => $invitation->company_id])->save();
            }

            /** @var Company $company */
            $company = $invitation->company;

            return $company;
        });
    }

    private function alreadyMember(Company $company, string $email): bool
    {
        return CompanyUser::query()
            ->where('company_id', $company->getKey())
            ->whereHas('user', fn ($q) => $q->whereRaw('LOWER(email) = ?', [$email]))
            ->exists();
    }
}
