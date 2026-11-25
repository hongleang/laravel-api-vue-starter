<?php

namespace App\Data;

use App\Enums\Role;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\AfterOrEqual;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * The user report's query string: a created-at date range (inclusive) and a role.
 */
#[MapName(SnakeCaseMapper::class)]
class UserReportFilters extends Data
{
    public const PER_PAGE_OPTIONS = [10, 25, 50];

    public function __construct(
        #[DateFormat('Y-m-d'), WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d')]
        public ?CarbonImmutable $from = null,
        #[DateFormat('Y-m-d'), AfterOrEqual('from'), WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d')]
        public ?CarbonImmutable $to = null,
        public ?Role $role = null,
        #[In(self::PER_PAGE_OPTIONS)]
        public int $perPage = self::PER_PAGE_OPTIONS[0],
    ) {}

    /**
     * Drop empty query values so the constructor defaults apply.
     *
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    public static function prepareForPipeline(array $properties): array
    {
        return array_filter(
            $properties,
            fn (mixed $value): bool => $value !== null && $value !== '',
        );
    }
}
