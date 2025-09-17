<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::with(['department:id,uuid,name', 'manager:id,name,email'])
            ->withCount(['workEntries as work_entries_count'])
            ->select(['id','uuid','name','description','department_uuid','manager_id','due_date','status','priority','completion_percentage','project_type','client_name']);

        // Filters
        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority') && $request->priority !== 'all') {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('project_type') && $request->project_type !== 'all') {
            $query->where('project_type', $request->project_type);
        }

        $sortBy = $request->get('sort_by','name');
        $sortDirection = $request->get('sort_direction','asc');
        $allowedSorts = ['name','due_date','priority','completion_percentage','status'];
        if (! in_array($sortBy, $allowedSorts)) { $sortBy = 'name'; }
        $query->orderBy($sortBy, $sortDirection === 'desc' ? 'desc' : 'asc');

        $projects = $query->paginate(15)->appends($request->query());

        return Inertia::render('admin/projects/Index', [
            'projects' => $projects,
            'filters' => [
                'search' => $request->get('search',''),
                'status' => $request->get('status','all'),
                'priority' => $request->get('priority','all'),
                'project_type' => $request->get('project_type','all'),
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
            ],
            'departments' => Department::orderBy('name')->get(['uuid','name']),
        ]);
    }

    public function show(Project $project)
    {
        $project->load([
            'department:id,uuid,name',
            'manager:id,name,email',
            'workEntries' => function ($q) {
                $q->select(['id','user_id','project_uuid','work_title','status','start_date_time','end_date_time','created_at'])
                    ->with('user:id,name')
                    ->latest('start_date_time')
                    ->limit(15);
            },
        ]);

        $allEntries = $project->workEntries()->with('user:id,name')->get();

        $stats = [
            'total_work_entries' => $allEntries->count(),
            'completed_entries' => $allEntries->where('status','completed')->count(),
            'total_hours' => round($allEntries->sum('hours_worked'),2),
            'team_members' => $allEntries->pluck('user_id')->unique()->count(),
            'is_overdue' => $project->isOverdue(),
        ];

        return Inertia::render('admin/projects/Show', [
            'project' => $project,
            'stats' => $stats,
        ]);
    }

    public function create()
    {
        return Inertia::render('admin/projects/Create', [
            'departments' => Department::orderBy('name')->get(['uuid','name']),
            'managers' => User::role(['admin','manager'])->orderBy('name')->get(['id','name','email']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateRequest($request);
        $project = Project::create($validated);

        if (! empty($validated['tags'] ?? [])) {
            $project->syncTags($validated['tags']);
        }

        return redirect()->route('admin.projects.show', $project->uuid)->with('success','Project created');
    }

    public function edit(Project $project)
    {
        return Inertia::render('admin/projects/Edit', [
            'project' => $project->load('tags'),
            'departments' => Department::orderBy('name')->get(['uuid','name']),
            'managers' => User::role(['admin','manager'])->orderBy('name')->get(['id','name','email']),
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $validated = $this->validateRequest($request, $project->id);
        $project->update($validated);
        if ($request->has('tags')) {
            $project->syncTags($validated['tags'] ?? []);
        }
        return back()->with('success','Project updated');
    }

    public function archive(Project $project)
    {
        $newStatus = $project->status === 'cancelled' ? 'active' : 'cancelled';
        $project->update(['status' => $newStatus]);
        return back()->with('success', $newStatus === 'cancelled' ? 'Project archived' : 'Project reactivated');
    }

    public function updateProgress(Project $project)
    {
        try {
            $project->updateProgress();
            return response()->json([
                'success' => true,
                'completion_percentage' => $project->completion_percentage,
                'status' => $project->status,
            ]);
        } catch (\Exception $e) {
            Log::error('Admin project update progress failed', ['error' => $e->getMessage(), 'project_id' => $project->id]);
            return response()->json(['success' => false], 500);
        }
    }

    private function validateRequest(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required','string','max:255'],
            'description' => ['nullable','string','max:2000'],
            'department_uuid' => ['required','uuid','exists:departments,uuid'],
            'manager_id' => ['required','integer','exists:users,id'],
            'start_date' => ['required','date'],
            'due_date' => ['required','date','after:start_date'],
            'status' => ['required','in:planning,active,on_hold,completed,cancelled'],
            'priority' => ['required','in:low,medium,high,urgent'],
            'estimated_hours' => ['nullable','numeric','min:0'],
            'is_shared' => ['boolean'],
            'project_type' => ['required','in:internal,client'],
            'client_name' => ['nullable','string','max:255'],
            'client_contact' => ['nullable','string','max:255'],
            'tags' => ['nullable','array'],
            'tags.*' => ['string','distinct'],
        ]);
    }
}
