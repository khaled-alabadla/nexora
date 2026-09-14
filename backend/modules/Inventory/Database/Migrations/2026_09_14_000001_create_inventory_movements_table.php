<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            // restrictOnDelete, not cascade: the ledger is an immutable audit
            // trail (PHASE-2-PLAN §7.5) — a product/warehouse with movement
            // history may never be hard-deleted out from under it.
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            // purchase|sale|return|adjustment|transfer_in|transfer_out|damage
            // — purchase/sale/return are modeled now but unreachable until
            // Phase 3/4 (PHASE-2-PLAN §1).
            $table->string('type', 20);
            // Signed: +in / -out (PHASE-2-PLAN §7.2). Current stock is
            // SUM(quantity) per (product, warehouse) — see the `stock` table.
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_cost', 18, 4)->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            // Append-only: created_at only, no updated_at — see
            // Modules\Inventory\Models\InventoryMovement.
            $table->timestamp('created_at')->useCurrent();

            // No separate (company_id, product_id) index: it would be a
            // strict left-prefix of the composite below, so MySQL can
            // already satisfy any such query from it — a second index would
            // add write overhead to every insert for zero query benefit.
            $table->index(['company_id', 'product_id', 'warehouse_id']);
            $table->index(['warehouse_id']);
            $table->index(['type']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
