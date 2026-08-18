<?php

namespace Database\Factories;

use App\Enums\TemplateStatus;
use App\Models\ProposalTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Auth;

/**
 * @extends Factory<ProposalTemplate>
 */
class ProposalTemplateFactory extends Factory
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
            'name' => fake()->unique()->randomElement([
                'Standard creative proposal',
                'Brand identity proposal',
                'Website project proposal',
                'Retainer proposal',
            ]),
            'description' => fake()->sentence(12),
            'sections' => self::defaultSections(),
            'default_terms' => '50% deposit before work begins, balance on delivery. '
                .'Quoted prices are valid for 30 days and exclude printing costs.',
            'currency' => 'MWK',
            'status' => TemplateStatus::Active,
            'is_default' => false,
        ];
    }

    /**
     * The section skeleton every proposal starts from.
     *
     * @return array<int, array{key: string, title: string, body: string, enabled: bool}>
     */
    public static function defaultSections(): array
    {
        return [
            [
                'key' => 'introduction',
                'title' => 'Introduction',
                'body' => 'Thank you for the opportunity to put this together for {{company_name}}.',
                'enabled' => true,
            ],
            [
                'key' => 'understanding',
                'title' => 'Understanding of the brief',
                'body' => 'Here is what I understand you need and why it matters right now.',
                'enabled' => true,
            ],
            [
                'key' => 'approach',
                'title' => 'Proposed approach',
                'body' => 'How the work will be done, stage by stage.',
                'enabled' => true,
            ],
            [
                'key' => 'deliverables',
                'title' => 'Deliverables',
                'body' => 'Exactly what you will receive at the end of the project.',
                'enabled' => true,
            ],
            [
                'key' => 'timeline',
                'title' => 'Timeline',
                'body' => 'Indicative dates from kick off to handover.',
                'enabled' => true,
            ],
            [
                'key' => 'investment',
                'title' => 'Investment',
                'body' => 'A breakdown of the cost of the work.',
                'enabled' => true,
            ],
            [
                'key' => 'why_me',
                'title' => 'Why work with me',
                'body' => 'Relevant experience and a few examples of similar work.',
                'enabled' => true,
            ],
            [
                'key' => 'terms',
                'title' => 'Terms',
                'body' => 'Payment terms, revisions and ownership of the final files.',
                'enabled' => true,
            ],
            [
                'key' => 'next_steps',
                'title' => 'Next steps',
                'body' => 'Approve this proposal and I will send an invoice for the deposit.',
                'enabled' => true,
            ],
        ];
    }

    /**
     * Indicate that this is the template new proposals should use.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => true,
        ]);
    }
}
