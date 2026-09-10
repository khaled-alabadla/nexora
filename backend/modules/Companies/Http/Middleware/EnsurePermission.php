<?php

declare(strict_types=1);

namespace Modules\Companies\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route middleware: `permission:member.invite`.
 *
 * Checks the permission against the user's *current* company (bound by
 * SetActiveCompany). Must run after `auth` and `active-company`.
 */
final class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->hasPermission($permission)) {
            abort(403, 'This action is not permitted for your role.');
        }

        return $next($request);
    }
}
