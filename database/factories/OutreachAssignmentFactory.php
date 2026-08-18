<?php

namespace Database\Factories;

use App\Enums\AssignmentStatus;
use App\Enums\OutreachStrategy;
use App\Models\Contact;
use App\Models\OutreachAssignment;
use App\Models\OutreachCycle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * @extends Factory<OutreachAssignment>
 */
class OutreachAssignmentFactory extends Factory
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
            'outreach_cycle_id' => OutreachCycle::factory(),
            'contact_id' => Contact::factory(),
            'status' => AssignmentStatus::Pending,
            'rank' => fake()->numberBetween(1, 25),
            'score' => fake()->numberBetween(20, 95),
            'reasons' => fake()->randomElements([
                'Never contacted before',
                'International NGO, commissions design regularly',
                'Referred by an existing client',
                'Website has not been updated in years',
                'Branding is inconsistent across channels',
                'Warm lead from an event',
            ], fake()->numberBetween(1, 3)),
            'strategy' => OutreachStrategy::Cold,
            'suggested_service_id' => null,
            'suggested_angle' => fake()->sentence(12),
            'assigned_on' => Carbon::today(),
            'contacted_at' => null,
            'skipped_at' => null,
            'snoozed_until' => null,
            'notes' => null,
        ];
    }

    /**
     * Indicate that the prospect has been emailed.
     */
    public function contacted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AssignmentStatus::Contacted,
            'contacted_at' => Carbon::now()->subDays(fake()->numberBetween(0, 4)),
        ]);
    }

    /**
     * Indicate that the prospect was passed over this week.
     */
    public function skipped(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AssignmentStatus::Skipped,
            'skipped_at' => Carbon::now()->subDays(fake()->numberBetween(0, 4)),
            'notes' => 'Not the right time to approach them.',
        ]);
    }

    /**
     * Indicate that the prospect has been pushed to a later date.
     */
    public function snoozed(int $days = 3): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AssignmentStatus::Snoozed,
            'snoozed_until' => Carbon::today()->addDays($days),
        ]);
    }
}
