<?php

declare(strict_types=1);

namespace Modules\Inventory\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\Companies\Models\Company;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\InventoryLedger;
use Modules\Products\Models\Product;
use Tests\TestCase;
use Throwable;

/**
 * Real MySQL concurrency, deliberately NOT using RefreshDatabase: that trait
 * wraps every test in one uncommitted transaction on the default connection,
 * which would make fixtures invisible to a genuinely separate connection —
 * defeating the whole point of this file. Every test here commits its own
 * data and cleans it up in tearDown() instead (see PHASE-2-PLAN.md §6/§8 —
 * "dedicated non-RefreshDatabase concurrency tests").
 *
 * PHP is single-threaded, so "concurrent" here means: connection A opens a
 * transaction and takes a lock without committing, then connection B (a
 * second, independent PDO session against the same database) is proven to
 * block on that lock rather than silently reading around it — the same
 * guarantee that prevents a lost update between two real simultaneous
 * requests. This is the standard way to test pessimistic locking
 * deterministically without forking processes.
 */
final class StockConcurrencyTest extends TestCase
{
    private const SECONDARY_CONNECTION = 'mysql_concurrency_secondary';

    private ?Company $company = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Self-contained regardless of run order: RefreshDatabase (used by
        // every other test) is what normally guarantees the schema exists.
        Artisan::call('migrate', ['--force' => true]);

        config(['database.connections.'.self::SECONDARY_CONNECTION => config('database.connections.mysql')]);
    }

    protected function tearDown(): void
    {
        DB::purge(self::SECONDARY_CONNECTION);

        if ($this->company !== null) {
            $this->company->delete(); // cascades to products/warehouses/movements/stock
        }

        parent::tearDown();
    }

    public function test_a_locked_existing_stock_row_blocks_a_concurrent_writer_instead_of_losing_an_update(): void
    {
        $stockId = $this->seedStockRow();

        $primary = DB::connection('mysql');
        $secondary = DB::connection(self::SECONDARY_CONNECTION);
        $secondary->statement('SET SESSION innodb_lock_wait_timeout = 1');

        $primary->beginTransaction();
        $primary->table('stock')->where('id', $stockId)->lockForUpdate()->first();

        $blockedOnLock = false;

        try {
            try {
                $secondary->table('stock')->where('id', $stockId)->lockForUpdate()->first();
            } catch (Throwable $e) {
                $blockedOnLock = str_contains($e->getMessage(), 'Lock wait timeout exceeded');
            }
        } finally {
            // Always release the primary's lock before asserting — an
            // assertion failure here must never leave an open transaction
            // holding a row lock on the shared default connection, which
            // would hang every later test that touches `stock`.
            $primary->commit();
        }

        $this->assertTrue(
            $blockedOnLock,
            'A second connection must block (and eventually time out) on the row InventoryLedger has locked, never read past it.',
        );

        // Now unblocked: the same row the second connection couldn't reach
        // a moment ago is reachable immediately once the lock is released.
        $row = $secondary->table('stock')->where('id', $stockId)->first();
        $this->assertNotNull($row);
    }

    public function test_two_racing_first_movements_for_a_brand_new_pair_never_create_duplicate_stock_rows(): void
    {
        [$company] = companyWithOwner();
        $this->company = $company;
        $product = withoutTenantScope(fn () => Product::factory()->for($company)->create());
        $warehouse = withoutTenantScope(fn () => Warehouse::factory()->for($company)->create());

        $primary = DB::connection('mysql');
        $secondary = DB::connection(self::SECONDARY_CONNECTION);
        $secondary->statement('SET SESSION innodb_lock_wait_timeout = 1');

        $insertRow = fn () => [
            'company_id' => $company->id,
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // No stock row exists yet for this pair. Connection A inserts the
        // first row but does not commit — exactly the moment
        // InventoryLedger::lockedStockRow() is exposed to a race.
        $primary->beginTransaction();
        $primary->table('stock')->insert($insertRow());

        $blockedOrRejected = false;

        try {
            // Connection B races to create the same row. It must either
            // block until A resolves (then fail with a duplicate-key error
            // once A commits) or time out waiting — never silently succeed
            // and produce a second row for the same (company, product,
            // warehouse) triple.
            $secondary->table('stock')->insert($insertRow());
        } catch (Throwable) {
            $blockedOrRejected = true;
        }

        $primary->commit();

        $this->assertTrue(
            $blockedOrRejected,
            'A second connection racing to insert the first stock row for the same pair must never succeed independently of the first.',
        );

        $count = DB::table('stock')
            ->where('company_id', $company->id)
            ->where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->count();

        $this->assertSame(
            1,
            $count,
            "Exactly one stock row must exist for the pair — the unique constraint plus InventoryLedger's catch-and-retry is what guarantees this in production.",
        );
    }

    private function seedStockRow(): int
    {
        [$company, $owner] = companyWithOwner();
        $this->company = $company;
        $product = withoutTenantScope(fn () => Product::factory()->for($company)->create());
        $warehouse = withoutTenantScope(fn () => Warehouse::factory()->for($company)->create());

        actingInCompany($owner, $company);
        app(InventoryLedger::class)->record($product, $warehouse, InventoryMovement::TYPE_PURCHASE, '10.0000');

        return Stock::query()
            ->where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->value('id');
    }
}
