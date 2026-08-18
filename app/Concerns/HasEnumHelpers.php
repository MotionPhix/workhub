<?php

namespace App\Concerns;

/**
 * Shared helpers for the application's backed enums.
 *
 * Provides the array shapes the React frontend expects for select inputs and
 * badges, so pages never need to hardcode option lists.
 *
 * @mixin \BackedEnum
 */
trait HasEnumHelpers
{
    /**
     * The human readable label for the case.
     */
    abstract public function label(): string;

    /**
     * All backing values for the enum.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * The cases formatted for a select input.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }

    /**
     * Determine whether the case matches any of the given cases.
     */
    public function in(self ...$cases): bool
    {
        return in_array($this, $cases, true);
    }
}
