<?php

namespace App\Http\Controllers;

use App\Events\NotificationPreferencesUpdated;
use App\Models\NotificationPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationPreferenceController extends Controller
{
    public function index()
    {
        $preferences = Auth::user()->notificationPreferences()->first();

        if (!$preferences) {
            $preferences = Auth::user()->notificationPreferences()->create([]);
        }

        return Inertia::render('Profile/NotificationPreferences', [
            'preferences' => $preferences,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'task_assignments' => 'boolean',
            'task_updates' => 'boolean',
            'task_completions' => 'boolean',
            'project_updates' => 'boolean',
            'project_deadlines' => 'boolean',
            'team_updates' => 'boolean',
            'report_submissions' => 'boolean',
            'report_approvals' => 'boolean',
            'system_maintenance' => 'boolean',
            'security_alerts' => 'boolean',
            'email_notifications' => 'boolean',
            'browser_notifications' => 'boolean',
            'mobile_push_notifications' => 'boolean',
            'quiet_hours' => 'nullable|array',
            'quiet_hours.start' => 'nullable|string',
            'quiet_hours.end' => 'nullable|string',
            'quiet_hours.timezone' => 'nullable|string',
            'digest_frequency' => 'nullable|array',
            'weekend_notifications' => 'boolean',
            'minimum_priority' => 'in:low,medium,high,urgent',
            'channel_preferences' => 'nullable|array',
            'sound_enabled' => 'boolean',
            'sound_preference' => 'string',
            'vibration_enabled' => 'boolean',
        ]);

        $preferences = Auth::user()->notificationPreferences()->firstOrCreate([]);
        $preferences->update($validated);

        // Fire the Verbs event
        NotificationPreferencesUpdated::fire(
            userId: Auth::id(),
            preferences: $validated
        );

        return redirect()->back()->with('success', 'Notification preferences updated successfully.');
    }

    public function reset()
    {
        $preferences = Auth::user()->notificationPreferences()->first();

        if ($preferences) {
            $defaultPreferences = [
                'task_assignments' => true,
                'task_updates' => true,
                'task_completions' => true,
                'project_updates' => true,
                'project_deadlines' => true,
                'team_updates' => true,
                'report_submissions' => true,
                'report_approvals' => true,
                'system_maintenance' => true,
                'security_alerts' => true,
                'email_notifications' => true,
                'browser_notifications' => true,
                'mobile_push_notifications' => false,
                'quiet_hours' => null,
                'digest_frequency' => null,
                'weekend_notifications' => false,
                'minimum_priority' => 'low',
                'channel_preferences' => null,
                'sound_enabled' => true,
                'sound_preference' => 'default',
                'vibration_enabled' => true,
            ];

            $preferences->update($defaultPreferences);

            // Fire the Verbs event
            NotificationPreferencesUpdated::fire(
                userId: Auth::id(),
                preferences: $defaultPreferences
            );
        }

        return redirect()->back()->with('success', 'Notification preferences reset to defaults.');
    }
}
