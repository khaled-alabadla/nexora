<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Handles the signed link from the verification email. The link is opened
 * directly in the user's browser (no SPA session guaranteed), so trust comes
 * from the URL signature plus the email hash — never from an auth guard.
 */
final class VerifyEmailController
{
    public function __invoke(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = User::query()->findOrFail($id);

        abort_unless(
            hash_equals(sha1($user->getEmailForVerification()), $hash),
            403,
            'Invalid verification link.',
        );

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return redirect()->away(
            rtrim((string) config('app.frontend_url'), '/').'/login?verified=1',
        );
    }
}
