<?php

namespace Database\Factories;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * @extends Factory<Goal>
 */
class GoalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = Carbon::today()->startOfMonth();

        return [
            'user_id' => Auth::id() ?? User::factory(),
            'period_start' => $start,
            'period_end' => $start->copy()->endOfMonth(),
            'prospect_target' => fake()->numberBetween(50, 150),
            'follow_up_target' => fake()->numberBetween(15, 40),
            'proposal_target' => fake()->numberBetween(5, 15),
            'client_target' => fake()->numberBetween(2, 5),
            'revenue_target' => fake()->randomElement([5_000_000, 8_000_000, 12_000_000, 18_000_000]),
            'currency' => 'MWK',
            'notes' => null,
        ];
    }

    /**
     * Set targets for a specific month.
     */
    public function forMonth(Carbon $date): static
    {
        return $this->state(function (array $attributes) use ($date): array {
            $start = $date->copy()->startOfMonth();

            return [
                'period_start' => $start,
                'period_end' => $start->copy()->endOfMonth(),
            ];
        });
    }

    /**
     * Set conservative targets for a quiet month.
     */
    public function conservative(): static
    {
        return $this->state(fn (array $attributes): array => [
            'prospect_target' => 40,
            'follow_up_target' => 10,
            'proposal_target' => 3,
            'client_target' => 1,
            'revenue_target' => 3_000_000,
        ]);
    }

    /**
     * Set ambitious targets for a peak month.
     */
    public function ambitious(): static
    {
        return $this->state(fn (array $attributes): array => [
            'prospect_target' => 200,
            'follow_up_target' => 50,
            'proposal_target' => 20,
            'client_target' => 8,
            'revenue_target' => 25_000_000,
        ]);
    }
}
