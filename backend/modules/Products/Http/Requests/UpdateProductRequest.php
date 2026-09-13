<?php

declare(strict_types=1);

namespace Modules\Products\Http\Requests;

use App\Support\Tenancy\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Products\Models\Product;

final class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route middleware enforces permission:product.update
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = app(CompanyContext::class)->id();
        /** @var Product $product */
        $product = $this->route('product');

        return [
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('product_categories', 'id')->where('company_id', $companyId),
            ],
            'sku' => [
                'sometimes',
                'string',
                'max:64',
                Rule::unique('products', 'sku')->where('company_id', $companyId)->ignore($product->getKey()),
            ],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'barcode' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('products', 'barcode')->where('company_id', $companyId)->ignore($product->getKey()),
            ],
            'unit' => ['sometimes', 'string', 'max:32'],
            'cost_price' => ['sometimes', 'numeric', 'min:0', 'max:99999999999999.9999'],
            'selling_price' => ['sometimes', 'numeric', 'min:0', 'max:99999999999999.9999'],
            'tax_rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'minimum_stock' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ];
    }
}
