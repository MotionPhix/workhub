<?php

namespace App\Http\Controllers;

use App\Models\WorkEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WorkEntryController extends Controller
{
  private array $rules = [
    'work_title' => ['required', 'string'],
    'start_date_time' => ['required', 'date', 'before_or_equal:now'],
    'end_date_time' => ['required', 'date', 'after:start_date_time'],
    'description' => ['required', 'string', 'max:1000'],
    'notes' => ['nullable', 'string', 'max:2000'],
    'status' => ['required', 'in:draft,completed,in_progress'],
    'priority' => ['required', 'in:low,medium,high,urgent'],
    'work_type' => ['required', 'in:task,meeting,call,email,travel,research,presentation,other'],
    'location' => ['nullable', 'string', 'max:255'],
    'project_uuid' => ['nullable', 'uuid', 'exists:projects,uuid'],
    'contacts' => 'nullable|array',
    'contacts.*.name' => 'required_with:contacts|string|max:255',
    'contacts.*.email' => 'nullable|email',
    'contacts.*.phone' => 'nullable|string|max:20',
    'contacts.*.company' => 'nullable|string|max:255',
    'contacts.*.role' => 'nullable|string|max:100',
    'organizations' => 'nullable|array',
    'organizations.*.name' => 'required_with:organizations|string|max:255',
    'organizations.*.type' => 'nullable|string|max:100',
    'organizations.*.website' => 'nullable|url',
    'value_generated' => ['nullable', 'numeric', 'min:0'],
    'outcome' => ['nullable', 'in:successful,partially_successful,unsuccessful,pending,follow_up_needed'],
    'mood' => ['nullable', 'string', 'max:50'],
    'productivity_rating' => ['nullable', 'numeric', 'min:1', 'max:5'],
    'tools_used' => 'nullable|array',
    'tools_used.*' => 'string|max:100',
    'collaborators' => 'nullable|array',
    'collaborators.*.name' => 'required_with:collaborators|string|max:255',
    'collaborators.*.role' => 'nullable|string|max:100',
    'requires_follow_up' => ['boolean'],
    'follow_up_date' => ['nullable', 'date', 'after:today'],
    'weather_condition' => ['nullable', 'string', 'max:50'],
    'tags' => 'nullable|array',
    'tags.*' => 'string|distinct',
  ];

  private array $messages = [
    'work_title.required' => 'Please provide a title for your work entry.',
    'work_title.string' => 'The work title must be text.',
    'start_date_time.required' => 'The start date and time is required.',
    'start_date_time.date' => 'Please provide a valid start date and time.',
    'start_date_time.before_or_equal' => 'Start time cannot be in the future.',
    'end_date_time.required' => 'The end date and time is required.',
    'end_date_time.date' => 'Please provide a valid end date and time.',
    'end_date_time.after' => 'End time must be after start time.',
    'description.required' => 'Please provide a description of your work.',
    'description.max' => 'Description cannot exceed 1000 characters.',
    'notes.max' => 'Notes cannot exceed 2000 characters.',
    'status.in' => 'Invalid status selected.',
    'priority.in' => 'Invalid priority selected.',
    'work_type.in' => 'Invalid work type selected.',
    'project_uuid.uuid' => 'Invalid project selected.',
    'project_uuid.exists' => 'The selected project does not exist.',
    'contacts.array' => 'Contacts must be provided as a list.',
    'contacts.*.name.required_with' => 'Contact name is required.',
    'contacts.*.email.email' => 'Please provide a valid email address.',
    'organizations.array' => 'Organizations must be provided as a list.',
    'organizations.*.name.required_with' => 'Organization name is required.',
    'organizations.*.website.url' => 'Please provide a valid website URL.',
    'value_generated.numeric' => 'Value must be a number.',
    'value_generated.min' => 'Value cannot be negative.',
    'productivity_rating.min' => 'Productivity rating must be between 1 and 5.',
    'productivity_rating.max' => 'Productivity rating must be between 1 and 5.',
    'follow_up_date.after' => 'Follow-up date must be in the future.',
    'tags.array' => 'Tags must be provided as a list.',
    'tags.*.distinct' => 'Duplicate tags are not allowed.',
  ];

  private function handleTags(WorkEntry $entry, array $tags): void
  {
    if (!empty($tags)) {
      $normalizedTags = collect($tags)->map(function ($tag) {
        $existingTag = \Spatie\Tags\Tag::whereRaw('LOWER(name) = ?', [strtolower($tag)])->first();
        return $existingTag ? $existingTag->name : $tag;
      });

      $entry->syncTags($normalizedTags);
    }
  }

  public function index(Request $request)
  {
    $query = WorkEntry::where('user_id', Auth::id());

    // Search functionality
    if ($request->filled('search')) {
      $search = $request->get('search');
      $query->where(function ($q) use ($search) {
        $q->where('work_title', 'like', '%' . $search . '%')
          ->orWhere('description', 'like', '%' . $search . '%')
          ->orWhereHas('tags', function ($tagQuery) use ($search) {
            $tagQuery->where('name->en', 'like', '%' . $search . '%')
                     ->orWhere('name', 'like', '%' . $search . '%');
          });
      });
    }

    // Status filtering
    if ($request->filled('status') && $request->get('status') !== 'all') {
      $query->where('status', $request->get('status'));
    }

    // Date range filtering
    if ($request->filled('date_from')) {
      $query->whereDate('start_date_time', '>=', $request->get('date_from'));
    }

    if ($request->filled('date_to')) {
      $query->whereDate('start_date_time', '<=', $request->get('date_to'));
    }

    // Project filtering
    if ($request->filled('project_uuid')) {
      $query->where('project_uuid', $request->get('project_uuid'));
    }

    // Tag filtering
    if ($request->filled('tag')) {
      $query->whereHas('tags', function ($tagQuery) use ($request) {
        $tagQuery->where('name->en', 'like', '%' . $request->get('tag') . '%')
                 ->orWhere('name', 'like', '%' . $request->get('tag') . '%');
      });
    }

    // Sorting
    $sortBy = $request->get('sort_by', 'start_date_time');
    $sortDirection = $request->get('sort_direction', 'desc');

    $allowedSorts = ['start_date_time', 'work_title', 'status', 'hours_worked', 'created_at'];
    if (in_array($sortBy, $allowedSorts)) {
      $query->orderBy($sortBy, $sortDirection);
    } else {
      $query->orderBy('start_date_time', 'desc');
    }

    $entries = $query->paginate(10)->appends($request->query());

    return Inertia('workentries/Index', [
      'workEntries' => $entries,
      'filters' => [
        'search' => $request->get('search', ''),
        'status' => $request->get('status', 'all'),
        'date_from' => $request->get('date_from', ''),
        'date_to' => $request->get('date_to', ''),
        'project_uuid' => $request->get('project_uuid', ''),
        'tag' => $request->get('tag', ''),
        'sort_by' => $sortBy,
        'sort_direction' => $sortDirection,
      ]
    ]);
  }

  public function show(WorkEntry $entry)
  {
    // Check if user owns the entry
    if ($entry->user_id !== Auth::id()) {
      return redirect()->route('workentries.index')
        ->with('error', 'You are not authorized to view this entry.');
    }

    $entry->load('user');

    return Inertia::render('workentries/Show', [
      'workEntry' => $entry
    ]);
  }

  public function form(Request $request, ?WorkEntry $entry = null)
  {
    return Inertia::render('workentries/WorkEntryForm', [
      'workLog' => $entry ?? new WorkEntry(),
      'tags' => fn() => \Spatie\Tags\Tag::all()->pluck('name')
    ]);
  }

  public function store(Request $request)
  {
    /*try {*/
    $validated = $request->validate($this->rules, $this->messages);

    $entry = $request->user()->workEntries()->create($validated);
    $this->handleTags($entry, $validated['tags'] ?? []);

    return back()->with('flush', 'Work entry created successfully');
  }

  public function update(Request $request, WorkEntry $entry)
  {
    //try {
      // Check if user owns the entry
      if ($entry->user_id !== Auth::id()) {
        return back()->with('error', 'You are not authorized to update this entry.');
      }

      // Validate the request
      $validated = $request->validate($this->rules, $this->messages);

      // Update the entry using the model's updateEntry method
      $updated = $entry->updateEntry($validated);

      if (!$updated) {
        throw new \Exception('Failed to update work entry');
      }

      return back()->with('success', 'Work entry updated successfully');
    /*} catch (ValidationException $e) {
      return back()
        ->withErrors($e->validator)
        ->withInput()
        ->with('error', 'Please correct the errors in the form.');
    } catch (\Exception $e) {
      Log::error('Work entry update failed: ' . $e->getMessage(), [
        'entry_id' => $entry->id,
        'user_id' => Auth::id()
      ]);
      return back()->with('error', 'An unexpected error occurred while updating the entry.');
    }*/
  }
}
