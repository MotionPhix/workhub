<?php

namespace Database\Factories;

use App\Models\Proposal;
use App\Models\ProposalVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProposalVersion>
 */
class ProposalVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'proposal_id' => Proposal::factory(),
            'version' => 1,
            'status' => fn (array $attributes): string => Proposal::find($attributes['proposal_id'])?->status->value ?? 'draft',
            'sections' => $this->exampleSections(),
            'line_items' => $this->exampleLineItems(),
            'subtotal' => 0,
            'discount' => 0,
            'tax_rate' => 0,
            'total_amount' => 0,
            'currency' => 'MWK',
            'timeline' => 'Estimated delivery: 4 weeks from deposit.',
            'terms' => '50% deposit before work begins, balance on delivery. Quoted prices are valid for 30 days.',
            'change_summary' => null,
            'valid_until' => null,
            'sent_at' => null,
        ];
    }

    /**
     * A realistic set of proposal sections.
     *
     * @return array<int, array{key: string, title: string, body: string, enabled: bool}>
     */
    protected function exampleSections(): array
    {
        return [
            [
                'key' => 'introduction',
                'title' => 'Introduction',
                'body' => 'Thank you for the opportunity to put this together. I am excited about the possibility of working with you on this project.',
                'enabled' => true,
            ],
            [
                'key' => 'understanding',
                'title' => 'Understanding of the brief',
                'body' => 'Based on our conversation, you need a refreshed brand identity that positions your organisation for the next phase of growth.',
                'enabled' => true,
            ],
            [
                'key' => 'approach',
                'title' => 'Proposed approach',
                'body' => 'I will start with a brand audit and stakeholder interviews, then move into concept development. You will see three directions before we refine the chosen one.',
                'enabled' => true,
            ],
            [
                'key' => 'deliverables',
                'title' => 'Deliverables',
                'body' => 'Logo files in all formats, brand guidelines document, stationery templates, and social media profile artwork.',
                'enabled' => true,
            ],
            [
                'key' => 'timeline',
                'title' => 'Timeline',
                'body' => 'Week 1: Discovery. Week 2-3: Concepts. Week 4: Refinement and final files.',
                'enabled' => true,
            ],
            [
                'key' => 'investment',
                'title' => 'Investment',
                'body' => 'The total investment for this project is shown in the line items below.',
                'enabled' => true,
            ],
            [
                'key' => 'terms',
                'title' => 'Terms',
                'body' => '50% deposit before work begins, balance on delivery. Two rounds of revisions included. All final files become your property.',
                'enabled' => true,
            ],
        ];
    }

    /**
     * A realistic set of priced line items.
     *
     * @return array<int, array{description: string, quantity: float, unit_price: float, total: float}>
     */
    protected function exampleLineItems(): array
    {
        $items = [
            ['description' => 'Brand strategy and discovery', 'quantity' => 1.0, 'unit_price' => 450_000],
            ['description' => 'Logo design (3 concepts, 2 revision rounds)', 'quantity' => 1.0, 'unit_price' => 800_000],
            ['description' => 'Brand guidelines document', 'quantity' => 1.0, 'unit_price' => 350_000],
            ['description' => 'Stationery suite (business cards, letterhead)', 'quantity' => 1.0, 'unit_price' => 250_000],
        ];

        return array_map(function (array $item): array {
            $item['total'] = $item['quantity'] * $item['unit_price'];

            return $item;
        }, $items);
    }

    /**
     * Indicate that this is a revised version of an earlier proposal.
     */
    public function revision(int $versionNumber = 2): static
    {
        return $this->state(fn (array $attributes): array => [
            'version' => $versionNumber,
            'change_summary' => fake()->randomElement([
                'Adjusted timeline to accommodate the client annual planning cycle.',
                'Added an additional round of revisions based on feedback.',
                'Reduced scope: removed social media templates to fit the revised budget.',
                'Split payment into three milestones instead of two.',
            ]),
        ]);
    }
}
