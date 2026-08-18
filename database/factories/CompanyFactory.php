<?php

namespace Database\Factories;

use App\Enums\CompanySize;
use App\Enums\LeadSource;
use App\Enums\OrganizationType;
use App\Enums\QualityRating;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Name fragments that produce plausible Malawian organisation names.
     *
     * @var array<int, string>
     */
    public const NAME_PREFIXES = [
        'Chikondi', 'Mzuzu', 'Lilongwe', 'Blantyre', 'Zomba', 'Nkhata', 'Salima',
        'Mangochi', 'Kasungu', 'Dedza', 'Thyolo', 'Mulanje', 'Shire', 'Nyika',
        'Kamuzu', 'Bwaila', 'Limbe', 'Ndirande', 'Chilomoni', 'Zalewa', 'Liwonde',
    ];

    /**
     * @var array<int, string>
     */
    public const NAME_SUFFIXES = [
        'Holdings', 'Enterprises', 'Trading', 'Group', 'Solutions', 'Consulting',
        'Foundation', 'Trust', 'Initiative', 'Network', 'Institute', 'Media',
        'Logistics', 'Agro', 'Investments', 'Properties', 'Technologies', 'Ventures',
    ];

    /**
     * @var array<int, string>
     */
    public const LOCATIONS = [
        'Lilongwe', 'Blantyre', 'Mzuzu', 'Zomba', 'Kasungu', 'Mangochi', 'Salima',
        'Karonga', 'Nkhotakota', 'Balaka', 'Dedza', 'Liwonde', 'Mulanje', 'Nsanje',
    ];

    /**
     * @var array<int, string>
     */
    public const INDUSTRIES = [
        'Agriculture', 'NGO / Development', 'Education', 'Financial Services',
        'Healthcare', 'Hospitality & Tourism', 'Manufacturing', 'Retail',
        'Construction', 'Media & Broadcasting', 'Telecommunications',
        'Transport & Logistics', 'Professional Services', 'Energy',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement(self::NAME_PREFIXES).' '.fake()->randomElement(self::NAME_SUFFIXES);
        $domain = Str::slug($name).'.mw';

        return [
            'user_id' => Auth::id() ?? User::factory(),
            'name' => $name,
            'email' => "info@{$domain}",
            'phone' => $this->malawianPhoneNumber(),
            'website' => fake()->boolean(70) ? "https://{$domain}" : null,
            'industry' => fake()->randomElement(self::INDUSTRIES),
            'location' => fake()->randomElement(self::LOCATIONS),
            'country' => 'Malawi',
            'size' => fake()->randomElement(CompanySize::cases()),
            'organization_type' => fake()->randomElement(OrganizationType::cases()),
            'lead_source' => fake()->randomElement(LeadSource::cases()),
            'linkedin' => null,
            'facebook' => fake()->boolean(60) ? 'https://facebook.com/'.Str::slug($name) : null,
            'twitter' => null,
            'instagram' => null,
            'estimated_budget' => fake()->randomElement([500_000, 1_500_000, 3_000_000, 6_000_000, 12_000_000]),
            'services_offered' => null,
            'notes' => null,
            'description' => null,
            'what_they_do' => null,
            'website_quality' => null,
            'branding_quality' => null,
            'social_presence' => null,
            'potential_problems' => null,
            'opportunities' => null,
            'suggested_pitch' => null,
            'researched_at' => null,
            'is_client' => false,
        ];
    }

    /**
     * A Malawian mobile number in local dialling format.
     */
    protected function malawianPhoneNumber(): string
    {
        return '+265 '.fake()->randomElement(['88', '99']).' '.fake()->numerify('# ### ###');
    }

    /**
     * Indicate that the research fields have been filled in.
     */
    public function researched(): static
    {
        return $this->state(fn (array $attributes): array => [
            'description' => fake()->paragraph(2),
            'what_they_do' => fake()->sentence(14),
            'website_quality' => fake()->randomElement(QualityRating::cases()),
            'branding_quality' => fake()->randomElement(QualityRating::cases()),
            'social_presence' => fake()->randomElement([
                'Active on Facebook, nothing else',
                'Facebook and LinkedIn, posts weekly',
                'No social presence found',
                'Instagram only, inconsistent branding',
            ]),
            'potential_problems' => fake()->randomElement([
                'Website is not mobile friendly and has not been updated in years.',
                'Logo and colours differ across every channel.',
                'No brand guidelines, so their reports look inconsistent.',
                'Social posts are text only with no visual identity.',
            ]),
            'opportunities' => fake()->randomElement([
                'Full brand refresh ahead of their next funding cycle.',
                'A modern website with a proper donor reporting section.',
                'Annual report design and a template pack for internal use.',
                'Social media template system to keep posts on brand.',
            ]),
            'suggested_pitch' => fake()->sentence(18),
            'researched_at' => fake()->dateTimeBetween('-3 months', 'now'),
        ]);
    }

    /**
     * Indicate that the organisation is a non governmental organisation.
     */
    public function ngo(): static
    {
        return $this->state(fn (array $attributes): array => [
            'organization_type' => fake()->randomElement([
                OrganizationType::Ngo,
                OrganizationType::InternationalNgo,
            ]),
            'industry' => 'NGO / Development',
            'lead_source' => LeadSource::Referral,
        ]);
    }

    /**
     * Indicate that the company already pays for work.
     */
    public function client(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_client' => true,
            'lead_source' => LeadSource::ExistingClient,
        ]);
    }
}
