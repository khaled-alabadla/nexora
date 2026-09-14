<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use App\Support\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Inventory\Database\Factories\InventoryMovementFactory;
use Modules\Products\Models\Product;

/**
 * One line of the append-only inventory ledger — the source of truth for
 * stock. The only writer is `Modules\Inventory\Services\InventoryLedger`
 * (PHASE-2-PLAN.md §5); never `update()`/`delete()` a row here — it has no
 * `updated_at` and no soft deletes because it is never meant to change once
 * written.
 *
 * @property int $id
 * @property int $company_id
 * @property int $product_id
 * @property int $warehouse_id
 * @property string $type
 * @property string $quantity signed: +in / -out
 * @property string|null $unit_cost
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string|null $note
 * @property int|null $created_by
 * @property \Carbon\CarbonInterface|null $created_at
 */
final class InventoryMovement extends Model
{
    use BelongsToCompany;

    /** @use HasFactory<InventoryMovementFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    public const TYPE_PURCHASE = 'purchase';

    public const TYPE_SALE = 'sale';

    public const TYPE_RETURN = 'return';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_TRANSFER_IN = 'transfer_in';

    public const TYPE_TRANSFER_OUT = 'transfer_out';

    public const TYPE_DAMAGE = 'damage';

    /**
     * Movement types allowed to carry `force: true` past the negative-stock
     * guard (PHASE-2-PLAN §7.4) — a true-up correction path, never a loophole
     * for ordinary stock-out operations like sales or transfers.
     */
    public const FORCEABLE_TYPES = [self::TYPE_ADJUSTMENT, self::TYPE_DAMAGE];

    /** @return list<string> */
    public static function types(): array
    {
        return [
            self::TYPE_PURCHASE,
            self::TYPE_SALE,
            self::TYPE_RETURN,
            self::TYPE_ADJUSTMENT,
            self::TYPE_TRANSFER_IN,
            self::TYPE_TRANSFER_OUT,
            self::TYPE_DAMAGE,
        ];
    }

    /**
     * The sign a type's `quantity` must carry, so the ledger's `type` stays
     * semantically meaningful (COGS/valuation reporting keys off it) instead
     * of only being internally consistent with the stock arithmetic.
     * `adjustment` has no fixed direction — it's a true-up correction that
     * can move stock either way.
     *
     * @return 1|-1|null 1 = must be positive, -1 = must be negative, null = either
     */
    public static function requiredSign(string $type): ?int
    {
        return match ($type) {
            self::TYPE_PURCHASE, self::TYPE_RETURN, self::TYPE_TRANSFER_IN => 1,
            self::TYPE_SALE, self::TYPE_TRANSFER_OUT, self::TYPE_DAMAGE => -1,
            self::TYPE_ADJUSTMENT => null,
            default => null,
        };
    }

    // company_id is forced by BelongsToCompany — never client-settable.
    // Every other column is set exclusively by InventoryLedger; there is
    // intentionally no public write endpoint or FormRequest for this model.
    protected $fillable = [
        'product_id',
        'warehouse_id',
        'type',
        'quantity',
        'unit_cost',
        'reference_type',
        'reference_id',
        'note',
        'created_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
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

    protected static function newFactory(): InventoryMovementFactory
    {
        return InventoryMovementFactory::new();
    }
}
