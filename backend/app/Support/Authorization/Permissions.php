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
        ];
    }

    /** @return list<string> */
    public static function slugs(): array
    {
        return array_keys(self::catalog());
    }
}
