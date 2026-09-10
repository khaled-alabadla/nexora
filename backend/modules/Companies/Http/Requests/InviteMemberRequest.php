<?php

declare(strict_types=1);

namespace Modules\Companies\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Companies\Models\Role;

final class InviteMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route middleware enforces permission:member.invite
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'role' => [
                'required',
                'string',
                Rule::exists('roles', 'slug')->where('is_system', true),
                Rule::notIn([Role::OWNER]),
            ],
        ];
    }
}
