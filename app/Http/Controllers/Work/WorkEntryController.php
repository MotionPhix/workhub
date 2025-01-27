<?php

namespace App\Http\Controllers\Work;

use App\Http\Controllers\Controller;
use App\Models\WorkEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class WorkEntryController extends Controller
{
  private array $rules = [
    'work_date' => ['required', 'date', 'before_or_equal:today'],
    'work_title' => ['required', 'string'],
    'hours_worked' => ['nullable', 'numeric', 'min:0', 'max:24'],
    'description' => ['required', 'string', 'max:1000'],
    'status' => ['required', 'in:draft,completed,in_progress'],
    'tags' => 'nullable|array',
    'tags.*' => 'string|distinct',
  ];

  private array $messages = [
    'work_date.required' => 'The work date is required.',
    'work_date.date' => 'Please provide a valid date.',
    'work_date.before_or_equal' => 'Work date cannot be in the future.',
    'work_title.required' => 'Please provide a title for your work entry.',
    'work_title.string' => 'The work title must be text.',
    'hours_worked.numeric' => 'Hours worked must be a number.',
    'hours_worked.min' => 'Hours worked cannot be negative.',
    'hours_worked.max' => 'Hours worked cannot exceed 24 hours per day.',
    'description.required' => 'Please provide a description of your work.',
    'description.max' => 'Description cannot exceed 1000 characters.',
    'status.in' => 'Invalid status selected.',
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

  public function index()
  {
    $entries = WorkEntry::where('user_id', Auth::id())
      ->with('tags')
      ->orderBy('work_date', 'desc')
      ->paginate(10);

    return Inertia('WorkEntries/Index', [
      'workEntries' => $entries
    ]);
  }

  public function form(Request $request, ?WorkEntry $entry = null)
  {
    return Inertia::modal('WorkEntries/WorkEntryForm', [
      'workLog' => $entry ?? new WorkEntry(),
      'tags' => fn() => \Spatie\Tags\Tag::all()->pluck('name')
    ])->baseUrl('/work-logs');
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
