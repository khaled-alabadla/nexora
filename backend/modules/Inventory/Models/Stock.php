<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use App\Support\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Inventory\Database\Factories\StockFactory;
use Modules\Products\Models\Product;

/**
 * A maintained projection of SUM(inventory_movements.quantity) for one
 * (company, product, warehouse) triple — the ledger is the source of truth
 * (PHASE-2-PLAN.md §7.1). Written only by
 * `Modules\Inventory\Services\InventoryLedger` and `inventory:reconcile`.
 *
 * @property int $id
 * @property int $company_id
 * @property int $product_id
 * @property int $warehouse_id
 * @property string $quantity
 * @property \Carbon\CarbonInterface|null $created_at
 * @property \Carbon\CarbonInterface|null $updated_at
 */
final class Stock extends Model
{
    use BelongsToCompany;

    /** @use HasFactory<StockFactory> */
    use HasFactory;

    protected $table = 'stock';

    // company_id is forced by BelongsToCompany — never client-settable.
    // quantity is never mass-assigned: InventoryLedger is the only writer.
    protected $fillable = [
        'product_id',
        'warehouse_id',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    protected static function newFactory(): StockFactory
    {
        return StockFactory::new();
    }
}
