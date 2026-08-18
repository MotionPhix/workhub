<?php

namespace Database\Factories;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'NGO', 'Corporate', 'Startup', 'Referral', 'High Value', 'Rebrand Needed',
            'No Website', 'Dated Website', 'Warm', 'Event Coming Up', 'Annual Report',
            'Repeat Client', 'Slow Payer', 'Decision Maker',
        ]);

        return [
            'user_id' => Auth::id() ?? User::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'color' => fake()->randomElement(['slate', 'sky', 'indigo', 'emerald', 'amber', 'rose', 'violet']),
        ];
    }
}
