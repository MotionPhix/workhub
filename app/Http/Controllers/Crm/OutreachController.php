<?php

namespace App\Http\Controllers\Crm;

use App\Enums\AssignmentStatus;
use App\Models\Contact;
use App\Models\EmailTemplate;
use App\Models\OutreachAssignment;
use App\Models\OutreachCycle;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class OutreachController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $this->user($request);
        $today = Carbon::today();

        $currentCycle = OutreachCycle::forUser($user)
            ->where('status', 'active')
            ->orderByDesc('starts_on')
            ->first();

        if (! $currentCycle) {
            $currentCycle = OutreachCycle::forUser($user)
                ->orderByDesc('starts_on')
                ->first();
        }

        $assignments = collect();
        $cycle = null;

        if ($currentCycle) {
            $assignments = OutreachAssignment::forUser($user)
                ->where('outreach_cycle_id', $currentCycle->id)
                ->with(['contact.company', 'suggestedService'])
                ->orderBy('rank')
                ->get();

            $cycle = [
                'id' => $currentCycle->id,
                'name' => $currentCycle->name ?? 'Outreach cycle',
                'starts_on' => $currentCycle->starts_on->toDateString(),
                'status' => $currentCycle->status->value,
                'assigned' => $currentCycle->assignment_count,
                'contacted' => $currentCycle->contacted_count,
                'completion' => $currentCycle->completionPercentage(),
            ];
        }

        $templates = EmailTemplate::forUser($user)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'category', 'subject']);

        return Inertia::render('outreach/index', [
            'cycle' => $cycle,
            'assignments' => $assignments,
            'templates' => $templates,
            'today' => $today->toDateString(),
        ]);
    }

    public function markContacted(Request $request, OutreachAssignment $assignment)
    {
        $assignment->update([
            'status' => AssignmentStatus::Contacted,
            'contacted_at' => now(),
        ]);

        $contact = $assignment->contact;
        if ($contact) {
            $contact->update([
                'status' => ContactStatus::Contacted,
                'first_contacted_at' => $contact->first_contacted_at ?? now(),
                'last_contacted_at' => now(),
            ]);
        }

        return back()->with('success', 'Marked as contacted.');
    }

    public function skip(Request $request, OutreachAssignment $assignment)
    {
        $assignment->update([
            'status' => AssignmentStatus::Skipped,
            'skipped_at' => now(),
        ]);

        return back()->with('success', 'Skipped for this cycle.');
    }

    public function snooze(Request $request, OutreachAssignment $assignment)
    {
        $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:30'],
        ]);

        $assignment->update([
            'status' => AssignmentStatus::Snoozed,
            'snoozed_until' => now()->addDays((int) $request->days)->toDateString(),
        ]);

        return back()->with('success', 'Snoozed.');
    }

    public function draftEmail(Request $request, OutreachAssignment $assignment)
    {
        $request->validate([
            'email_template_id' => ['required', 'uuid', 'exists:email_templates,id'],
        ]);

        $template = EmailTemplate::findOrFail($request->email_template_id);
        $contact = $assignment->contact()->with('company')->first();

        $rendered = [
            'subject' => $this->interpolate($template->subject, $contact),
            'body' => $this->interpolate($template->body, $contact),
            'to_email' => $contact?->email,
            'to_name' => $contact?->full_name,
        ];

        return back()->with([
            'draft' => $rendered,
        ]);
    }

    private function interpolate(string $template, ?Contact $contact): string
    {
        if (! $contact) {
            return $template;
        }

        $company = $contact->company;

        return strtr($template, [
            '{{contact_first_name}}' => $contact->first_name,
            '{{contact_full_name}}' => $contact->full_name,
            '{{contact_job_title}}' => $contact->job_title ?? '',
            '{{company_name}}' => $company?->name ?? '',
            '{{company_industry}}' => $company?->industry ?? '',
        ]);
    }
}
