<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests;

use App\Support\Tenancy\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route middleware enforces permission:warehouse.create
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = app(CompanyContext::class)->id();

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('warehouses', 'name')->where('company_id', $companyId),
            ],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
