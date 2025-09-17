<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ProjectController extends Controller
{
    private array $rules = [
        'name' => ['required', 'string', 'max:255'],
        'description' => ['nullable', 'string', 'max:2000'],
        'department_uuid' => ['required', 'uuid', 'exists:departments,uuid'],
        'manager_id' => ['required', 'integer', 'exists:users,id'],
        'start_date' => ['required', 'date'],
        'due_date' => ['required', 'date', 'after:start_date'],
        'status' => ['required', 'in:planning,active,on_hold,completed,cancelled'],
        'priority' => ['required', 'in:low,medium,high,urgent'],
        'estimated_hours' => ['nullable', 'numeric', 'min:0'],
        'is_shared' => ['boolean'],
        'tags' => 'nullable|array',
        'tags.*' => 'string|distinct',
    ];

    private array $messages = [
        'name.required' => 'Project name is required.',
        'department_uuid.required' => 'Please select a department.',
        'department_uuid.exists' => 'Selected department does not exist.',
        'manager_id.required' => 'Please assign a project manager.',
        'manager_id.exists' => 'Selected manager does not exist.',
        'start_date.required' => 'Project start date is required.',
        'due_date.required' => 'Project due date is required.',
        'due_date.after' => 'Due date must be after start date.',
        'status.in' => 'Invalid project status.',
        'priority.in' => 'Invalid project priority.',
        'estimated_hours.min' => 'Estimated hours cannot be negative.',
    ];

    public function index(Request $request)
    {
        $query = Project::with(['department', 'manager', 'workEntries'])
            ->withCount('workEntries');

        // Filter by department if user has department restrictions
        $user = Auth::user();
        if ($user->department_uuid && ! $user->hasRole('admin')) {
            $query->where('department_uuid', $user->department_uuid);
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        // Status filtering
        if ($request->filled('status') && $request->get('status') !== 'all') {
            $query->where('status', $request->get('status'));
        }

        // Priority filtering
        if ($request->filled('priority') && $request->get('priority') !== 'all') {
            $query->where('priority', $request->get('priority'));
        }

        // Department filtering
        if ($request->filled('department') && $request->get('department') !== 'all') {
            $query->where('department_uuid', $request->get('department'));
        }

        // Overdue filter
        if ($request->filled('overdue') && $request->boolean('overdue')) {
            $query->overdue();
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        $allowedSorts = ['name', 'status', 'priority', 'due_date', 'completion_percentage', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $projects = $query->paginate(12)->appends($request->query());

        return Inertia::render('projects/Index', [
            'projects' => $projects,
            'departments' => Department::orderBy('name')->get(['uuid', 'name']),
            'filters' => [
                'search' => $request->get('search', ''),
                'status' => $request->get('status', 'all'),
                'priority' => $request->get('priority', 'all'),
                'department' => $request->get('department', 'all'),
                'overdue' => $request->boolean('overdue'),
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
            ],
        ]);
    }

    public function show(Project $project)
    {
        // Load relationships and work entry statistics
        $project->load([
            'department',
            'manager',
            'workEntries' => function ($query) {
                $query->with('user')
                    ->orderBy('start_date_time', 'desc')
                    ->limit(10);
            },
        ]);

        // Calculate project statistics
        // We already loaded a limited subset of workEntries for display; get full collection for stats needing accessors
        $allEntries = $project->workEntries()->with('user')->get();

        $stats = [
            'total_work_entries' => $allEntries->count(),
            'completed_entries' => $allEntries->where('status', 'completed')->count(),
            'total_hours' => round($allEntries->sum('hours_worked'), 2),
            'team_members' => $allEntries->pluck('user_id')->unique()->count(),
            'avg_daily_progress' => $this->calculateDailyProgress($project),
        ];

        return Inertia::render('projects/Show', [
            'project' => $project,
            'stats' => $stats,
        ]);
    }

    public function create()
    {
        return Inertia::render('projects/Create', [
            'departments' => Department::orderBy('name')->get(['uuid', 'name']),
            'managers' => User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['manager', 'admin']);
            })->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate($this->rules, $this->messages);

            $project = Project::create($validated);

            // Handle tags if provided
            if (! empty($validated['tags'])) {
                $project->syncTags($validated['tags']);
            }

            return redirect()->route('projects.show', $project)
                ->with('success', 'Project created successfully!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withErrors($e->validator)
                ->withInput()
                ->with('error', 'Please correct the validation errors.');

        } catch (\Exception $e) {
            Log::error('Project creation failed', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return back()->with('error', 'An unexpected error occurred while creating the project.');
        }
    }

    public function edit(Project $project)
    {
        return Inertia::render('projects/Edit', [
            'project' => $project->load('tags'),
            'departments' => Department::orderBy('name')->get(['uuid', 'name']),
            'managers' => User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['manager', 'admin']);
            })->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function update(Request $request, Project $project)
    {
        try {
            $validated = $request->validate($this->rules, $this->messages);

            $project->update($validated);

            // Handle tags if provided
            if (array_key_exists('tags', $validated)) {
                $project->syncTags($validated['tags'] ?? []);
            }

            return back()->with('success', 'Project updated successfully!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withErrors($e->validator)
                ->withInput()
                ->with('error', 'Please correct the validation errors.');

        } catch (\Exception $e) {
            Log::error('Project update failed', [
                'error' => $e->getMessage(),
                'project_id' => $project->id,
                'data' => $request->all(),
            ]);

            return back()->with('error', 'An unexpected error occurred while updating the project.');
        }
    }

    public function destroy(Project $project)
    {
        try {
            // Check if project has work entries
            if ($project->workEntries()->exists()) {
                return back()->with('error', 'Cannot delete project that has work entries. Archive it instead.');
            }

            $project->delete();

            return redirect()->route('projects.index')
                ->with('success', 'Project deleted successfully!');

        } catch (\Exception $e) {
            Log::error('Project deletion failed', [
                'error' => $e->getMessage(),
                'project_id' => $project->id,
            ]);

            return back()->with('error', 'An unexpected error occurred while deleting the project.');
        }
    }

    /**
     * Archive/Unarchive project (soft status change)
     */
    public function archive(Project $project)
    {
        try {
            $newStatus = $project->status === 'cancelled' ? 'active' : 'cancelled';
            $project->update(['status' => $newStatus]);

            $message = $newStatus === 'cancelled' ? 'Project archived successfully!' : 'Project reactivated successfully!';

            return back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Project archive failed', [
                'error' => $e->getMessage(),
                'project_id' => $project->id,
            ]);

            return back()->with('error', 'An unexpected error occurred.');
        }
    }

    /**
     * Update project completion percentage
     */
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
            Log::error('Project progress update failed', [
                'error' => $e->getMessage(),
                'project_id' => $project->id,
            ]);

            return response()->json(['success' => false], 500);
        }
    }

    private function calculateDailyProgress(Project $project): float
    {
        if (! $project->start_date) {
            return 0;
        }

        $daysSinceStart = max($project->start_date->diffInDays(now()), 1);

        return round($project->completion_percentage / $daysSinceStart, 2);
    }
}
