<?php

declare(strict_types=1);

namespace Modules\Companies\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Support\Tenancy\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Companies\Http\Requests\UpdateMemberRoleRequest;
use Modules\Companies\Http\Resources\MemberResource;
use Modules\Companies\Models\CompanyUser;
use Modules\Companies\Models\Role;
use Modules\Companies\Services\CompanyMembershipService;

/**
 * Members of the active company. Every query is scoped to CompanyContext::id();
 * a {user} that is not a member of the active company resolves to 404.
 */
final class MemberController
{
    public function __construct(private readonly CompanyContext $context) {}

    public function index(): JsonResponse
    {
        $members = CompanyUser::query()
            ->where('company_id', $this->context->id())
            ->with(['user', 'role'])
            ->get()
            ->sortBy(fn (CompanyUser $m): string => (string) $m->user?->name)
            ->values();

        return ApiResponse::make(MemberResource::collection($members));
    }

    public function update(UpdateMemberRoleRequest $request, int $user, CompanyMembershipService $memberships): JsonResponse
    {
        $company = $this->context->company();
        $member = $this->memberOrFail($user);

        $role = Role::query()->where('slug', (string) $request->string('role'))->firstOrFail();

        $membership = $memberships->changeRole($company, $member, $role);

        return ApiResponse::make(
            new MemberResource($membership->load(['user', 'role'])),
            'Member role updated.',
        );
    }

    public function destroy(Request $request, int $user, CompanyMembershipService $memberships): Response
    {
        $company = $this->context->company();
        $member = $this->memberOrFail($user);

        $memberships->removeMember($company, $member);

        return ApiResponse::noContent();
    }

    private function memberOrFail(int $userId): User
    {
        $membership = CompanyUser::query()
            ->where('company_id', $this->context->id())
            ->where('user_id', $userId)
            ->firstOrFail();

        /** @var User $user */
        $user = $membership->user()->firstOrFail();

        return $user;
    }
}
