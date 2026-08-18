<?php

namespace Database\Factories;

use App\Enums\EmailEventType;
use App\Models\EmailEvent;
use App\Models\EmailMessage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<EmailEvent>
 */
class EmailEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email_message_id' => EmailMessage::factory(),
            'type' => fake()->randomElement(EmailEventType::cases()),
            'occurred_at' => Carbon::now()->subMinutes(fake()->numberBetween(1, 4_000)),
            'meta' => null,
        ];
    }

    /**
     * Record a specific kind of event.
     */
    public function type(EmailEventType $type): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => $type,
        ]);
    }
}
