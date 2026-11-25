<?php

namespace App\Data;

use App\Models\User;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * One row of the user report, shared by the on-screen preview and the CSV export.
 */
#[MapOutputName(SnakeCaseMapper::class)]
class UserReportRow extends Data
{
    public function __construct(
        public int $id,
        public string $firstName,
        public string $lastName,
        public string $email,
        public ?string $mobilePhone,
        public string $role,
        public bool $verified,
        public string $createdAt,
    ) {}

    public static function fromUser(User $user): self
    {
        return new self(
            id: $user->id,
            firstName: $user->first_name,
            lastName: $user->last_name,
            email: $user->email,
            mobilePhone: $user->mobile_phone,
            role: $user->roles->pluck('name')->implode(', '),
            verified: $user->email_verified_at !== null,
            createdAt: $user->created_at?->toIso8601String() ?? '',
        );
    }

    /**
     * CSV cells, with a leading apostrophe on text a spreadsheet would run as a formula.
     *
     * @return array<string, int|string>
     */
    public function toCsvRow(): array
    {
        return [
            'ID' => $this->id,
            'First name' => self::safeCell($this->firstName),
            'Last name' => self::safeCell($this->lastName),
            'Email' => self::safeCell($this->email),
            'Mobile phone' => self::safeCell($this->mobilePhone ?? ''),
            'Role' => $this->role,
            'Verified' => $this->verified ? 'Yes' : 'No',
            'Created at' => $this->createdAt,
        ];
    }

    private static function safeCell(string $value): string
    {
        return $value !== '' && str_contains("=+-@\t\r", $value[0]) ? "'".$value : $value;
    }
}
