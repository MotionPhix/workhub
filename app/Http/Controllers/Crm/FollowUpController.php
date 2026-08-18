<?php

namespace App\Http\Controllers\Crm;

use App\Enums\FollowUpStatus;
use App\Models\FollowUp;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FollowUpController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $this->user($request);

        $followUps = FollowUp::forUser($user)
            ->with(['contact.company'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderBy('due_on')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('follow-ups/index', [
            'followUps' => $followUps,
            'filters' => [
                'status' => $request->status,
            ],
        ]);
    }

    public function update(Request $request, FollowUp $followUp)
    {
        $data = $request->validate([
            'status' => ['sometimes', 'string', 'in:pending,completed,cancelled'],
            'due_on' => ['sometimes', 'date'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'type' => ['sometimes', 'string'],
        ]);

        if (isset($data['status']) && $data['status'] === FollowUpStatus::Completed->value) {
            $data['completed_at'] = now();
        }

        $followUp->update($data);

        return back()->with('success', 'Follow-up updated.');
    }

    public function destroy(FollowUp $followUp)
    {
        $followUp->delete();

        return back()->with('success', 'Follow-up removed.');
    }
}
