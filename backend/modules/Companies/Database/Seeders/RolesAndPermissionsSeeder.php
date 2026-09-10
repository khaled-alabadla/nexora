<?php

declare(strict_types=1);

namespace Modules\Companies\Database\Seeders;

use App\Support\Authorization\Permissions;
use Illuminate\Database\Seeder;
use Modules\Companies\Models\Permission;
use Modules\Companies\Models\Role;

/**
 * Idempotent. Runs on every `migrate --seed`; safe to re-run.
 */
final class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * slug => [name, level]
     */
    private const ROLES = [
        Role::OWNER => ['Owner', 100],
        Role::ADMINISTRATOR => ['Administrator', 80],
        Role::ACCOUNTANT => ['Accountant', 50],
        Role::SALES_MANAGER => ['Sales Manager', 50],
        Role::INVENTORY_MANAGER => ['Inventory Manager', 50],
        Role::PURCHASING_MANAGER => ['Purchasing Manager', 50],
        Role::SALES_REP => ['Sales Representative', 20],
        Role::EMPLOYEE => ['Employee', 10],
    ];

    /**
     * role slug => permission slugs. Owner is omitted — it implicitly holds all.
     *
     * @return array<string, list<string>>
     */
    private function rolePermissions(): array
    {
        $managerView = [Permissions::MEMBER_VIEW];

        return [
            Role::ADMINISTRATOR => [
                Permissions::COMPANY_UPDATE,
                Permissions::MEMBER_VIEW,
                Permissions::MEMBER_INVITE,
                Permissions::MEMBER_ROLE_UPDATE,
                Permissions::MEMBER_REMOVE,
            ],
            Role::ACCOUNTANT => $managerView,
            Role::SALES_MANAGER => $managerView,
            Role::INVENTORY_MANAGER => $managerView,
            Role::PURCHASING_MANAGER => $managerView,
            Role::SALES_REP => [],
            Role::EMPLOYEE => [],
        ];
    }

    public function run(): void
    {
        foreach (Permissions::catalog() as $slug => $meta) {
            Permission::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $meta['name'], 'group' => $meta['group']],
            );
        }

        foreach (self::ROLES as $slug => [$name, $level]) {
            $role = Role::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'level' => $level, 'is_system' => true],
            );

            $permissionIds = Permission::query()
                ->whereIn('slug', $this->rolePermissions()[$slug] ?? [])
                ->pluck('id');

            $role->permissions()->sync($permissionIds);
        }
    }
}
