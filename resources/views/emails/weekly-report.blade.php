<x-mail::message>
  # Weekly Work Report for {{ $user->name }}

  ## Report Period: {{ $weekStart->format('M d, Y') }} - {{ $weekEnd->format('M d, Y') }}

  ### Work Entries Summary

  @if($workEntries->isNotEmpty())
    | Date | Project | Hours Worked | Description |
    |------|---------|--------------|-------------|
    @foreach($workEntries as $entry)
      | {{ $entry->work_date->format('M d, Y') }} | {{ $entry->project ?? 'N/A' }} | {{ $entry->hours_worked }} | {{ $entry->description }} |
    @endforeach
  @else
    No work entries were recorded this week.
  @endif

  ### Total Hours Worked: {{ $workEntries->sum('hours_worked') }}

  Thank you for using WorkHub!

  {{ config('app.name') }}
</x-mail::message>
