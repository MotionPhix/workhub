<?php

namespace Database\Factories;

use App\Enums\CycleStatus;
use App\Models\OutreachCycle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * @extends Factory<OutreachCycle>
 */
class OutreachCycleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => Auth::id() ?? User::factory(),
            ...$this->weekAttributes(Carbon::today()->startOfWeek()),
            'status' => CycleStatus::Active,
            'target_count' => 25,
            'notes' => null,
            'closed_at' => null,
        ];
    }

    /**
     * Place the cycle on the week containing the given date.
     */
    public function forWeek(Carbon $date): static
    {
        return $this->state(fn (array $attributes): array => $this->weekAttributes($date->copy()->startOfWeek()));
    }

    /**
     * Indicate that the cycle has not started yet.
     */
    public function planned(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CycleStatus::Planned,
        ]);
    }

    /**
     * Indicate that the cycle has been closed off.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CycleStatus::Completed,
            'closed_at' => Carbon::parse($attributes['ends_on'] ?? Carbon::today())->endOfDay(),
        ]);
    }

    /**
     * The date and naming attributes for the week starting on the given Monday.
     *
     * @return array<string, mixed>
     */
    protected function weekAttributes(Carbon $startsOn): array
    {
        $weekOfMonth = (int) ceil($startsOn->day / 7);

        return [
            'name' => "Week {$weekOfMonth}, ".$startsOn->format('F Y'),
            'starts_on' => $startsOn,
            'ends_on' => $startsOn->copy()->endOfWeek(),
            'month_key' => $startsOn->format('Y-m'),
            'week_of_month' => $weekOfMonth,
        ];
    }
}
