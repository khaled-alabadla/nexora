<?php

declare(strict_types=1);

namespace App\Support\Authorization;

use Modules\Companies\Models\Role;

/**
 * Resolves the effective permission slugs for a role. Owner implicitly holds
 * every permission in the catalog; every other role holds exactly what the
 * role_permission table grants.
 */
final class GrantedPermissions
{
    /**
     * @return list<string>
     */
    public static function for(?Role $role): array
    {
        if ($role === null) {
            return [];
        }

        if ($role->isOwner()) {
            return Permissions::slugs();
        }

        return array_values(
            $role->permissions()
                ->pluck('slug')
                ->map(static fn (mixed $slug): string => (string) $slug)
                ->all()
        );
    }
}
