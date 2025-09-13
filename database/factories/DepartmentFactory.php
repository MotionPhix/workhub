<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Department>
 */
class DepartmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $departments = [
            'Human Resources',
            'Information Technology',
            'Finance',
            'Marketing',
            'Sales',
            'Operations',
            'Customer Service',
            'Research & Development',
            'Quality Assurance',
            'Legal',
            'Accounting',
            'Administration',
        ];

        return [
            'name' => $this->faker->unique()->randomElement($departments),
            'description' => $this->faker->sentence(6, true),
        ];
    }
}
