<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Modules\Companies\Database\Seeders\RolesAndPermissionsSeeder;

/**
 * Roles and permissions are reference data — effectively part of the schema.
 * Seeding them in a migration means they are always present (including in the
 * test database) at zero per-test cost. The seeder is idempotent, so running
 * `migrate --seed` (which also invokes it) is harmless.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new RolesAndPermissionsSeeder)->run();
    }

    public function down(): void
    {
        // role_permission cascades; roles/permissions are safe to leave.
    }
};
