<?php

namespace App\Http\Controllers\Crm;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Service;
use App\Models\Tag;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProspectController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $this->user($request);

        $query = Contact::forUser($user)
            ->with(['company', 'tags', 'primaryService'])
            ->when($request->search, fn ($q) => $q->search($request->search))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->temperature, fn ($q) => $q->where('temperature', $request->temperature))
            ->when($request->company_id, fn ($q) => $q->where('company_id', $request->company_id))
            ->orderByDesc('lead_score');

        $contacts = $query->paginate(20)->withQueryString();

        return Inertia::render('prospects/index', [
            'contacts' => $contacts,
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'temperature' => $request->temperature,
                'company_id' => $request->company_id,
            ],
            'companies' => Company::forUser($user)->orderBy('name')->get(['id', 'name']),
            'tags' => Tag::forUser($user)->orderBy('name')->get(['id', 'name', 'color']),
            'services' => Service::forUser($user)->orderBy('sort_order')->get(['id', 'name']),
        ]);
    }

    public function show(Request $request, Contact $prospect): Response
    {
        $user = $this->user($request);

        $prospect->load([
            'company',
            'tags',
            'services',
            'primaryService',
            'emailMessages' => fn ($q) => $q->orderByDesc('sent_at'),
            'opportunities' => fn ($q) => $q->orderByDesc('created_at'),
            'followUps' => fn ($q) => $q->orderBy('due_on'),
            'activities' => fn ($q) => $q->orderByDesc('occurred_at')->limit(25),
        ]);

        return Inertia::render('prospects/show', [
            'contact' => $prospect,
            'tags' => Tag::forUser($user)->orderBy('name')->get(['id', 'name', 'color']),
            'services' => Service::forUser($user)->orderBy('sort_order')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $this->user($request);

        return Inertia::render('prospects/create', [
            'companies' => Company::forUser($user)->orderBy('name')->get(['id', 'name']),
            'tags' => Tag::forUser($user)->orderBy('name')->get(['id', 'name', 'color']),
            'services' => Service::forUser($user)->orderBy('sort_order')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'company_id' => ['nullable', 'uuid', 'exists:companies,id'],
            'status' => ['nullable', 'string'],
            'lead_source' => ['nullable', 'string'],
            'primary_service_id' => ['nullable', 'uuid', 'exists:services,id'],
            'estimated_budget' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['uuid', 'exists:tags,id'],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['uuid', 'exists:services,id'],
        ]);

        $contact = Contact::create([
            ...$data,
            'user_id' => $request->user()->id,
        ]);

        if (! empty($data['tag_ids'])) {
            $contact->tags()->attach($data['tag_ids']);
        }
        if (! empty($data['service_ids'])) {
            $contact->services()->attach($data['service_ids']);
        }

        return redirect()->route('prospects.show', $contact)
            ->with('success', 'Prospect added.');
    }

    public function edit(Request $request, Contact $prospect): Response
    {
        $user = $this->user($request);

        $prospect->load(['tags', 'services']);

        return Inertia::render('prospects/edit', [
            'contact' => $prospect,
            'companies' => Company::forUser($user)->orderBy('name')->get(['id', 'name']),
            'tags' => Tag::forUser($user)->orderBy('name')->get(['id', 'name', 'color']),
            'services' => Service::forUser($user)->orderBy('sort_order')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Contact $prospect)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'company_id' => ['nullable', 'uuid', 'exists:companies,id'],
            'status' => ['nullable', 'string'],
            'lead_source' => ['nullable', 'string'],
            'primary_service_id' => ['nullable', 'uuid', 'exists:services,id'],
            'estimated_budget' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['uuid', 'exists:tags,id'],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['uuid', 'exists:services,id'],
        ]);

        $prospect->update($data);

        if (isset($data['tag_ids'])) {
            $prospect->tags()->sync($data['tag_ids']);
        }
        if (isset($data['service_ids'])) {
            $prospect->services()->sync($data['service_ids']);
        }

        return redirect()->route('prospects.show', $prospect)
            ->with('success', 'Prospect updated.');
    }

    public function destroy(Contact $prospect)
    {
        $prospect->delete();

        return redirect()->route('prospects.index')
            ->with('success', 'Prospect deleted.');
    }
}
