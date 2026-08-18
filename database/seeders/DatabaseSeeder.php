<?php

namespace Database\Seeders;

use App\Enums\ActivityType;
use App\Enums\AssignmentStatus;
use App\Enums\ContactStatus;
use App\Enums\EmailStatus;
use App\Enums\OpportunityStage;
use App\Enums\OutreachStrategy;
use App\Enums\ProposalStatus;
use App\Models\Activity;
use App\Models\Client;
use App\Models\Company;
use App\Models\Contact;
use App\Models\EmailMessage;
use App\Models\FollowUp;
use App\Models\Opportunity;
use App\Models\OutreachAssignment;
use App\Models\OutreachCycle;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\ProposalTemplate;
use App\Models\ProposalVersion;
use App\Models\Service;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class DatabaseSeeder extends Seeder
{
    protected User $user;

    protected Carbon $today;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->today = Carbon::today();

        $this->user = User::factory()->create([
            'name' => 'Kingsley Nyirenda',
            'email' => 'hello@ultrashots.net',
        ]);

        Auth::login($this->user);

        $this->call([
            ServicesSeeder::class,
            TagsSeeder::class,
            EmailTemplatesSeeder::class,
            ProposalTemplatesSeeder::class,
            GoalsSeeder::class,
        ]);

        $this->seedProspectsAndOutreach();
        $this->seedClientsAndProjects();

        Auth::logout();
    }

    /**
     * Seed companies, contacts, outreach cycles, assignments, emails, follow-ups, and activities.
     */
    protected function seedProspectsAndOutreach(): void
    {
        $services = Service::all();
        $tags = Tag::all();

        // Create real Malawian organisations
        $fpam = Company::factory()->ngo()->researched()->create([
            'name' => 'Family Planning Association of Malawi',
            'website' => 'https://fpam.org.mw',
            'industry' => 'NGO / Development',
            'location' => 'Lilongwe',
            'description' => 'FPAM is a leading sexual and reproductive health rights organisation in Malawi.',
            'what_they_do' => 'Provides family planning services, advocacy, and youth programs across Malawi.',
        ]);

        $mra = Company::factory()->researched()->create([
            'name' => 'Malawi Revenue Authority',
            'website' => 'https://mra.mw',
            'industry' => 'Professional Services',
            'location' => 'Blantyre',
        ]);

        $tevet = Company::factory()->researched()->create([
            'name' => 'TEVET Authority',
            'website' => null,
            'industry' => 'Education',
            'location' => 'Lilongwe',
        ]);

        // Generate additional prospect companies
        $companies = collect([$fpam, $mra, $tevet])
            ->merge(Company::factory()->count(42)->create())
            ->merge(Company::factory()->ngo()->count(15)->create())
            ->merge(Company::factory()->researched()->count(20)->create());

        // Create contacts for each company
        $contacts = collect();
        foreach ($companies as $company) {
            $primaryContact = Contact::factory()
                ->for($company)
                ->primary()
                ->create();

            $contacts->push($primaryContact);

            // Some companies have multiple contacts
            if (fake()->boolean(30)) {
                $secondaryContact = Contact::factory()
                    ->for($company)
                    ->create();
                $contacts->push($secondaryContact);
            }
        }

        // Attach services and tags
        foreach ($contacts as $contact) {
            $contact->services()->attach($services->random(fake()->numberBetween(1, 3)));
            $contact->tags()->attach($tags->random(fake()->numberBetween(0, 2)));
        }

        // Seed outreach cycles for the past 3 months and this month
        for ($i = 3; $i >= 0; $i--) {
            $weekStart = $this->today->copy()->subMonths($i)->startOfMonth()->next('Monday');

            for ($week = 1; $week <= 4; $week++) {
                $cycle = OutreachCycle::factory()
                    ->forWeek($weekStart)
                    ->create();

                // Assign 20-30 contacts per cycle
                $cycleContacts = $contacts->random(fake()->numberBetween(20, 30));

                foreach ($cycleContacts as $contact) {
                    $assignment = OutreachAssignment::factory()
                        ->for($cycle)
                        ->for($contact)
                        ->create();

                    // Past cycles have been contacted
                    if ($i > 0 || $week < 3) {
                        $this->processAssignment($contact, $assignment, $cycle);
                    }
                }

                $weekStart->addWeek();
            }
        }

        // Create some standalone emails not part of cycles
        $recentContacts = $contacts->random(10);
        foreach ($recentContacts as $contact) {
            EmailMessage::factory()
                ->for($contact)
                ->sent(fake()->numberBetween(10, 40))
                ->create([
                    'company_id' => $contact->company_id,
                ]);
        }

        // Seed opportunities
        $interestedContacts = $contacts->where('status', ContactStatus::Interested)->take(15);
        foreach ($interestedContacts as $contact) {
            Opportunity::factory()
                ->for($contact)
                ->create([
                    'company_id' => $contact->company_id,
                    'service_id' => $contact->services->first()?->id,
                ]);
        }

        // Seed proposals for some opportunities
        $opportunities = Opportunity::whereIn('stage', [
            OpportunityStage::Proposal,
            OpportunityStage::Negotiation,
            OpportunityStage::Won,
        ])->get();

        $proposalTemplate = ProposalTemplate::where('is_default', true)->first();

        foreach ($opportunities as $opportunity) {
            $proposal = Proposal::factory()
                ->forOpportunity($opportunity)
                ->fromTemplate($proposalTemplate)
                ->sent(fake()->numberBetween(5, 30))
                ->create();

            $version = ProposalVersion::factory()
                ->for($proposal)
                ->create([
                    'sections' => $proposalTemplate->sections,
                    'sent_at' => $proposal->sent_at,
                    'valid_until' => $proposal->valid_until,
                ]);

            $version->recalculateTotals();
            $version->save();

            $proposal->update([
                'total_amount' => $version->total_amount,
            ]);

            // Some proposals have been viewed
            if (fake()->boolean(60)) {
                $proposal->update([
                    'status' => ProposalStatus::Viewed,
                    'viewed_at' => $proposal->sent_at->copy()->addDays(fake()->numberBetween(1, 5)),
                    'view_count' => fake()->numberBetween(1, 8),
                ]);
            }

            // A few have been accepted
            if ($opportunity->stage === OpportunityStage::Won && fake()->boolean(70)) {
                $proposal->update([
                    'status' => ProposalStatus::Accepted,
                    'accepted_at' => $proposal->sent_at->copy()->addDays(fake()->numberBetween(3, 10)),
                ]);
            }
        }

        // Seed follow-ups
        $contactsNeedingFollowUp = Contact::whereIn('status', [
            ContactStatus::Contacted,
            ContactStatus::Interested,
        ])->get();

        foreach ($contactsNeedingFollowUp->random(min(25, $contactsNeedingFollowUp->count())) as $contact) {
            $latestEmail = $contact->latestEmailMessage;

            $followUp = FollowUp::factory()
                ->for($contact)
                ->create([
                    'email_message_id' => $latestEmail?->id,
                ]);

            // Some are overdue
            if (fake()->boolean(20)) {
                $followUp->update([
                    'due_on' => $this->today->copy()->subDays(fake()->numberBetween(1, 10)),
                ]);
            }

            // A few are due today
            if (fake()->boolean(15)) {
                $followUp->update([
                    'due_on' => $this->today,
                ]);
            }
        }
    }

    /**
     * Process an outreach assignment, sending an email and logging activity.
     */
    protected function processAssignment(Contact $contact, OutreachAssignment $assignment, OutreachCycle $cycle): void
    {
        $daysAgo = $this->today->diffInDays($cycle->starts_on) + fake()->numberBetween(0, 3);

        $email = EmailMessage::factory()
            ->for($contact)
            ->sent($daysAgo)
            ->create([
                'company_id' => $contact->company_id,
                'outreach_cycle_id' => $cycle->id,
                'outreach_assignment_id' => $assignment->id,
                'strategy' => OutreachStrategy::Cold,
            ]);

        $assignment->update([
            'status' => AssignmentStatus::Contacted,
            'contacted_at' => $email->sent_at,
        ]);

        $contact->update([
            'status' => ContactStatus::Contacted,
            'last_contacted_at' => $email->sent_at,
            'cooldown_until' => $email->sent_at->copy()->addDays(30),
        ]);

        Activity::factory()
            ->forContact($contact)
            ->type(ActivityType::EmailSent)
            ->create([
                'subject_type' => EmailMessage::class,
                'subject_id' => $email->id,
                'occurred_at' => $email->sent_at,
            ]);

        // Some have opened
        if (fake()->boolean(40)) {
            $email->update([
                'status' => EmailStatus::Opened,
                'opened_at' => $email->sent_at->copy()->addDays(fake()->numberBetween(1, 5)),
                'opens' => fake()->numberBetween(1, 3),
            ]);
        }

        // A few have replied
        if (fake()->boolean(10)) {
            $email->update([
                'status' => EmailStatus::Replied,
                'replied_at' => $email->sent_at->copy()->addDays(fake()->numberBetween(2, 7)),
            ]);

            $contact->update([
                'status' => fake()->randomElement([ContactStatus::Replied, ContactStatus::Interested]),
            ]);

            // Log the inbound reply
            $reply = EmailMessage::factory()
                ->for($contact)
                ->inbound()
                ->create([
                    'company_id' => $contact->company_id,
                    'parent_id' => $email->id,
                    'sent_at' => $email->replied_at,
                ]);

            Activity::factory()
                ->forContact($contact)
                ->type(ActivityType::EmailReplied)
                ->create([
                    'subject_type' => EmailMessage::class,
                    'subject_id' => $reply->id,
                    'occurred_at' => $reply->sent_at,
                ]);
        }
    }

    /**
     * Seed clients and their projects.
     */
    protected function seedClientsAndProjects(): void
    {
        $services = Service::all();

        // Convert some won opportunities into clients
        $wonOpportunities = Opportunity::where('stage', OpportunityStage::Won)->get();

        foreach ($wonOpportunities->take(8) as $opportunity) {
            $contact = $opportunity->contact;
            $company = $opportunity->company;

            $client = Client::factory()
                ->fromOpportunity($opportunity)
                ->create([
                    'name' => $company?->name ?? $contact->full_name,
                ]);

            $company?->update(['is_client' => true]);

            Activity::factory()
                ->forContact($contact)
                ->type(ActivityType::ClientConverted)
                ->create([
                    'subject_type' => Client::class,
                    'subject_id' => $client->id,
                    'occurred_at' => $opportunity->won_at ?? Carbon::now()->subDays(fake()->numberBetween(5, 60)),
                ]);

            // Create 1-3 projects per client
            $projectCount = fake()->numberBetween(1, 3);
            for ($i = 0; $i < $projectCount; $i++) {
                Project::factory()
                    ->for($client)
                    ->forService($services->random())
                    ->{fake()->randomElement(['inProgress', 'completed'])}()
                    ->create();
            }
        }

        // Add a couple of retainer clients
        $retainerClients = Client::factory()
            ->count(2)
            ->onRetainer()
            ->create();

        foreach ($retainerClients as $client) {
            Project::factory()
                ->for($client)
                ->inProgress()
                ->create([
                    'name' => 'Monthly retainer work',
                ]);
        }
    }
}
