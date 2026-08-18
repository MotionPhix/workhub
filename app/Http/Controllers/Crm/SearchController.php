<?php

namespace App\Http\Controllers\Crm;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Proposal;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $this->user($request);
        $term = trim((string) $request->search);

        $contacts = collect();
        $companies = collect();
        $proposals = collect();

        if ($term !== '') {
            $contacts = Contact::forUser($user)
                ->with('company')
                ->search($term)
                ->limit(8)
                ->get(['id', 'first_name', 'last_name', 'company_id'])
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'label' => $c->full_name,
                    'sublabel' => $c->company?->name,
                    'type' => 'contact',
                ]);

            $companies = Company::forUser($user)
                ->where(function ($q) use ($term) {
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhere('industry', 'like', "%{$term}%");
                })
                ->limit(8)
                ->get(['id', 'name', 'industry'])
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'label' => $c->name,
                    'sublabel' => $c->industry,
                    'type' => 'company',
                ]);

            $proposals = Proposal::forUser($user)
                ->where('title', 'like', "%{$term}%")
                ->limit(8)
                ->get(['id', 'title'])
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'label' => $p->title,
                    'sublabel' => 'Proposal',
                    'type' => 'proposal',
                ]);
        }

        return Inertia::render('search/index', [
            'term' => $term,
            'results' => [
                'contacts' => $contacts,
                'companies' => $companies,
                'proposals' => $proposals,
            ],
        ]);
    }
}
