<?php

namespace Database\Seeders;

use App\Models\ProposalTemplate;
use Database\Factories\ProposalTemplateFactory;
use Illuminate\Database\Seeder;

class ProposalTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProposalTemplate::create([
            'name' => 'Standard creative proposal',
            'description' => 'Default template for most creative projects',
            'sections' => ProposalTemplateFactory::defaultSections(),
            'default_terms' => '50% deposit before work begins, balance on delivery. Two rounds of revisions included. Quoted prices are valid for 30 days and exclude printing or hosting costs.',
            'currency' => 'MWK',
            'is_default' => true,
        ]);

        ProposalTemplate::create([
            'name' => 'Website project proposal',
            'description' => 'Template for website design and development projects',
            'sections' => $this->websiteProposalSections(),
            'default_terms' => '30% deposit before work begins, 40% on design approval, 30% on launch. Two rounds of revisions per stage. Quoted prices are valid for 30 days. Hosting and domain registration not included.',
            'currency' => 'MWK',
            'is_default' => false,
        ]);

        ProposalTemplate::create([
            'name' => 'Retainer proposal',
            'description' => 'Template for ongoing monthly retainer work',
            'sections' => $this->retainerProposalSections(),
            'default_terms' => 'Invoiced monthly in advance. First month paid upfront. Either party may cancel with 30 days written notice. Unused hours do not roll over.',
            'currency' => 'MWK',
            'is_default' => false,
        ]);

        ProposalTemplate::create([
            'name' => 'Brand identity proposal',
            'description' => 'Template specifically for brand identity projects',
            'sections' => $this->brandProposalSections(),
            'default_terms' => '50% deposit before work begins, 50% on final delivery. Three concept rounds, two revision rounds. All final files become your property. Quoted prices are valid for 30 days.',
            'currency' => 'MWK',
            'is_default' => false,
        ]);
    }

    /**
     * @return array<int, array{key: string, title: string, body: string, enabled: bool}>
     */
    protected function websiteProposalSections(): array
    {
        return [
            ['key' => 'introduction', 'title' => 'Introduction', 'body' => 'Thank you for the opportunity to propose a new website for {{company_name}}.', 'enabled' => true],
            ['key' => 'understanding', 'title' => 'Understanding of the brief', 'body' => 'Based on our conversation, you need a website that positions your organisation for growth and makes it easy for visitors to understand what you do.', 'enabled' => true],
            ['key' => 'approach', 'title' => 'Proposed approach', 'body' => 'Phase 1: Content strategy and sitemap. Phase 2: Visual design and user testing. Phase 3: Development and launch.', 'enabled' => true],
            ['key' => 'deliverables', 'title' => 'Deliverables', 'body' => 'Fully responsive website, content management system, analytics setup, and handover training.', 'enabled' => true],
            ['key' => 'timeline', 'title' => 'Timeline', 'body' => 'Estimated delivery: 8 weeks from deposit.', 'enabled' => true],
            ['key' => 'investment', 'title' => 'Investment', 'body' => 'Total investment shown below.', 'enabled' => true],
            ['key' => 'hosting', 'title' => 'Hosting and maintenance', 'body' => 'I can recommend hosting providers and offer ongoing support packages after launch.', 'enabled' => true],
            ['key' => 'terms', 'title' => 'Terms', 'body' => '30% deposit, 40% on design approval, 30% on launch. Two rounds of revisions per stage.', 'enabled' => true],
            ['key' => 'next_steps', 'title' => 'Next steps', 'body' => 'Approve this proposal and I will send an invoice for the first payment.', 'enabled' => true],
        ];
    }

    /**
     * @return array<int, array{key: string, title: string, body: string, enabled: bool}>
     */
    protected function retainerProposalSections(): array
    {
        return [
            ['key' => 'introduction', 'title' => 'Introduction', 'body' => 'This proposal outlines a monthly retainer arrangement to support {{company_name}} with ongoing creative work.', 'enabled' => true],
            ['key' => 'scope', 'title' => 'Scope of work', 'body' => 'Each month you will receive up to X hours of design work, including: social media graphics, report design, presentation templates, and ad hoc creative requests.', 'enabled' => true],
            ['key' => 'process', 'title' => 'How it works', 'body' => 'Submit requests via email or a shared task board. I will confirm timelines and deliver drafts for your review. Turnaround times depend on the size of the request.', 'enabled' => true],
            ['key' => 'investment', 'title' => 'Monthly investment', 'body' => 'Total monthly fee shown below.', 'enabled' => true],
            ['key' => 'terms', 'title' => 'Terms', 'body' => 'Invoiced monthly in advance. Either party may cancel with 30 days written notice. Unused hours do not roll over.', 'enabled' => true],
            ['key' => 'next_steps', 'title' => 'Next steps', 'body' => 'Approve this proposal and we can start with the first month.', 'enabled' => true],
        ];
    }

    /**
     * @return array<int, array{key: string, title: string, body: string, enabled: bool}>
     */
    protected function brandProposalSections(): array
    {
        return [
            ['key' => 'introduction', 'title' => 'Introduction', 'body' => 'Thank you for the opportunity to propose a new brand identity for {{company_name}}.', 'enabled' => true],
            ['key' => 'understanding', 'title' => 'Understanding of the brief', 'body' => 'You need a brand that reflects where the organisation is going, not where it has been.', 'enabled' => true],
            ['key' => 'approach', 'title' => 'Proposed approach', 'body' => 'Week 1: Brand audit and stakeholder interviews. Week 2-3: Three logo concepts. Week 4: Refinement and guidelines.', 'enabled' => true],
            ['key' => 'deliverables', 'title' => 'Deliverables', 'body' => 'Logo files in all formats, brand guidelines document, stationery templates, and social profile artwork.', 'enabled' => true],
            ['key' => 'timeline', 'title' => 'Timeline', 'body' => 'Estimated delivery: 4 weeks from deposit.', 'enabled' => true],
            ['key' => 'investment', 'title' => 'Investment', 'body' => 'Total investment shown below.', 'enabled' => true],
            ['key' => 'why_me', 'title' => 'Why work with me', 'body' => 'I have worked with organisations like yours to build brands that position them for the next phase of growth.', 'enabled' => true],
            ['key' => 'terms', 'title' => 'Terms', 'body' => '50% deposit, balance on delivery. Three concept rounds, two revision rounds. All final files become your property.', 'enabled' => true],
            ['key' => 'next_steps', 'title' => 'Next steps', 'body' => 'Approve this proposal and I will send an invoice for the deposit.', 'enabled' => true],
        ];
    }
}
