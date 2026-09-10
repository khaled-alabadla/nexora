<?php

declare(strict_types=1);

namespace Modules\Companies\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route middleware enforces permission:company.update
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
