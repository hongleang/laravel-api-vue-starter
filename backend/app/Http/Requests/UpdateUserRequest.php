<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->targetUser()) ?? false;
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
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->targetUser())],
            'role' => [
                // Only people who may manage roles can send one; a user editing themselves cannot.
                Rule::prohibitedIf(fn (): bool => ! ($this->user()?->can('assignRole', $this->targetUser()) ?? false)),
                'nullable',
                Rule::enum(Role::class),
            ],
        ];
    }

    /**
     * The new role, or null to leave the user's roles unchanged.
     */
    public function role(): ?Role
    {
        return $this->filled('role') ? Role::from($this->string('role')->toString()) : null;
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

    public function targetUser(): User
    {
        /** @var User $user */
        $user = $this->route('user');

        return $user;
    }
}
