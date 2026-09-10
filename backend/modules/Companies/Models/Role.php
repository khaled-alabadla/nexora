<?php

declare(strict_types=1);

namespace Modules\Companies\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A system-defined role (Phase 1 has no per-company custom roles).
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property int $level
 * @property bool $is_system
 */
final class Role extends Model
{
    public const OWNER = 'owner';

    public const ADMINISTRATOR = 'administrator';

    public const ACCOUNTANT = 'accountant';

    public const SALES_MANAGER = 'sales-manager';

    public const SALES_REP = 'sales-rep';

    public const INVENTORY_MANAGER = 'inventory-manager';

    public const PURCHASING_MANAGER = 'purchasing-manager';

    public const EMPLOYEE = 'employee';

    // Roles are seed data only (never created from user input).
    protected $fillable = [
        'slug',
        'name',
        'level',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'bool',
        'level' => 'int',
    ];

    /** @return BelongsToMany<Permission, $this> */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    public function isOwner(): bool
    {
        return $this->slug === self::OWNER;
    }
}
