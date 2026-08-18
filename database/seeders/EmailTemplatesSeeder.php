<?php

namespace Database\Seeders;

use App\Enums\TemplateCategory;
use App\Enums\TemplateStatus;
use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        EmailTemplate::create([
            'name' => 'Cold intro — Brand observation',
            'category' => TemplateCategory::ColdOutreach,
            'subject' => 'A thought about {{company_name}}',
            'body' => $this->coldIntroBody(),
            'status' => TemplateStatus::Active,
        ]);

        EmailTemplate::create([
            'name' => 'Cold intro — Website observation',
            'category' => TemplateCategory::ColdOutreach,
            'subject' => 'Your website and {{company_name}}',
            'body' => $this->websiteObservationBody(),
            'status' => TemplateStatus::Active,
        ]);

        EmailTemplate::create([
            'name' => 'Follow-up — No reply',
            'category' => TemplateCategory::FollowUp,
            'subject' => 'Re: A thought about {{company_name}}',
            'body' => $this->followUpNoReplyBody(),
            'status' => TemplateStatus::Active,
        ]);

        EmailTemplate::create([
            'name' => 'Follow-up — After meeting',
            'category' => TemplateCategory::FollowUp,
            'subject' => 'Great to speak with you',
            'body' => $this->followUpAfterMeetingBody(),
            'status' => TemplateStatus::Active,
        ]);

        EmailTemplate::create([
            'name' => 'Proposal sent',
            'category' => TemplateCategory::ProposalIntroduction,
            'subject' => 'Proposal for {{company_name}}',
            'body' => $this->proposalSentBody(),
            'status' => TemplateStatus::Active,
        ]);

        EmailTemplate::create([
            'name' => 'Proposal follow-up',
            'category' => TemplateCategory::FollowUp,
            'subject' => 'Re: Proposal for {{company_name}}',
            'body' => $this->proposalFollowUpBody(),
            'status' => TemplateStatus::Active,
        ]);

        EmailTemplate::create([
            'name' => 'Client onboarding',
            'category' => TemplateCategory::Introduction,
            'subject' => 'Next steps for {{project_name}}',
            'body' => $this->clientOnboardingBody(),
            'status' => TemplateStatus::Active,
        ]);

        EmailTemplate::create([
            'name' => 'Project update',
            'category' => TemplateCategory::Introduction,
            'subject' => 'Progress update: {{project_name}}',
            'body' => $this->projectUpdateBody(),
            'status' => TemplateStatus::Active,
        ]);
    }

    protected function coldIntroBody(): string
    {
        return <<<'TEXT'
Hi {{first_name}},

I came across {{company_name}} while researching {{industry}} organisations in Malawi, and {{observation}}.

I am a {{my_role}} based in {{my_location}}, and I work with organisations like yours on {{service}}. {{suggestion}}.

Would you be open to a short conversation about this? I have a few ideas that might be relevant to what you are doing right now.

Best,
{{my_name}}
{{my_business}}
{{my_phone}}
{{my_website}}
TEXT;
    }

    protected function websiteObservationBody(): string
    {
        return <<<'TEXT'
Hi {{first_name}},

I was looking at the {{company_name}} website and noticed {{observation}}.

I specialise in {{service}} and have worked with several {{industry}} organisations to {{outcome}}.

If this sounds useful, I would be happy to share a few specific ideas for {{company_name}}.

Best,
{{my_name}}
{{my_business}}
{{my_phone}}
{{my_website}}
TEXT;
    }

    protected function followUpNoReplyBody(): string
    {
        return <<<'TEXT'
Hi {{first_name}},

I sent a message last week about {{subject}}.

I know inboxes get busy, so I thought I would follow up in case this is still relevant.

If now is not the right time, that is fine too. Just let me know.

Best,
{{my_name}}
TEXT;
    }

    protected function followUpAfterMeetingBody(): string
    {
        return <<<'TEXT'
Hi {{first_name}},

Thanks for taking the time to speak with me {{meeting_date}}.

As discussed, I have put together {{deliverable}}. I will send that over by {{deadline}}.

Let me know if you need anything else in the meantime.

Best,
{{my_name}}
TEXT;
    }

    protected function proposalSentBody(): string
    {
        return <<<'TEXT'
Hi {{first_name}},

I have put together a proposal for {{project_name}}, based on our conversation.

You can view it here: {{proposal_url}}

Let me know if you have any questions or would like me to walk you through it.

Best,
{{my_name}}
TEXT;
    }

    protected function proposalFollowUpBody(): string
    {
        return <<<'TEXT'
Hi {{first_name}},

Just following up on the proposal I sent for {{project_name}}.

Have you had a chance to review it? I am happy to answer any questions or adjust anything if needed.

Best,
{{my_name}}
TEXT;
    }

    protected function clientOnboardingBody(): string
    {
        return <<<'TEXT'
Hi {{first_name}},

Great news — I have scheduled {{project_name}} to start on {{start_date}}.

Before we begin, I will need:
- {{requirement_1}}
- {{requirement_2}}
- {{requirement_3}}

I will send a calendar invite for our kick-off meeting shortly.

Looking forward to working together.

Best,
{{my_name}}
TEXT;
    }

    protected function projectUpdateBody(): string
    {
        return <<<'TEXT'
Hi {{first_name}},

Quick update on {{project_name}}:

{{update_summary}}

Next steps:
- {{next_step_1}}
- {{next_step_2}}

Let me know if you have any questions.

Best,
{{my_name}}
TEXT;
    }
}
