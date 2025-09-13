<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserInvite;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserInvite>
 */
class UserInviteFactory extends Factory
{
    protected $model = UserInvite::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invited_by' => User::factory(),
            'email' => fake()->unique()->safeEmail(),
            'name' => fake()->name(),
            'department_uuid' => null,
            'manager_email' => null,
            'job_title' => fake()->jobTitle(),
            'role_name' => 'employee',
            'token' => hash('sha256', bin2hex(random_bytes(32))),
            'invited_at' => now(),
            'expires_at' => now()->addWeek(),
            'accepted_at' => null,
            'declined_at' => null,
            'reminder_sent_at' => null,
            'reminder_count' => 0,
            'invite_data' => [],
            'status' => 'pending',
        ];
    }

    /**
     * Create an accepted invitation
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);
    }

    /**
     * Create a declined invitation
     */
    public function declined(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'declined',
            'declined_at' => now(),
        ]);
    }

    /**
     * Create an expired invitation
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subWeek(),
        ]);
    }

    /**
     * Create an invitation with reminders sent
     */
    public function withReminders(int $count = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'reminder_count' => $count,
            'reminder_sent_at' => now()->subDays($count),
        ]);
    }
}
