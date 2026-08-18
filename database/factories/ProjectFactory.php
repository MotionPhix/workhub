<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
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
            'client_id' => Client::factory(),
            'service_id' => null,
            'proposal_id' => null,
            'name' => fake()->randomElement([
                'Annual report 2025',
                'Website redesign',
                'Brand refresh',
                'Marketing collateral pack',
                'Social media template system',
                'Event branding',
            ]),
            'description' => fake()->sentence(12),
            'status' => fake()->randomElement(ProjectStatus::cases()),
            'value' => fake()->randomElement([650_000, 1_200_000, 2_500_000, 4_000_000]),
            'currency' => 'MWK',
            'starts_on' => Carbon::today()->subDays(fake()->numberBetween(0, 60)),
            'ends_on' => null,
        ];
    }

    /**
     * Indicate that the project is currently being worked on.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProjectStatus::InProgress,
            'starts_on' => Carbon::today()->subDays(fake()->numberBetween(5, 30)),
            'ends_on' => Carbon::today()->addDays(fake()->numberBetween(10, 60)),
        ]);
    }

    /**
     * Indicate that the project has been delivered.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProjectStatus::Completed,
            'starts_on' => Carbon::today()->subMonths(fake()->numberBetween(2, 12)),
            'ends_on' => Carbon::today()->subDays(fake()->numberBetween(1, 60)),
        ]);
    }

    /**
     * Link the project to a specific service.
     */
    public function forService(Service $service): static
    {
        return $this->state(fn (array $attributes): array => [
            'service_id' => $service->id,
        ]);
    }

    /**
     * Link the project to the proposal that sold it.
     */
    public function fromProposal(Proposal $proposal): static
    {
        return $this->state(fn (array $attributes): array => [
            'proposal_id' => $proposal->id,
        ]);
    }
}
