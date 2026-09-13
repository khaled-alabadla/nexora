<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Modules\Companies\Database\Seeders\RolesAndPermissionsSeeder;

/**
 * Phase 2 added product/warehouse/inventory permissions and extended the role
 * grants (see docs/PHASE-2-PLAN.md §7). The seeder is idempotent
 * (updateOrCreate + sync), so re-running it here is safe and picks up the new
 * catalogue on every environment that already ran the original seed migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new RolesAndPermissionsSeeder)->run();
    }

    public function down(): void
    {
        // Permissions/roles are reference data; nothing to roll back safely.
    }
};
