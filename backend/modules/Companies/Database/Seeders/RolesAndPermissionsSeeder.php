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
        $fullInventory = [
            Permissions::PRODUCT_VIEW,
            Permissions::PRODUCT_CREATE,
            Permissions::PRODUCT_UPDATE,
            Permissions::PRODUCT_DELETE,
            Permissions::CATEGORY_MANAGE,
            Permissions::WAREHOUSE_VIEW,
            Permissions::WAREHOUSE_CREATE,
            Permissions::WAREHOUSE_UPDATE,
            Permissions::WAREHOUSE_DELETE,
            Permissions::INVENTORY_VIEW,
            Permissions::INVENTORY_ADJUST,
            Permissions::INVENTORY_TRANSFER,
        ];

        // product.view + inventory.view only — the common "can look, can't touch" grant.
        $catalogView = [Permissions::PRODUCT_VIEW, Permissions::INVENTORY_VIEW];

        return [
            Role::ADMINISTRATOR => [
                Permissions::COMPANY_UPDATE,
                Permissions::MEMBER_VIEW,
                Permissions::MEMBER_INVITE,
                Permissions::MEMBER_ROLE_UPDATE,
                Permissions::MEMBER_REMOVE,
                ...$fullInventory,
            ],
            Role::ACCOUNTANT => [Permissions::MEMBER_VIEW, ...$catalogView],
            Role::SALES_MANAGER => [Permissions::MEMBER_VIEW, ...$catalogView],
            Role::INVENTORY_MANAGER => [Permissions::MEMBER_VIEW, ...$fullInventory],
            Role::PURCHASING_MANAGER => [
                Permissions::MEMBER_VIEW,
                ...$catalogView,
                Permissions::WAREHOUSE_VIEW,
            ],
            Role::SALES_REP => [Permissions::PRODUCT_VIEW],
            Role::EMPLOYEE => [Permissions::PRODUCT_VIEW],
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
