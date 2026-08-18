<?php

use App\Models\Goal;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard loads when a goal exists for the current month', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Goal::factory()->create([
        'user_id' => $user->id,
        'period_start' => now()->startOfMonth(),
        'period_end' => now()->endOfMonth(),
        'revenue_target' => 500000,
    ]);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});
