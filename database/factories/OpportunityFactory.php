<?php

namespace Database\Factories;

use App\Enums\OpportunityStage;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * @extends Factory<Opportunity>
 */
class OpportunityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $stage = fake()->randomElement(OpportunityStage::cases());

        return [
            'user_id' => Auth::id() ?? User::factory(),
            'contact_id' => Contact::factory(),
            'company_id' => null,
            'service_id' => null,
            'title' => fake()->randomElement([
                'Brand refresh',
                'Website redesign',
                'Annual report design',
                'Logo and stationery',
                'Social media template pack',
                'Web application build',
                'Campaign artwork',
            ]),
            'stage' => $stage,
            'value' => fake()->randomElement([450_000, 800_000, 1_500_000, 2_800_000, 4_500_000, 7_000_000]),
            'currency' => 'MWK',
            'probability' => $stage->defaultProbability(),
            'expected_close_on' => Carbon::today()->addDays(fake()->numberBetween(7, 90)),
            'source' => null,
            'notes' => null,
            'won_at' => null,
            'lost_at' => null,
            'lost_reason' => null,
            'last_activity_at' => Carbon::now()->subDays(fake()->numberBetween(0, 20)),
        ];
    }

    /**
     * Place the opportunity in a specific pipeline stage.
     */
    public function stage(OpportunityStage $stage): static
    {
        return $this->state(fn (array $attributes): array => [
            'stage' => $stage,
            'probability' => $stage->defaultProbability(),
        ]);
    }

    /**
     * Indicate that the work was won.
     */
    public function won(): static
    {
        return $this->stage(OpportunityStage::Won)->state(fn (array $attributes): array => [
            'won_at' => Carbon::now()->subDays(fake()->numberBetween(1, 60)),
        ]);
    }

    /**
     * Indicate that the work was lost.
     */
    public function lost(): static
    {
        return $this->stage(OpportunityStage::Lost)->state(fn (array $attributes): array => [
            'lost_at' => Carbon::now()->subDays(fake()->numberBetween(1, 60)),
            'lost_reason' => fake()->randomElement([
                'Went with a cheaper quote.',
                'Budget was cut for the year.',
                'Decided to handle it in house.',
                'No response after the proposal.',
            ]),
        ]);
    }
}
