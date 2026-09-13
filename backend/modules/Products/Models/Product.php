<?php

declare(strict_types=1);

namespace Modules\Products\Models;

use App\Support\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Products\Database\Factories\ProductFactory;

/**
 * @property int $id
 * @property int $company_id
 * @property int|null $category_id
 * @property string $sku
 * @property string $name
 * @property string|null $description
 * @property string|null $barcode
 * @property string $unit
 * @property string $cost_price
 * @property string $selling_price
 * @property string $tax_rate
 * @property string $minimum_stock
 * @property string $status
 * @property \Carbon\CarbonInterface|null $created_at
 * @property \Carbon\CarbonInterface|null $updated_at
 * @property \Carbon\CarbonInterface|null $deleted_at
 */
final class Product extends Model
{
    use BelongsToCompany;

    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    // company_id is forced by BelongsToCompany — never client-settable.
    protected $fillable = [
        'category_id',
        'sku',
        'name',
        'description',
        'barcode',
        'unit',
        'cost_price',
        'selling_price',
        'tax_rate',
        'minimum_stock',
        'status',
    ];

    /**
     * Mirrors the migration's column defaults so a freshly-created instance
     * reflects them in memory without an extra round-trip.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'unit' => 'pcs',
        'cost_price' => 0,
        'selling_price' => 0,
        'tax_rate' => 0,
        'minimum_stock' => 0,
        'status' => self::STATUS_ACTIVE,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:4',
            'selling_price' => 'decimal:4',
            'tax_rate' => 'decimal:4',
            'minimum_stock' => 'decimal:4',
        ];
    }

    /** @return BelongsTo<ProductCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }
}
