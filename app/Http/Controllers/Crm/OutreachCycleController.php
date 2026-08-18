<?php

namespace App\Http\Controllers\Crm;

use App\Models\OutreachCycle;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OutreachCycleController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $this->user($request);

        $cycles = OutreachCycle::forUser($user)
            ->orderByDesc('starts_on')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('outreach/cycles', [
            'cycles' => $cycles,
        ]);
    }

    public function show(Request $request, OutreachCycle $cycle): Response
    {
        $assignments = $cycle->assignments()
            ->with(['contact.company', 'suggestedService'])
            ->orderBy('rank')
            ->get();

        return Inertia::render('outreach/cycle-show', [
            'cycle' => [
                'id' => $cycle->id,
                'name' => $cycle->name ?? 'Outreach cycle',
                'starts_on' => $cycle->starts_on->toDateString(),
                'ends_on' => $cycle->ends_on->toDateString(),
                'status' => $cycle->status->value,
                'completion' => $cycle->completionPercentage(),
            ],
            'assignments' => $assignments,
        ]);
    }
}
