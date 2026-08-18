<?php

namespace Database\Seeders;

use App\Models\Goal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class GoalsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $today = Carbon::today();

        // Current month goal
        Goal::factory()
            ->forMonth($today)
            ->create([
                'prospect_target' => 100,
                'follow_up_target' => 25,
                'proposal_target' => 8,
                'client_target' => 3,
                'revenue_target' => 10_000_000,
            ]);

        // Next month goal
        Goal::factory()
            ->forMonth($today->copy()->addMonth())
            ->create([
                'prospect_target' => 120,
                'follow_up_target' => 30,
                'proposal_target' => 10,
                'client_target' => 4,
                'revenue_target' => 12_000_000,
            ]);

        // Last month goal (for analytics comparison)
        Goal::factory()
            ->forMonth($today->copy()->subMonth())
            ->create([
                'prospect_target' => 80,
                'follow_up_target' => 20,
                'proposal_target' => 6,
                'client_target' => 2,
                'revenue_target' => 8_000_000,
            ]);
    }
}
