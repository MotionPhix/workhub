<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            ['name' => 'Hot Lead', 'slug' => 'hot-lead', 'color' => '#ef4444'],
            ['name' => 'Decision Maker', 'slug' => 'decision-maker', 'color' => '#8b5cf6'],
            ['name' => 'Influencer', 'slug' => 'influencer', 'color' => '#06b6d4'],
            ['name' => 'Budget Holder', 'slug' => 'budget-holder', 'color' => '#10b981'],
            ['name' => 'Referral', 'slug' => 'referral', 'color' => '#f59e0b'],
            ['name' => 'Conference', 'slug' => 'conference', 'color' => '#6366f1'],
            ['name' => 'LinkedIn', 'slug' => 'linkedin', 'color' => '#0077b5'],
            ['name' => 'Past Client', 'slug' => 'past-client', 'color' => '#ec4899'],
            ['name' => 'NGO Contact', 'slug' => 'ngo-contact', 'color' => '#14b8a6'],
            ['name' => 'Government', 'slug' => 'government', 'color' => '#64748b'],
            ['name' => 'Startup', 'slug' => 'startup', 'color' => '#f97316'],
            ['name' => 'Enterprise', 'slug' => 'enterprise', 'color' => '#3b82f6'],
            ['name' => 'Needs Follow Up', 'slug' => 'needs-follow-up', 'color' => '#eab308'],
            ['name' => 'VIP', 'slug' => 'vip', 'color' => '#dc2626'],
            ['name' => 'Newsletter Subscriber', 'slug' => 'newsletter', 'color' => '#22c55e'],
        ];

        foreach ($tags as $tag) {
            Tag::create($tag);
        }
    }
}
