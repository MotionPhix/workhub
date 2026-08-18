<?php

namespace App\Http\Controllers\Crm;

use App\Enums\AssignmentStatus;
use App\Enums\OpportunityStage;
use App\Models\Activity;
use App\Models\Contact;
use App\Models\FollowUp;
use App\Models\Goal;
use App\Models\Opportunity;
use App\Models\OutreachAssignment;
use App\Models\OutreachCycle;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Today's morning briefing: who to contact, next best prospects, pipeline state.
     */
    public function index(Request $request): Response
    {
        $user = $this->user($request);
        $today = Carbon::today();

        $currentCycle = OutreachCycle::forUser($user)
            ->where('status', 'active')
            ->orderByDesc('starts_on')
            ->first();

        $todayOutreach = collect();
        $cycleStats = null;

        if ($currentCycle) {
            $todayOutreach = OutreachAssignment::forUser($user)
                ->where('outreach_cycle_id', $currentCycle->id)
                ->where('assigned_on', '<=', $today)
                ->whereIn('status', [AssignmentStatus::Pending, AssignmentStatus::Snoozed])
                ->with(['contact.company', 'suggestedService'])
                ->orderBy('rank')
                ->get();

            $cycleStats = [
                'name' => $currentCycle->name ?? 'Current cycle',
                'week_label' => $today->format('W'),
                'assigned' => $currentCycle->assignment_count,
                'contacted' => $currentCycle->contacted_count,
                'remaining' => OutreachAssignment::forUser($user)
                    ->where('outreach_cycle_id', $currentCycle->id)
                    ->whereIn('status', [AssignmentStatus::Pending, AssignmentStatus::Snoozed])
                    ->count(),
            ];
        }

        $followUps = FollowUp::forUser($user)
            ->with(['contact.company'])
            ->pending()
            ->where('due_on', '<=', $today->copy()->addDays(7))
            ->orderBy('due_on')
            ->get()
            ->map(fn (FollowUp $followUp) => [
                'id' => $followUp->id,
                'type' => $followUp->type->value,
                'due_on' => $followUp->due_on->toDateString(),
                'is_overdue' => $followUp->isOverdue(),
                'is_due_today' => $followUp->isDueToday(),
                'contact' => [
                    'id' => $followUp->contact->id,
                    'full_name' => $followUp->contact->full_name,
                    'company' => $followUp->contact->company?->name,
                ],
            ]);

        $recentActivity = Activity::forUser($user)
            ->where('occurred_at', '>=', $today->copy()->subDays(7))
            ->count();

        $nextBestProspects = Inertia::defer(function () use ($user, $today) {
            return Contact::forUser($user)
                ->contactable()
                ->with('company')
                ->orderByDesc('lead_score')
                ->limit(10)
                ->get()
                ->map(fn (Contact $contact) => [
                    'id' => $contact->id,
                    'full_name' => $contact->full_name,
                    'company' => $contact->company?->name,
                    'job_title' => $contact->job_title,
                    'status' => $contact->status->value,
                    'temperature' => $contact->temperature->value,
                    'lead_score' => $contact->lead_score,
                    'estimated_budget' => $contact->estimated_budget,
                    'last_contacted_at' => $contact->last_contacted_at?->toDateString(),
                    'days_since_contact' => $contact->last_contacted_at
                        ? $contact->last_contacted_at->copy()->startOfDay()->diffInDays($today)
                        : null,
                ]);
        });

        $pipelineSummary = Inertia::defer(function () use ($user) {
            $stages = OpportunityStage::cases();

            return collect($stages)->map(function (OpportunityStage $stage) use ($user) {
                $opportunities = Opportunity::forUser($user)
                    ->where('stage', $stage)
                    ->get();

                return [
                    'stage' => $stage->value,
                    'count' => $opportunities->count(),
                    'value' => (float) $opportunities->sum('value'),
                ];
            })->all();
        });

        $monthlyGoal = Inertia::defer(function () use ($user, $today) {
            $goal = Goal::forUser($user)
                ->forMonth($today)
                ->first();

            if (! $goal) {
                return null;
            }

            $wonThisMonth = Opportunity::forUser($user)
                ->where('stage', OpportunityStage::Won)
                ->whereNotNull('won_at')
                ->where('won_at', '>=', $today->copy()->startOfMonth())
                ->where('won_at', '<=', $today->copy()->endOfMonth())
                ->sum('value');

            return [
                'revenue_target' => (float) $goal->revenue_target,
                'revenue_current' => (float) $wonThisMonth,
                'progress' => $goal->revenue_target > 0
                    ? min(100, round((float) $wonThisMonth / (float) $goal->revenue_target * 100))
                    : 0,
            ];
        });

        return Inertia::render('dashboard', [
            'todayOutreach' => $todayOutreach,
            'cycleStats' => $cycleStats,
            'followUps' => $followUps,
            'recentActivity' => $recentActivity,
            'nextBestProspects' => $nextBestProspects,
            'pipelineSummary' => $pipelineSummary,
            'monthlyGoal' => $monthlyGoal,
        ]);
    }
}
