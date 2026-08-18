<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Brand Identity Design',
            'Logo Design',
            'Website Design',
            'Website Development',
            'Web Application Development',
            'UI/UX Design',
            'Graphic Design',
            'Social Media Design',
            'Digital Marketing Support',
            'Creative Consultancy',
            'Annual Report Design',
            'Print & Publication Design',
        ]);

        return [
            'user_id' => Auth::id() ?? User::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(12),
            'color' => fake()->randomElement(['slate', 'sky', 'indigo', 'emerald', 'amber', 'rose', 'violet']),
            'default_rate' => fake()->randomElement([250_000, 450_000, 750_000, 1_200_000, 2_500_000]),
            'rate_unit' => fake()->randomElement(['project', 'day', 'month', 'page']),
            'is_active' => true,
            'is_focus' => false,
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }

    /**
     * Indicate that the service is being actively pushed.
     */
    public function focus(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_focus' => true,
        ]);
    }

    /**
     * Indicate that the service is no longer offered.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
