<?php

declare(strict_types=1);

namespace Modules\Products\Http\Requests;

use App\Support\Tenancy\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Products\Models\ProductCategory;

final class UpdateProductCategoryRequest extends FormRequest
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
        /** @var ProductCategory $category */
        $category = $this->route('category');

        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('product_categories', 'name')
                    ->where('company_id', $companyId)
                    ->ignore($category->getKey()),
            ],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('product_categories', 'id')->where('company_id', $companyId),
            ],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('parent_id')) {
                return;
            }

            /** @var ProductCategory $category */
            $category = $this->route('category');
            $parentId = (int) $this->input('parent_id');

            if ($this->createsCycle($category->getKey(), $parentId)) {
                $validator->errors()->add('parent_id', 'A category cannot be its own ancestor.');
            }
        });
    }

    /**
     * Would setting $category's parent to $proposedParentId create a cycle?
     * Walks $proposedParentId's ancestor chain looking for $categoryId — not
     * just a direct self-parent, but any depth (A → B → C → A).
     */
    private function createsCycle(int $categoryId, int $proposedParentId): bool
    {
        $visited = [];
        $currentId = $proposedParentId;

        while ($currentId !== null) {
            if ($currentId === $categoryId) {
                return true;
            }

            // Already-corrupted data upstream — nothing more this request can say.
            if (isset($visited[$currentId])) {
                return false;
            }

            $visited[$currentId] = true;
            $currentId = ProductCategory::query()->whereKey($currentId)->value('parent_id');
        }

        return false;
    }
}
