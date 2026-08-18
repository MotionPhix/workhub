<?php

namespace Database\Factories;

use App\Enums\ClientStatus;
use App\Models\Client;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
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
            'company_id' => Company::factory(),
            'primary_contact_id' => null,
            'opportunity_id' => null,
            'name' => fn (array $attributes): string => Company::withoutGlobalScopes()->find($attributes['company_id'])?->name ?? fake()->company(),
            'status' => ClientStatus::Active,
            'since' => Carbon::today()->subMonths(fake()->numberBetween(1, 36)),
            'total_revenue' => fake()->randomElement([850_000, 1_800_000, 3_500_000, 6_000_000, 12_000_000]),
            'retainer_amount' => null,
            'currency' => 'MWK',
            'notes' => null,
        ];
    }

    /**
     * Indicate that the client pays a monthly retainer.
     */
    public function onRetainer(): static
    {
        return $this->state(fn (array $attributes): array => [
            'retainer_amount' => fake()->randomElement([250_000, 500_000, 750_000, 1_000_000]),
        ]);
    }

    /**
     * Indicate that the relationship has ended.
     */
    public function former(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ClientStatus::Former,
        ]);
    }

    /**
     * Link the client to a specific contact.
     */
    public function withContact(Contact $contact): static
    {
        return $this->state(fn (array $attributes): array => [
            'primary_contact_id' => $contact->id,
        ]);
    }

    /**
     * Link the client to the opportunity that converted them.
     */
    public function fromOpportunity(Opportunity $opportunity): static
    {
        return $this->state(fn (array $attributes): array => [
            'opportunity_id' => $opportunity->id,
            'company_id' => $opportunity->company_id,
            'primary_contact_id' => $opportunity->contact_id,
        ]);
    }
}
