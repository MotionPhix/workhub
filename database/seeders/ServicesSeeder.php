<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            ['name' => 'Brand Strategy', 'slug' => 'brand-strategy', 'description' => 'Brand positioning, messaging, and identity development', 'is_focus' => true, 'sort_order' => 1],
            ['name' => 'Logo & Visual Identity', 'slug' => 'logo-visual-identity', 'description' => 'Logo design, brand guidelines, and visual systems', 'is_focus' => true, 'sort_order' => 2],
            ['name' => 'Website Design', 'slug' => 'website-design', 'description' => 'Custom website design and user experience', 'is_focus' => true, 'sort_order' => 3],
            ['name' => 'Website Development', 'slug' => 'website-development', 'description' => 'Front-end and back-end web development', 'is_focus' => true, 'sort_order' => 4],
            ['name' => 'Web Application Development', 'slug' => 'web-app-development', 'description' => 'Custom web applications and SaaS products', 'is_focus' => true, 'sort_order' => 5],
            ['name' => 'UI/UX Design', 'slug' => 'ui-ux-design', 'description' => 'Interface design and user experience optimization', 'is_focus' => true, 'sort_order' => 6],
            ['name' => 'Graphic Design', 'slug' => 'graphic-design', 'description' => 'Print and digital design: reports, brochures, presentations', 'is_focus' => true, 'sort_order' => 7],
            ['name' => 'Marketing Materials', 'slug' => 'marketing-materials', 'description' => 'Brochures, flyers, posters, and promotional design', 'is_focus' => false, 'sort_order' => 8],
            ['name' => 'Social Media Design', 'slug' => 'social-media-design', 'description' => 'Social media templates, posts, and profile design', 'is_focus' => false, 'sort_order' => 9],
            ['name' => 'Digital Marketing Support', 'slug' => 'digital-marketing', 'description' => 'Email campaigns, landing pages, and digital assets', 'is_focus' => false, 'sort_order' => 10],
            ['name' => 'Creative Consultancy', 'slug' => 'creative-consultancy', 'description' => 'Strategic creative advice and project planning', 'is_focus' => false, 'sort_order' => 11],
            ['name' => 'Template Systems', 'slug' => 'template-systems', 'description' => 'Branded template packs for reports, presentations, social media', 'is_focus' => false, 'sort_order' => 12],
        ];

        foreach ($services as $service) {
            Service::create($service);
        }
    }
}
