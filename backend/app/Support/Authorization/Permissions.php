<?php

declare(strict_types=1);

namespace App\Support\Authorization;

/**
 * Central registry of every permission slug in the system.
 *
 * Seeded into the `permissions` table by RolesAndPermissionsSeeder and mapped
 * to roles there. Business modules add their own constants + entries here as
 * they land (products.*, invoices.*, …).
 */
final class Permissions
{
    // Company
    public const COMPANY_UPDATE = 'company.update';

    // Members
    public const MEMBER_VIEW = 'member.view';

    public const MEMBER_INVITE = 'member.invite';

    public const MEMBER_ROLE_UPDATE = 'member.role.update';

    public const MEMBER_REMOVE = 'member.remove';

    // Products (Phase 2)
    public const PRODUCT_VIEW = 'product.view';

    public const PRODUCT_CREATE = 'product.create';

    public const PRODUCT_UPDATE = 'product.update';

    public const PRODUCT_DELETE = 'product.delete';

    public const CATEGORY_MANAGE = 'category.manage';

    // Warehouses (Phase 2)
    public const WAREHOUSE_VIEW = 'warehouse.view';

    public const WAREHOUSE_CREATE = 'warehouse.create';

    public const WAREHOUSE_UPDATE = 'warehouse.update';

    public const WAREHOUSE_DELETE = 'warehouse.delete';

    // Inventory (Phase 2)
    public const INVENTORY_VIEW = 'inventory.view';

    public const INVENTORY_ADJUST = 'inventory.adjust';

    public const INVENTORY_TRANSFER = 'inventory.transfer';

    /**
     * slug => [name, group]
     *
     * @return array<string, array{name: string, group: string}>
     */
    public static function catalog(): array
    {
        return [
            self::COMPANY_UPDATE => ['name' => 'Update company settings', 'group' => 'company'],
            self::MEMBER_VIEW => ['name' => 'View members', 'group' => 'members'],
            self::MEMBER_INVITE => ['name' => 'Invite members', 'group' => 'members'],
            self::MEMBER_ROLE_UPDATE => ['name' => 'Change member roles', 'group' => 'members'],
            self::MEMBER_REMOVE => ['name' => 'Remove members', 'group' => 'members'],
            self::PRODUCT_VIEW => ['name' => 'View products', 'group' => 'products'],
            self::PRODUCT_CREATE => ['name' => 'Create products', 'group' => 'products'],
            self::PRODUCT_UPDATE => ['name' => 'Update products', 'group' => 'products'],
            self::PRODUCT_DELETE => ['name' => 'Delete products', 'group' => 'products'],
            self::CATEGORY_MANAGE => ['name' => 'Manage categories', 'group' => 'products'],
            self::WAREHOUSE_VIEW => ['name' => 'View warehouses', 'group' => 'inventory'],
            self::WAREHOUSE_CREATE => ['name' => 'Create warehouses', 'group' => 'inventory'],
            self::WAREHOUSE_UPDATE => ['name' => 'Update warehouses', 'group' => 'inventory'],
            self::WAREHOUSE_DELETE => ['name' => 'Delete warehouses', 'group' => 'inventory'],
            self::INVENTORY_VIEW => ['name' => 'View inventory & stock', 'group' => 'inventory'],
            self::INVENTORY_ADJUST => ['name' => 'Adjust stock', 'group' => 'inventory'],
            self::INVENTORY_TRANSFER => ['name' => 'Transfer stock between warehouses', 'group' => 'inventory'],
        ];
    }

    /** @return list<string> */
    public static function slugs(): array
    {
        return array_keys(self::catalog());
    }
}
