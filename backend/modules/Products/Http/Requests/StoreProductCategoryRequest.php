<?php

declare(strict_types=1);

namespace Modules\Products\Http\Requests;

use App\Support\Tenancy\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreProductCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route middleware enforces permission:category.manage
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
                Rule::unique('product_categories', 'name')->where('company_id', $companyId),
            ],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('product_categories', 'id')->where('company_id', $companyId),
            ],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ];
    }
}
