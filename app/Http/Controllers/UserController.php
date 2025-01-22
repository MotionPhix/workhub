<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class UserController extends Controller
{
  use AuthorizesRequests;

  public function index(Request $request)
  {
    // Only admin can list users
    $this->authorize('viewAny', User::class);

    $users = User::query()
      ->when($request->input('search'), function ($query, $search) {
        $query->where('name', 'like', "%{$search}%")
          ->orWhere('email', 'like', "%{$search}%");
      })
      ->when($request->input('department'), function ($query, $department) {
        $query->where('department', $department);
      })
      ->paginate(15)
      ->withQueryString();

    return Inertia::render('Users/Index', [
      'users' => $users,
      'filters' => $request->all('search', 'department')
    ]);
  }

  public function create()
  {
    $this->authorize('create', User::class);

    return Inertia::render('Users/Create');
  }

  public function store(Request $request)
  {
    $this->authorize('create', User::class);

    $validated = $request->validate([
      'name' => ['required', 'string', 'max:255'],
      'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
      'password' => [
        'required',
        'confirmed',
        Password::defaults()
          ->min(12)
          ->letters()
          ->mixedCase()
          ->numbers()
          ->symbols()
      ],
      'department' => ['nullable', 'string', 'max:100'],
      'manager_email' => ['nullable', 'email', 'exists:users,email'],
      'settings.notifications.email' => ['boolean'],
      'settings.notifications.sms' => ['boolean'],
      'settings.timezone' => ['string', 'max:50'],
    ]);

    $user = User::create([
      'name' => $validated['name'],
      'email' => $validated['email'],
      'password' => Hash::make($validated['password']),
      'department' => $validated['department'] ?? null,
      'manager_email' => $validated['manager_email'] ?? null,
      'settings' => [
        'notifications' => [
          'email' => $validated['settings']['notifications']['email'] ?? true, // Default to true
          'sms' => $validated['settings']['notifications']['sms'] ?? false,  // Default to false
        ],
        'timezone' => $validated['settings']['timezone'] ?? 'UTC', // Default to UTC
      ]
    ]);

    return redirect()->route('users.index')
      ->with('success', 'User created successfully.');
  }

  public function edit(User $user)
  {
    $this->authorize('update', $user);

    return Inertia::render('Users/Edit', [
      'user' => $user
    ]);
  }

  public function update(Request $request, User $user)
  {
    $this->authorize('update', $user);

    $validated = $request->validate([
      'name' => ['sometimes', 'string', 'max:255'],
      'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
      'department' => ['nullable', 'string', 'max:100'],
      'manager_email' => ['nullable', 'email', 'exists:users,email']
    ]);

    $user->update($validated);

    return redirect()->route('users.index')
      ->with('success', 'User updated successfully.');
  }

  public function destroy(User $user)
  {
    $this->authorize('delete', $user);

    // Prevent deleting self or last admin
    if (Auth::id() === $user->id) {
      return back()->with('error', 'You cannot delete your own account.');
    }

    // Log the deletion
    Log::info('User account deleted', [
      'deleted_user_id' => $user->id,
      'deleted_by_user_id' => Auth::id()
    ]);

    // Soft delete
    $user->delete();

    return redirect()->route('users.index')
      ->with('success', 'User account has been deactivated.');
  }

  public function profile()
  {
    $user = Auth::user()->load('roles', 'permissions');

    return Inertia::render('Profile/Show', [
      'user' => $user,
      'activityLog' => $this->getUserActivityLog($user)
    ]);
  }

  public function updateProfile(Request $request)
  {
    $user = Auth::user();

    $validated = $request->validate([
      'name' => ['sometimes', 'string', 'max:255'],
      'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
      'avatar' => ['sometimes', 'image', 'max:2048'],
      'department' => ['nullable', 'string', 'max:100'],
    ]);

    // Handle avatar upload
    if ($request->hasFile('avatar')) {
      $avatarPath = $request->file('avatar')->store('avatars', 'public');
      $validated['avatar'] = $avatarPath;
    }

    $user->update($validated);

    return back()->with('success', 'Profile updated successfully.');
  }

  public function changePassword(Request $request)
  {
    $user = Auth::user();

    $validated = $request->validate([
      'current_password' => ['required', 'current_password'],
      'password' => [
        'required',
        'confirmed',
        Password::defaults()
          ->min(12)
          ->letters()
          ->mixedCase()
          ->numbers()
          ->symbols()
          ->uncompromised()
      ]
    ]);

    // Update password
    $user->update([
      'password' => Hash::make($validated['password'])
    ]);

    // Log password change
    Log::info('User password changed', [
      'user_id' => $user->id
    ]);

    // Logout user from other devices
    Auth::logoutOtherDevices($validated['password']);

    return back()->with('success', 'Password changed successfully.');
  }

  public function toggleTwoFactorAuthentication(Request $request)
  {
    $user = Auth::user();

    $validated = $request->validate([
      'two_factor_enabled' => ['required', 'boolean']
    ]);

    if ($validated['two_factor_enabled']) {
      $user->enableTwoFactorAuthentication();
    } else {
      $user->disableTwoFactorAuthentication();
    }

    return back()->with('success', 'Two-factor authentication updated.');
  }

  private function getUserActivityLog(User $user)
  {
    return $user->activityLogs()
      ->latest()
      ->limit(50)
      ->get();
  }

  public function impersonate(Request $request, User $user)
  {
    // Ensure only admins can impersonate
    $this->authorize('impersonate', $user);

    // Start impersonation
    session()->put('impersonator', Auth::id());
    Auth::login($user);

    return redirect()->route('dashboard')
      ->with('success', "You are now impersonating {$user->name}");
  }

  public function stopImpersonation()
  {
    $impersonatorId = session()->get('impersonator');

    if ($impersonatorId) {
      Auth::loginUsingId($impersonatorId);
      session()->forget('impersonator');
    }

    return redirect()->route('dashboard')
      ->with('success', 'Impersonation stopped');
  }
}
