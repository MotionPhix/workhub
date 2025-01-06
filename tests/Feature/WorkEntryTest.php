<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WorkEntryTest extends TestCase
{
  use RefreshDatabase;

  public function test_user_can_create_work_entry()
  {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/work-entries', [
      'work_date' => now()->format('Y-m-d'),
      'description' => 'Test work entry',
      'hours_worked' => 4
    ]);

    $response->assertSessionHas('success');
    $this->assertDatabaseHas('work_entries', [
      'user_id' => $user->id,
      'description' => 'Test work entry'
    ]);
  }
}
