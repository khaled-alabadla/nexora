<?php

declare(strict_types=1);

namespace Modules\Products\Models;

use App\Support\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Products\Database\Factories\ProductCategoryFactory;

/**
 * A product category, optionally nested under a parent (no enforced depth —
 * see docs/PHASE-2-PLAN.md §7.3).
 *
 * @property int $id
 * @property int $company_id
 * @property int|null $parent_id
 * @property string $name
 * @property string $status
 * @property \Carbon\CarbonInterface|null $created_at
 * @property \Carbon\CarbonInterface|null $updated_at
 */
final class ProductCategory extends Model
{
    use BelongsToCompany;

    /** @use HasFactory<ProductCategoryFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    // company_id is forced by BelongsToCompany — never client-settable.
    protected $fillable = [
        'parent_id',
        'name',
        'status',
    ];

    /**
     * Mirrors the migration's column default so a freshly-created instance
     * reflects `status` in memory without an extra round-trip.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];

    /** @return BelongsTo<ProductCategory, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<ProductCategory, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    protected static function newFactory(): ProductCategoryFactory
    {
        return ProductCategoryFactory::new();
    }
}
