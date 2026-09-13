<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()
                ->constrained('product_categories')->nullOnDelete();
            $table->string('sku');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('barcode')->nullable();
            $table->string('unit')->default('pcs');
            $table->decimal('cost_price', 18, 4)->default(0);
            $table->decimal('selling_price', 18, 4)->default(0);
            $table->decimal('tax_rate', 18, 4)->default(0);
            $table->decimal('minimum_stock', 18, 4)->default(0);
            $table->string('status', 20)->default('active'); // active | inactive
            $table->timestamps();
            $table->softDeletes();

            // A soft-deleted product keeps its SKU reserved (see PHASE-2-PLAN §7.5) —
            // MySQL's unique index already excludes multiple NULLs, so a deleted
            // product's slot cannot be reused while it still exists in the table.
            $table->unique(['company_id', 'sku']);
            $table->unique(['company_id', 'barcode']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
