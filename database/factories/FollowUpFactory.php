<?php

namespace Database\Factories;

use App\Enums\FollowUpStatus;
use App\Enums\FollowUpType;
use App\Enums\Priority;
use App\Models\Contact;
use App\Models\FollowUp;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * @extends Factory<FollowUp>
 */
class FollowUpFactory extends Factory
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
            'contact_id' => Contact::factory(),
            'email_message_id' => null,
            'type' => FollowUpType::Email,
            'status' => FollowUpStatus::Pending,
            'priority' => fake()->randomElement(Priority::cases()),
            'due_on' => Carbon::today()->addDays(fake()->numberBetween(1, 10)),
            'notes' => fake()->randomElement([
                'No reply to the first email, send a short nudge.',
                'Asked to be contacted after their board meeting.',
                'Promised to send examples of similar work.',
                'Check whether the proposal was received.',
            ]),
            'outcome' => null,
            'completed_at' => null,
            'cancelled_at' => null,
            'snooze_count' => 0,
        ];
    }

    /**
     * Indicate that the follow up is due today.
     */
    public function dueToday(): static
    {
        return $this->state(fn (array $attributes): array => [
            'due_on' => Carbon::today(),
        ]);
    }

    /**
     * Indicate that the follow up is late.
     */
    public function overdue(int $days = 4): static
    {
        return $this->state(fn (array $attributes): array => [
            'due_on' => Carbon::today()->subDays($days),
        ]);
    }

    /**
     * Indicate that the follow up has been done.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => FollowUpStatus::Completed,
            'completed_at' => Carbon::now()->subDays(fake()->numberBetween(1, 20)),
            'outcome' => fake()->randomElement([
                'Sent the nudge, no reply yet.',
                'Spoke on the phone, they asked for a proposal.',
                'Not interested this year, revisit in January.',
            ]),
        ]);
    }

    /**
     * Indicate that the follow up was called off.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => FollowUpStatus::Cancelled,
            'cancelled_at' => Carbon::now()->subDays(fake()->numberBetween(1, 10)),
        ]);
    }
}
