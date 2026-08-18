<?php

namespace App\Http\Controllers\Crm;

use App\Models\Contact;
use App\Models\EmailMessage;
use App\Models\Opportunity;
use App\Models\OutreachAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $this->user($request);
        $start = Carbon::today()->subDays(90);

        $outreachSent = EmailMessage::forUser($user)
            ->where('direction', 'outbound')
            ->where('sent_at', '>=', $start)
            ->count();

        $outreachReplied = EmailMessage::forUser($user)
            ->where('direction', 'outbound')
            ->whereNotNull('replied_at')
            ->where('sent_at', '>=', $start)
            ->count();

        $wonRevenue = (float) Opportunity::forUser($user)
            ->where('stage', 'won')
            ->whereNotNull('won_at')
            ->where('won_at', '>=', $start)
            ->sum('value');

        $stageCounts = Opportunity::forUser($user)
            ->selectRaw('stage, count(*) as total, coalesce(sum(value), 0) as value')
            ->groupBy('stage')
            ->get();

        $weeklyOutreach = OutreachAssignment::forUser($user)
            ->where('assigned_on', '>=', $start)
            ->selectRaw('week(assigned_on) as week, count(*) as total')
            ->groupBy('week')
            ->orderBy('week')
            ->get();

        return Inertia::render('analytics/index', [
            'summary' => [
                'outreach_sent' => $outreachSent,
                'outreach_replied' => $outreachReplied,
                'reply_rate' => $outreachSent > 0 ? round($outreachReplied / $outreachSent * 100, 1) : 0,
                'won_revenue' => $wonRevenue,
                'total_prospects' => Contact::forUser($user)->count(),
                'active_opportunities' => Opportunity::forUser($user)->whereNotIn('stage', ['won', 'lost'])->count(),
            ],
            'stageCounts' => $stageCounts,
            'weeklyOutreach' => $weeklyOutreach,
        ]);
    }
}
