<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests;

use App\Support\Tenancy\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Inventory\Models\Warehouse;

final class UpdateWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route middleware enforces permission:warehouse.update
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = app(CompanyContext::class)->id();
        /** @var Warehouse $warehouse */
        $warehouse = $this->route('warehouse');

        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('warehouses', 'name')->where('company_id', $companyId)->ignore($warehouse->getKey()),
            ],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
