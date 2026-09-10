<?php

declare(strict_types=1);

namespace Modules\Companies\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Membership row: one user's role in one company.
 *
 * @property int $id
 * @property int $company_id
 * @property int $user_id
 * @property int $role_id
 * @property \Carbon\CarbonInterface|null $created_at
 * @property \Carbon\CarbonInterface|null $updated_at
 */
final class CompanyUser extends Pivot
{
    public $incrementing = true;

    protected $table = 'company_user';

    // Written only by CompanyMembershipService, never from request input.
    protected $guarded = ['id'];

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Role, $this> */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
