<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use App\Support\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Database\Factories\WarehouseFactory;

/**
 * A stock location within a company. Exactly one warehouse is the company's
 * default at any time once it has at least one — enforced by
 * WarehouseService, not the database (MySQL has no partial unique index; see
 * docs/PHASE-2-PLAN.md §7.9).
 *
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $location
 * @property bool $is_default
 * @property string $status
 * @property \Carbon\CarbonInterface|null $created_at
 * @property \Carbon\CarbonInterface|null $updated_at
 */
final class Warehouse extends Model
{
    use BelongsToCompany;

    /** @use HasFactory<WarehouseFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    // company_id is forced by BelongsToCompany — never client-settable.
    // is_default is never mass-assigned either: WarehouseService is the only
    // writer, so the one-default invariant can't be bypassed via a stray field.
    protected $fillable = [
        'name',
        'location',
        'status',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_default' => false,
        'status' => self::STATUS_ACTIVE,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    protected static function newFactory(): WarehouseFactory
    {
        return WarehouseFactory::new();
    }
}
