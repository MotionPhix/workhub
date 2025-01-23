<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Department;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
  /**
   * Display the user's profile form.
   */
  public function index(User $user): Response
  {
    return Inertia::render('Profile/Index', [
      'mustVerifyEmail' => $user instanceof MustVerifyEmail,
      'user' => $user->load('roles')
    ]);
  }

  public function edit(Request $request, User $user)
  {
    return Inertia::modal('Profile/Partials/ProfileForm', [
      'user' => $user,
      'departments' => fn() => Department::all('id', 'uuid', 'name', 'description'),
    ])->baseRoute('profile.index', ['user' => $user->uuid]);
  }

  /**
   * Update the user's profile information.
   */
  public function update(ProfileUpdateRequest $request): RedirectResponse
  {
    $request->user()->fill($request->validated());

    if ($request->user()->isDirty('email')) {
      $request->user()->email_verified_at = null;
    }

    $request->user()->save();

    return Redirect::route('profile.index');
  }

  /**
   * Delete the user's account.
   */
  public function destroy(Request $request): RedirectResponse
  {
    $request->validate([
      'password' => ['required', 'current_password'],
    ]);

    $user = $request->user();

    Auth::logout();

    $user->delete();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    Inertia::clearHistory();

    return Redirect::to('/');
  }
}
