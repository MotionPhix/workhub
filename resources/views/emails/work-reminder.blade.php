<x-mail::message>
  # Daily Work Entry Reminder

  Hello {{ $user->name }},

  This is a friendly reminder to log your work for today. Tracking your daily activities helps in:
  - Maintaining productivity
  - Providing accurate reports
  - Tracking your professional growth

  @component('mail::button', ['url' => route('work-entries.create')])
    Log Work Entry
  @endcomponent

  Best regards,<br>
  {{ config('app.name') }} team
</x-mail::message>
