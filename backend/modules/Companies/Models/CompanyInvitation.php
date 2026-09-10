<?php

declare(strict_types=1);

namespace Modules\Companies\Models;

use App\Models\User;
use App\Support\Tenancy\BelongsToCompany;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Companies\Database\Factories\CompanyInvitationFactory;

/**
 * A pending invitation for an email address to join a company with a role.
 *
 * Tenant-scoped (BelongsToCompany): list/create always run inside an active
 * company. Acceptance, which happens with no active company, uses the single
 * sanctioned bypass `CompanyInvitation::withoutCompanyScope(...)`.
 *
 * @property int $id
 * @property int $company_id
 * @property int $role_id
 * @property int $invited_by
 * @property string $email
 * @property string $token sha256 hash of the emailed token — never the raw value
 * @property CarbonInterface $expires_at
 * @property CarbonInterface|null $accepted_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
final class CompanyInvitation extends Model
{
    use BelongsToCompany;

    /** @use HasFactory<CompanyInvitationFactory> */
    use HasFactory;

    // Written only by CompanyInvitationService; company_id comes from the trait.
    protected $guarded = ['id', 'company_id'];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    protected $hidden = [
        'token',
    ];

    /** @return BelongsTo<Role, $this> */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** @return BelongsTo<User, $this> */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null && ! $this->isExpired();
    }

    protected static function newFactory(): CompanyInvitationFactory
    {
        return CompanyInvitationFactory::new();
    }
}
