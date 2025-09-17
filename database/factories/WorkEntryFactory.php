<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WorkEntry;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WorkEntry>
 */
class WorkEntryFactory extends Factory
{
    protected $model = WorkEntry::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-10 days', '-1 days');
        $end = (clone $start)->modify('+'.fake()->numberBetween(1, 5).' hours');

        return [
            'uuid' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'work_title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'start_date_time' => $start,
            'end_date_time' => $end,
            'status' => 'completed',
            'project_uuid' => null,
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'work_type' => fake()->randomElement(['task', 'meeting', 'call', 'email', 'research']),
            'location' => fake()->city(),
            'contacts' => [],
            'organizations' => [],
            'value_generated' => null,
            'outcome' => null,
            'attachments' => [],
            'mood' => fake()->randomElement(['happy', 'neutral', 'tired']),
            'collaborators' => [],
            'requires_follow_up' => false,
            'follow_up_date' => null,
        ];
    }

    /**
     * Assign the entry to a specific existing user (with optional department already set).
     */
    public function forUser(User $user): static
    {
        return $this->state(fn () => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Create the entry for a user belonging to the provided department (by uuid) using a new factory user.
     */
    public function forDepartment(string $departmentUuid): static
    {
        return $this->state(function () use ($departmentUuid) {
            return [
                'user_id' => User::factory()->state([
                    'department_uuid' => $departmentUuid,
                ]),
            ];
        });
    }

    /**
     * Sequence through multiple department UUIDs for bulk creation.
     */
    public function sequenceDepartments(array $departmentUuids): static
    {
        $seq = [];
        foreach ($departmentUuids as $uuid) {
            $seq[] = ['user_id' => User::factory()->state(['department_uuid' => $uuid])];
        }

        return $this->sequence(...$seq);
    }
}
