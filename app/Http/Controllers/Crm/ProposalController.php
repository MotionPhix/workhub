<?php

namespace App\Http\Controllers\Crm;

use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\Proposal;
use App\Models\ProposalTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProposalController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $this->user($request);

        $proposals = Proposal::forUser($user)
            ->with(['contact.company', 'latestVersion'])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('proposals/index', [
            'proposals' => $proposals,
        ]);
    }

    public function show(Request $request, Proposal $proposal): Response
    {
        $proposal->load([
            'contact.company',
            'opportunity',
            'versions' => fn ($q) => $q->orderByDesc('version'),
        ]);

        return Inertia::render('proposals/show', [
            'proposal' => $proposal,
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $this->user($request);

        return Inertia::render('proposals/create', [
            'contacts' => Contact::forUser($user)->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'company_id']),
            'templates' => ProposalTemplate::forUser($user)->orderBy('name')->get(['id', 'name']),
            'opportunities' => Opportunity::forUser($user)->orderBy('title')->get(['id', 'title', 'contact_id']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'contact_id' => ['required', 'uuid', 'exists:contacts,id'],
            'title' => ['required', 'string', 'max:255'],
            'proposal_template_id' => ['nullable', 'uuid', 'exists:proposal_templates,id'],
            'opportunity_id' => ['nullable', 'uuid', 'exists:opportunities,id'],
            'status' => ['nullable', 'string'],
        ]);

        $proposal = Proposal::create([
            ...$data,
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('proposals.show', $proposal)
            ->with('success', 'Proposal created.');
    }

    public function edit(Request $request, Proposal $proposal): Response
    {
        $proposal->load(['versions' => fn ($q) => $q->orderByDesc('version')]);

        return Inertia::render('proposals/edit', [
            'proposal' => $proposal,
        ]);
    }

    public function update(Request $request, Proposal $proposal)
    {
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'status' => ['nullable', 'string'],
            'total_amount' => ['nullable', 'numeric'],
            'sent_at' => ['nullable', 'date'],
        ]);

        $proposal->update($data);

        return back()->with('success', 'Proposal updated.');
    }

    public function exportPdf(Request $request, Proposal $proposal)
    {
        $proposal->load(['contact.company', 'versions' => fn ($q) => $q->orderByDesc('version')]);

        // PDF rendering delegated to ProposalPdfRenderer / spatie-laravel-pdf.
        // Stub returns a download route placeholder; full implementation in Phase 8.
        return response()->json([
            'message' => 'PDF export queued',
            'proposal' => $proposal->id,
        ]);
    }
}
