<?php

namespace Database\Factories;

use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
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
            'subject_type' => null,
            'subject_id' => null,
            'contact_id' => null,
            'company_id' => null,
            'type' => fake()->randomElement([
                ActivityType::ContactCreated,
                ActivityType::EmailSent,
                ActivityType::EmailOpened,
                ActivityType::FollowUpCreated,
                ActivityType::ProposalSent,
            ]),
            'description' => null,
            'meta' => null,
            'occurred_at' => Carbon::now()->subDays(fake()->numberBetween(0, 30)),
        ];
    }

    /**
     * Link the activity to a specific contact.
     */
    public function forContact(Contact $contact): static
    {
        return $this->state(fn (array $attributes): array => [
            'contact_id' => $contact->id,
            'company_id' => $contact->company_id,
        ]);
    }

    /**
     * Link the activity to a specific company.
     */
    public function forCompany(Company $company): static
    {
        return $this->state(fn (array $attributes): array => [
            'company_id' => $company->id,
        ]);
    }

    /**
     * Record a specific type of activity.
     */
    public function type(ActivityType $type): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => $type,
            'description' => $this->descriptionForType($type),
        ]);
    }

    /**
     * Generate a realistic description for the given activity type.
     */
    protected function descriptionForType(ActivityType $type): ?string
    {
        return match ($type) {
            ActivityType::ContactCreated => 'Added to the database',
            ActivityType::EmailSent => 'Cold intro email sent',
            ActivityType::EmailOpened => 'Opened the email',
            ActivityType::EmailReplied => 'Replied asking for more information',
            ActivityType::FollowUpCreated => 'Follow up scheduled',
            ActivityType::ProposalSent => 'Proposal sent via email',
            ActivityType::ProposalAccepted => 'Accepted the proposal',
            ActivityType::DealWon => 'Deal closed',
            default => null,
        };
    }
}
