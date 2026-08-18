<?php

namespace App\Http\Controllers\Crm;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $this->user($request);

        $companies = Company::forUser($user)
            ->withCount('contacts')
            ->when($request->search, fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('industry', 'like', "%{$request->search}%")
                    ->orWhere('location', 'like', "%{$request->search}%");
            }))
            ->when($request->organization_type, fn ($q) => $q->where('organization_type', $request->organization_type))
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        return Inertia::render('companies/index', [
            'companies' => $companies,
            'filters' => [
                'search' => $request->search,
                'organization_type' => $request->organization_type,
            ],
        ]);
    }

    public function show(Request $request, Company $company): Response
    {
        $company->load([
            'contacts' => fn ($q) => $q->orderByDesc('lead_score'),
            'opportunities' => fn ($q) => $q->orderByDesc('created_at'),
        ]);

        return Inertia::render('companies/show', [
            'company' => $company,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('companies/create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'string'],
            'organization_type' => ['nullable', 'string'],
            'lead_source' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'what_they_do' => ['nullable', 'string'],
            'estimated_budget' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        $company = Company::create([
            ...$data,
            'user_id' => $request->user()->id,
            'slug' => Str::slug($data['name']).'-'.substr((string) Str::uuid(), 0, 8),
        ]);

        return redirect()->route('companies.show', $company)
            ->with('success', 'Company added.');
    }

    public function edit(Company $company): Response
    {
        return Inertia::render('companies/edit', [
            'company' => $company,
        ]);
    }

    public function update(Request $request, Company $company)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'string'],
            'organization_type' => ['nullable', 'string'],
            'lead_source' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'what_they_do' => ['nullable', 'string'],
            'estimated_budget' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        $company->update($data);

        return redirect()->route('companies.show', $company)
            ->with('success', 'Company updated.');
    }

    public function destroy(Company $company)
    {
        $company->delete();

        return redirect()->route('companies.index')
            ->with('success', 'Company deleted.');
    }
}
