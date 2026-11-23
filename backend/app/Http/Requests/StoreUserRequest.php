<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'mobile_phone' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::enum(Role::class)],
        ];
    }

    public function role(): Role
    {
        return Role::from($this->string('role')->toString());
    }

    /**
     * @return array{first_name: string, last_name: string, mobile_phone: string|null, email: string}
     */
    public function userAttributes(): array
    {
        /** @var array{first_name: string, last_name: string, mobile_phone: string|null, email: string} $attributes */
        $attributes = $this->safe()->only(['first_name', 'last_name', 'mobile_phone', 'email']);

        return $attributes;
    }
}
