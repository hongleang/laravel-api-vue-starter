<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexUsersRequest extends FormRequest
{
    public const SORTABLE_COLUMNS = ['first_name', 'last_name', 'email', 'created_at'];

    public const PER_PAGE_OPTIONS = [10, 25, 50];

    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', User::class) ?? false;
    }

    /**
     * Query Builder allow-lists filters and sorts; these rules turn bad values into 422s.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'filter' => ['sometimes', 'array'],
            'filter.search' => ['nullable', 'string', 'max:100'],
            'filter.role' => ['nullable', Rule::enum(Role::class)],
            'sort' => ['nullable', 'string', Rule::in([
                ...self::SORTABLE_COLUMNS,
                ...array_map(fn (string $column): string => '-'.$column, self::SORTABLE_COLUMNS),
            ])],
            'per_page' => ['nullable', 'integer', Rule::in(self::PER_PAGE_OPTIONS)],
        ];
    }

    public function perPage(): int
    {
        return $this->integer('per_page', self::PER_PAGE_OPTIONS[0]);
    }
}
