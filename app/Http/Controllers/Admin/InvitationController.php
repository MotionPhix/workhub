<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkInvitationRequest;
use App\Http\Requests\Admin\SendInvitationRequest;
use App\Models\Department;
use App\Models\User;
use App\Models\UserInvite;
use App\Services\Auth\InvitationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class InvitationController extends Controller
{
    public function __construct(private InvitationService $invitationService) {}

    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => 'nullable|in:pending,accepted,declined,expired',
            'department' => 'nullable|string',
            'search' => 'nullable|string|max:255',
        ]);

        $query = UserInvite::with(['inviter', 'department', 'manager'])
            ->orderByDesc('invited_at');

        if (! empty($filters['status'])) {
            if ($filters['status'] === 'expired') {
                $query->expired();
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (! empty($filters['department'])) {
            $query->whereHas('department', function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['department']}%");
            });
        }

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('email', 'like', "%{$filters['search']}%")
                    ->orWhere('name', 'like', "%{$filters['search']}%");
            });
        }

        $invitations = $query->paginate(15);

        return Inertia::render('admin/invitations/Index', [
            'invitations' => $invitations,
            'filters' => $filters,
            'stats' => $this->invitationService->getInvitationStats(),
            'departments' => Department::select('uuid', 'name')->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('shared/InviteUser', [
            'departments' => Department::select('uuid as id', 'name')->get(),
            'roles' => \Spatie\Permission\Models\Role::select('name as id', 'name')->get(),
            'managers' => User::role(['manager', 'admin'])
                ->select('id', 'name', 'email')
                ->get(),
            'currentUser' => auth()->user()->only(['id', 'name', 'email']),
            'isManager' => false,
            'isAdmin' => true,
        ]);
    }

    public function store(SendInvitationRequest $request)
    {
        try {
            $invite = $this->invitationService->sendInvitation(
                $request->validated(),
                auth()->user()
            );

            return redirect()->route('admin.invitations.index')
                ->with('success', "Invitation sent successfully to {$invite->email}");

        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['email' => $e->getMessage()])
                ->withInput();
        }
    }

    public function show(UserInvite $invitation)
    {
        $invitation->load(['inviter', 'department', 'manager']);

        return Inertia::render('admin/invitations/Show', [
            'invitation' => $invitation,
        ]);
    }

    public function sendReminder(UserInvite $invitation)
    {
        if (! $this->invitationService->sendReminder($invitation)) {
            return redirect()->back()
                ->withErrors(['message' => 'Cannot send reminder for this invitation.']);
        }

        return redirect()->back()
            ->with('success', 'Reminder sent successfully.');
    }

    public function extend(Request $request, UserInvite $invitation)
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:30',
        ]);

        if (! $this->invitationService->extendInvitation($invitation, $request->days)) {
            return redirect()->back()
                ->withErrors(['message' => 'Cannot extend this invitation.']);
        }

        return redirect()->back()
            ->with('success', "Invitation extended by {$request->days} days.");
    }

    public function cancel(UserInvite $invitation)
    {
        if (! $this->invitationService->cancelInvitation($invitation, auth()->user())) {
            return redirect()->back()
                ->withErrors(['message' => 'Cannot cancel this invitation.']);
        }

        return redirect()->back()
            ->with('success', 'Invitation cancelled successfully.');
    }

    public function bulkCreate()
    {
        return Inertia::render('admin/invitations/BulkCreate', [
            'departments' => Department::select('uuid', 'name')->get(),
            'managers' => User::role(['manager', 'admin'])
                ->select('id', 'name', 'email')
                ->get(),
            'roles' => \Spatie\Permission\Models\Role::select('id', 'name')->get(),
        ]);
    }

    public function bulkStore(BulkInvitationRequest $request)
    {
        try {
            $results = $this->invitationService->bulkInvite(
                $request->validated()['invitations'],
                auth()->user()
            );

            $successCount = count($results['success']);
            $failedCount = count($results['failed']);

            $message = "Bulk invitation completed. {$successCount} sent successfully";
            if ($failedCount > 0) {
                $message .= ", {$failedCount} failed";
            }

            return redirect()->route('admin.invitations.index')
                ->with('success', $message)
                ->with('bulk_results', $results);

        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['message' => 'Bulk invitation failed: '.$e->getMessage()])
                ->withInput();
        }
    }

    public function bulkInvite(BulkInvitationRequest $request)
    {
        return $this->bulkStore($request);
    }

    public function export(Request $request)
    {
        Gate::authorize('export-invitations');

        $filters = $request->validate([
            'status' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $query = UserInvite::with(['inviter', 'department']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['start_date'])) {
            $query->where('invited_at', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->where('invited_at', '<=', $filters['end_date']);
        }

        $invitations = $query->get();

        return response()->streamDownload(function () use ($invitations) {
            $output = fopen('php://output', 'w');

            // CSV headers
            fputcsv($output, [
                'Email',
                'Name',
                'Status',
                'Department',
                'Manager',
                'Role',
                'Invited By',
                'Invited At',
                'Expires At',
                'Accepted At',
                'Reminder Count',
            ]);

            // CSV data
            foreach ($invitations as $invitation) {
                fputcsv($output, [
                    $invitation->email,
                    $invitation->name,
                    $invitation->getStatusDisplay(),
                    $invitation->department?->name,
                    $invitation->manager_email,
                    $invitation->role_name,
                    $invitation->inviter?->name,
                    $invitation->invited_at?->format('Y-m-d H:i:s'),
                    $invitation->expires_at?->format('Y-m-d H:i:s'),
                    $invitation->accepted_at?->format('Y-m-d H:i:s'),
                    $invitation->reminder_count,
                ]);
            }

            fclose($output);
        }, 'invitations-export-'.now()->format('Y-m-d').'.csv');
    }

    public function cleanupExpired()
    {
        Gate::authorize('cleanup-invitations');

        $deleted = $this->invitationService->cleanupExpiredInvitations();

        return redirect()->back()
            ->with('success', "Cleaned up {$deleted} expired invitations.");
    }

    public function resend(UserInvite $invitation)
    {
        if (! $invitation->isPending()) {
            return redirect()->back()
                ->withErrors(['message' => 'Cannot resend this invitation.']);
        }

        // Generate new token and extend expiry
        $invitation->generateToken();
        $invitation->extend(7);

        // Send new invitation
        \App\Jobs\SendUserInviteJob::dispatch($invitation, null, false)->onQueue('emails');

        return redirect()->back()
            ->with('success', 'Invitation resent successfully.');
    }

    public function statistics()
    {
        Gate::authorize('view-invitation-statistics');

        $stats = $this->invitationService->getInvitationStats();

        // Additional detailed statistics
        $monthlyStats = UserInvite::selectRaw('
                YEAR(invited_at) as year,
                MONTH(invited_at) as month,
                status,
                COUNT(*) as count
            ')
            ->where('invited_at', '>=', now()->subYear())
            ->groupBy('year', 'month', 'status')
            ->get()
            ->groupBy(['year', 'month']);

        $departmentStats = UserInvite::join('departments', 'user_invites.department_uuid', '=', 'departments.uuid')
            ->selectRaw('departments.name, user_invites.status, COUNT(*) as count')
            ->groupBy('departments.name', 'user_invites.status')
            ->get()
            ->groupBy('name');

        return Inertia::render('admin/invitations/Statistics', [
            'overview' => $stats,
            'monthly_stats' => $monthlyStats,
            'department_stats' => $departmentStats,
        ]);
    }
}
