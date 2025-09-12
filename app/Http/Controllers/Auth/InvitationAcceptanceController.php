<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AcceptInvitationRequest;
use App\Models\UserInvite;
use App\Services\Auth\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InvitationAcceptanceController extends Controller
{
    public function __construct(
        private InvitationService $invitationService
    ) {}

    public function show(string $token): Response|RedirectResponse
    {
        $invitation = UserInvite::findByToken($token);

        if (! $invitation) {
            return redirect()->route('login')
                ->withErrors(['token' => 'Invalid or expired invitation token.']);
        }

        if (! $invitation->canBeAccepted()) {
            $message = match (true) {
                $invitation->isExpired() => 'This invitation has expired.',
                $invitation->status === 'accepted' => 'This invitation has already been accepted.',
                $invitation->status === 'declined' => 'This invitation has been declined.',
                default => 'This invitation is no longer valid.'
            };

            return redirect()->route('login')->withErrors(['token' => $message]);
        }

        return Inertia::render('auth/invitation/Show', [
            'invitation' => [
                'token' => $token,
                'email' => $invitation->email,
                'name' => $invitation->name,
                'job_title' => $invitation->job_title,
                'department_name' => $invitation->department?->name,
                'manager_name' => $invitation->manager?->name,
                'inviter_name' => $invitation->inviter->name,
                'expires_at' => $invitation->expires_at->diffForHumans(),
                'days_until_expiry' => $invitation->getDaysUntilExpiry(),
            ],
        ]);
    }

    public function accept(AcceptInvitationRequest $request, string $token): RedirectResponse
    {
        try {
            $user = $this->invitationService->acceptInvitation($token, $request->validated());

            // Log the user in
            Auth::login($user);

            // Redirect to onboarding if needed, otherwise dashboard
            if ($user->userOnboarding && ! $user->userOnboarding->is_completed) {
                return redirect()->route('onboarding.show', ['step' => 1])
                    ->with('success', 'Welcome! Please complete your profile setup.');
            }

            return redirect()->route('dashboard')
                ->with('success', 'Welcome to the team! Your account has been created successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    public function decline(string $token): RedirectResponse
    {
        try {
            $invitation = UserInvite::findByToken($token);

            if (! $invitation || ! $invitation->isPending()) {
                return redirect()->route('login')
                    ->withErrors(['token' => 'Invalid or expired invitation.']);
            }

            $this->invitationService->declineInvitation($token);

            return redirect()->route('login')
                ->with('message', 'You have declined the invitation. Thank you for your response.');

        } catch (\Exception $e) {
            return redirect()->route('login')
                ->withErrors(['error' => 'Unable to decline invitation. Please try again.']);
        }
    }
}
