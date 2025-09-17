<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-30 days', '-5 days');
        $due = (clone $start)->modify('+'.fake()->numberBetween(5, 25).' days');

        return [
            'uuid' => (string) Str::uuid(),
            'name' => fake()->unique()->sentence(3),
            'description' => fake()->paragraph(),
            'department_uuid' => Department::inRandomOrder()->first()?->uuid ?? Department::factory()->create()->uuid,
            'manager_id' => User::inRandomOrder()->first()?->id ?? User::factory()->create()->id,
            'start_date' => $start,
            'due_date' => $due,
            'status' => fake()->randomElement(['draft', 'active', 'on_hold', 'completed', 'cancelled']),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'completion_percentage' => fake()->numberBetween(0, 90),
            'is_shared' => fake()->boolean(20),
            'estimated_hours' => fake()->numberBetween(10, 200),
            'actual_hours' => fake()->numberBetween(0, 150),
            'project_type' => fake()->randomElement(['internal', 'client']),
            'client_name' => fn(array $attrs) => $attrs['project_type'] === 'client' ? fake()->company() : null,
            'client_contact' => fn(array $attrs) => $attrs['project_type'] === 'client' ? fake()->email() : null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => 'completed', 'completion_percentage' => 100]);
    }

    public function highPriority(): static
    {
        return $this->state(fn () => ['priority' => 'high']);
    }
}
