<?php

namespace App\Http\Controllers\Crm;

use App\Enums\OpportunityStage;
use App\Models\Opportunity;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PipelineController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $this->user($request);

        $opportunities = Opportunity::forUser($user)
            ->with(['contact.company', 'service'])
            ->orderBy('stage')
            ->orderByDesc('value')
            ->get();

        $stages = collect(OpportunityStage::cases())->map(function (OpportunityStage $stage) use ($opportunities) {
            $items = $opportunities->where('stage', $stage);

            return [
                'stage' => $stage->value,
                'count' => $items->count(),
                'value' => (float) $items->sum('value'),
                'opportunities' => $items->values(),
            ];
        });

        return Inertia::render('pipeline/index', [
            'stages' => $stages,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'contact_id' => ['required', 'uuid', 'exists:contacts,id'],
            'title' => ['required', 'string', 'max:255'],
            'stage' => ['required', 'string'],
            'value' => ['nullable', 'numeric'],
            'service_id' => ['nullable', 'uuid', 'exists:services,id'],
            'probability' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $opportunity = Opportunity::create([
            ...$data,
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('pipeline')->with('success', 'Opportunity created.');
    }

    public function updateStage(Request $request, Opportunity $opportunity)
    {
        $request->validate([
            'stage' => ['required', 'string'],
        ]);

        $opportunity->update(['stage' => $request->stage]);

        if ($request->stage === OpportunityStage::Won->value && ! $opportunity->won_at) {
            $opportunity->update(['won_at' => now()]);
        }

        return back()->with('success', 'Stage updated.');
    }

    public function update(Request $request, Opportunity $opportunity)
    {
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'value' => ['nullable', 'numeric'],
            'probability' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'expected_close_on' => ['nullable', 'date'],
        ]);

        $opportunity->update($data);

        return back()->with('success', 'Opportunity updated.');
    }
}
