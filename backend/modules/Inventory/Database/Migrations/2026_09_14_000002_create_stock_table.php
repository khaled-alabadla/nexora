<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            // Maintained projection of SUM(inventory_movements.quantity) per
            // (product, warehouse) — the ledger stays the source of truth
            // (PHASE-2-PLAN §7.1). Only Modules\Inventory\Services\InventoryLedger
            // and the inventory:reconcile command ever write this table.
            $table->decimal('quantity', 18, 4)->default(0);
            $table->timestamps();

            // The row's existence for a (company, product, warehouse) triple is
            // itself part of the invariant InventoryLedger enforces: this real
            // unique constraint is what makes a race to create the first stock
            // row for a pair safe under any transaction isolation level (the
            // loser's INSERT blocks on the unique key, then fails cleanly, then
            // retries as a locked read) — unlike warehouses.is_default, which
            // has no DB constraint backing it and needed a named lock instead.
            $table->unique(['company_id', 'product_id', 'warehouse_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock');
    }
};
