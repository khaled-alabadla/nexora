<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests;

use App\Support\Tenancy\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Inventory\Models\InventoryMovement;

/**
 * `POST /inventory/adjustments` (PHASE-2-PLAN.md §5): one warehouse, one
 * type (`adjustment`|`damage`), one or more product lines. Each line becomes
 * its own `InventoryLedger::record()` call — this class only validates the
 * request shape; the negative-stock guard, force-scoping, and quantity-sign
 * check are enforced again (authoritatively) by the ledger itself.
 */
final class StoreAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route middleware enforces permission:inventory.adjust
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = app(CompanyContext::class)->id();

        return [
            'warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')->where('company_id', $companyId),
            ],
            'type' => ['required', Rule::in([InventoryMovement::TYPE_ADJUSTMENT, InventoryMovement::TYPE_DAMAGE])],
            'force' => ['sometimes', 'boolean'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('company_id', $companyId)->whereNull('deleted_at'),
            ],
            'lines.*.quantity_delta' => [
                'required',
                'numeric',
                'not_in:0',
                'max:99999999999999.9999',
                'min:-99999999999999.9999',
                // damage always decreases stock — InventoryLedger enforces
                // this too, but a client should get a clean 422 here rather
                // than a raw exception from the service layer.
                ...($this->input('type') === InventoryMovement::TYPE_DAMAGE ? ['lt:0'] : []),
            ],
            'lines.*.unit_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999999999.9999'],
            'lines.*.reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
