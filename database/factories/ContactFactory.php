<?php

namespace Database\Factories;

use App\Enums\ContactStatus;
use App\Enums\LeadSource;
use App\Enums\Priority;
use App\Enums\RelationshipStage;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    /**
     * @var array<int, string>
     */
    public const FIRST_NAMES = [
        'Chikondi', 'Thoko', 'Tamandani', 'Limbani', 'Mphatso', 'Yamikani', 'Takondwa',
        'Alinafe', 'Chimwemwe', 'Grace', 'Patrick', 'Memory', 'Blessings', 'Precious',
        'Fatsani', 'Kondwani', 'Tiyamike', 'Rejoice', 'Emmanuel', 'Esther', 'Joseph',
        'Faith', 'Gift', 'Lucius', 'Sarah', 'Innocent', 'Charity', 'Madalitso',
    ];

    /**
     * @var array<int, string>
     */
    public const LAST_NAMES = [
        'Banda', 'Phiri', 'Mwale', 'Chirwa', 'Nkhoma', 'Kumwenda', 'Gondwe', 'Kachala',
        'Mkandawire', 'Chisale', 'Mhango', 'Zimba', 'Nyirenda', 'Msiska', 'Kalua',
        'Mbewe', 'Kanyama', 'Jere', 'Lungu', 'Mataka', 'Kaunda', 'Tembo', 'Chilenje',
    ];

    /**
     * @var array<int, string>
     */
    public const JOB_TITLES = [
        'Executive Director', 'Programmes Manager', 'Communications Officer',
        'Marketing Manager', 'Managing Director', 'Operations Manager', 'Brand Manager',
        'Head of Communications', 'Country Director', 'Business Development Manager',
        'Digital Marketing Officer', 'Founder', 'Chief Executive Officer',
        'Projects Coordinator', 'Proprietor',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->randomElement(self::FIRST_NAMES);
        $lastName = fake()->randomElement(self::LAST_NAMES);

        return [
            'user_id' => Auth::id() ?? User::factory(),
            'company_id' => null,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'job_title' => fake()->randomElement(self::JOB_TITLES),
            'email' => Str::slug("{$firstName} {$lastName}", '.').'@'.fake()->randomElement([
                'example.mw', 'example.org', 'example.com',
            ]),
            'phone' => '+265 '.fake()->randomElement(['88', '99']).' '.fake()->numerify('# ### ###'),
            'website' => null,
            'linkedin' => null,
            'facebook' => null,
            'twitter' => null,
            'instagram' => null,
            'location' => fake()->randomElement(CompanyFactory::LOCATIONS),
            'industry' => fake()->randomElement(CompanyFactory::INDUSTRIES),
            'status' => ContactStatus::New,
            'priority' => fake()->randomElement(Priority::cases()),
            'lead_score' => fake()->numberBetween(10, 85),
            'relationship_stage' => RelationshipStage::Stranger,
            'lead_source' => fake()->randomElement(LeadSource::cases()),
            'primary_service_id' => null,
            'potential_needs' => null,
            'estimated_budget' => fake()->randomElement([null, 400_000, 900_000, 2_000_000, 5_000_000]),
            'notes' => null,
            'is_primary_contact' => false,
            'first_contacted_at' => null,
            'last_contacted_at' => null,
            'responded_at' => null,
            'outreach_count' => 0,
            'last_outcome' => null,
            'cooldown_until' => null,
        ];
    }

    /**
     * Indicate that the contact has been emailed but has not answered.
     */
    public function contacted(int $daysAgo = 5): static
    {
        return $this->state(function (array $attributes) use ($daysAgo): array {
            $sentAt = Carbon::today()->subDays($daysAgo);

            return [
                'status' => ContactStatus::Contacted,
                'relationship_stage' => RelationshipStage::Introduced,
                'first_contacted_at' => $sentAt,
                'last_contacted_at' => $sentAt,
                'outreach_count' => fake()->numberBetween(1, 3),
                'cooldown_until' => $sentAt->copy()->addDays(30),
            ];
        });
    }

    /**
     * Indicate that the contact answered the outreach.
     */
    public function replied(): static
    {
        return $this->contacted(9)->state(fn (array $attributes): array => [
            'status' => ContactStatus::Replied,
            'relationship_stage' => RelationshipStage::Conversing,
            'responded_at' => Carbon::today()->subDays(7),
            'lead_score' => fake()->numberBetween(60, 90),
            'last_outcome' => 'Replied asking for pricing',
        ]);
    }

    /**
     * Indicate that the contact is actively interested in buying.
     */
    public function interested(): static
    {
        return $this->replied()->state(fn (array $attributes): array => [
            'status' => ContactStatus::Interested,
            'relationship_stage' => RelationshipStage::Engaged,
            'lead_score' => fake()->numberBetween(70, 95),
            'potential_needs' => fake()->randomElement([
                'Needs a full rebrand before their next annual report.',
                'Wants a new website with a donor section.',
                'Looking for social media templates and a brand guide.',
            ]),
        ]);
    }

    /**
     * Indicate that the contact converted into a client.
     */
    public function won(): static
    {
        return $this->interested()->state(fn (array $attributes): array => [
            'status' => ContactStatus::Won,
            'relationship_stage' => RelationshipStage::Client,
            'lead_score' => fake()->numberBetween(85, 100),
        ]);
    }

    /**
     * Indicate that the contact asked never to be emailed again.
     */
    public function doNotContact(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ContactStatus::DoNotContact,
            'last_outcome' => 'Asked not to be contacted again',
        ]);
    }

    /**
     * Indicate that the contact is resting inside a cooldown window.
     */
    public function inCooldown(int $days = 20): static
    {
        return $this->state(fn (array $attributes): array => [
            'cooldown_until' => Carbon::today()->addDays($days),
        ]);
    }

    /**
     * Indicate that the contact is a strong lead.
     */
    public function hot(): static
    {
        return $this->state(fn (array $attributes): array => [
            'lead_score' => fake()->numberBetween(70, 100),
            'priority' => Priority::High,
        ]);
    }

    /**
     * Indicate that the contact is the main person at their organisation.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_primary_contact' => true,
        ]);
    }
}
