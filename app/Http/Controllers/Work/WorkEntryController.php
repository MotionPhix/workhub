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
  public function index()
  {
    $entries = WorkEntry::where('user_id', Auth::id())
      ->orderBy('work_date', 'desc')
      ->paginate(10);

    return Inertia('WorkEntries/Index', [
      'workEntries' => $entries
    ]);
  }

  public function create(Request $request, ?WorkEntry $entry = null)
  {
    return Inertia::modal('WorkEntries/WorkEntryForm', [
      'workEntry' => $entry ?? new WorkEntry(),
      'projects' => [],
    ])->baseUrl('/work-logs');
  }

  public function store(Request $request)
  {
    try {
      $validated = $request->validate([
        'work_date' => ['required', 'date', 'before_or_equal:today'],
        'description' => ['required', 'string', 'max:1000'],
        'hours_worked' => ['nullable', 'numeric', 'min:0', 'max:24'],
        'tags' => 'nullable|array',
        'status' => 'in:draft,completed,in_progress'
      ]);

      $entry = $request->user()->workEntries()->create($validated);

      return back()->with('success', 'Work entry created successfully');
    } catch (ValidationException $e) {
      return back()
        ->withErrors($e->validator)
        ->withInput()
        ->with('error', 'Please correct the errors in the form.');
    } catch (\Exception $e) {
      Log::error('Work entry creation failed: ' . $e->getMessage());
      return back()->with('error', 'An unexpected error occurred. Please try again.');
    }

  }
}
