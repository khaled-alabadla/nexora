<?php

declare(strict_types=1);

namespace Modules\Companies\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Companies\Models\Role;

final class UpdateMemberRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route middleware enforces permission:member.role.update
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'role' => [
                'required',
                'string',
                Rule::exists('roles', 'slug')->where('is_system', true),
                Rule::notIn([Role::OWNER]),
            ],
        ];
    }
}
