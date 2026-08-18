<?php

namespace Database\Factories;

use App\Enums\ProposalStatus;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\Proposal;
use App\Models\ProposalTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * @extends Factory<Proposal>
 */
class ProposalFactory extends Factory
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
            'contact_id' => Contact::factory(),
            'company_id' => null,
            'opportunity_id' => null,
            'proposal_template_id' => null,
            'title' => fake()->randomElement([
                'Brand identity and website proposal',
                'Annual report design proposal',
                'Logo and stationery proposal',
                'Marketing materials proposal',
                'Website redesign proposal',
            ]),
            'project_name' => null,
            'status' => ProposalStatus::Draft,
            'currency' => 'MWK',
            'total_amount' => 0,
            'current_version' => 1,
            'valid_until' => null,
            'sent_at' => null,
            'viewed_at' => null,
            'accepted_at' => null,
            'rejected_at' => null,
            'expired_at' => null,
            'view_count' => 0,
            'notes' => null,
        ];
    }

    /**
     * Indicate that the proposal has been sent to the client.
     */
    public function sent(int $daysAgo = 5): static
    {
        return $this->state(function (array $attributes) use ($daysAgo): array {
            $sentAt = Carbon::now()->subDays($daysAgo);

            return [
                'status' => ProposalStatus::Sent,
                'sent_at' => $sentAt,
                'valid_until' => Carbon::today()->addDays(30 - $daysAgo),
            ];
        });
    }

    /**
     * Indicate that the client has opened the proposal.
     */
    public function viewed(int $daysAgo = 3): static
    {
        return $this->sent($daysAgo + 2)->state(fn (array $attributes): array => [
            'status' => ProposalStatus::Viewed,
            'viewed_at' => Carbon::now()->subDays($daysAgo),
            'view_count' => fake()->numberBetween(1, 8),
        ]);
    }

    /**
     * Indicate that the client has accepted the proposal.
     */
    public function accepted(): static
    {
        return $this->viewed(5)->state(fn (array $attributes): array => [
            'status' => ProposalStatus::Accepted,
            'accepted_at' => Carbon::now()->subDays(fake()->numberBetween(1, 3)),
        ]);
    }

    /**
     * Indicate that the client has turned down the proposal.
     */
    public function rejected(): static
    {
        return $this->viewed(8)->state(fn (array $attributes): array => [
            'status' => ProposalStatus::Rejected,
            'rejected_at' => Carbon::now()->subDays(fake()->numberBetween(1, 5)),
        ]);
    }

    /**
     * Indicate that the proposal has passed its validity date unanswered.
     */
    public function expired(): static
    {
        return $this->sent(35)->state(fn (array $attributes): array => [
            'status' => ProposalStatus::Expired,
            'expired_at' => Carbon::now()->subDays(5),
            'valid_until' => Carbon::today()->subDays(5),
        ]);
    }

    /**
     * Link the proposal to a specific opportunity.
     */
    public function forOpportunity(Opportunity $opportunity): static
    {
        return $this->state(fn (array $attributes): array => [
            'opportunity_id' => $opportunity->id,
            'contact_id' => $opportunity->contact_id,
            'company_id' => $opportunity->company_id,
        ]);
    }

    /**
     * Base the proposal on a specific template.
     */
    public function fromTemplate(ProposalTemplate $template): static
    {
        return $this->state(fn (array $attributes): array => [
            'proposal_template_id' => $template->id,
        ]);
    }
}
