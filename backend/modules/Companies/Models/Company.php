<?php

declare(strict_types=1);

namespace Modules\Companies\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Companies\Database\Factories\CompanyFactory;

/**
 * A tenant. Users belong to many companies through `company_user`.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $status
 * @property \Carbon\CarbonInterface|null $created_at
 * @property \Carbon\CarbonInterface|null $updated_at
 * @property \Carbon\CarbonInterface|null $deleted_at
 */
final class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    /** Client-settable attributes only. company_id-style fields are never here. */
    protected $fillable = [
        'name',
        'slug',
    ];

    /** @return HasMany<CompanyUser, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(CompanyUser::class);
    }

    /** @return BelongsToMany<User, $this, CompanyUser> */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_user')
            ->using(CompanyUser::class)
            ->withPivot(['role_id', 'id'])
            ->withTimestamps();
    }

    /** @return HasMany<CompanyInvitation, $this> */
    public function invitations(): HasMany
    {
        return $this->hasMany(CompanyInvitation::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    protected static function newFactory(): CompanyFactory
    {
        return CompanyFactory::new();
    }
}
