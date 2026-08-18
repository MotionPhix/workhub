<?php

namespace Database\Factories;

use App\Enums\TemplateCategory;
use App\Enums\TemplateStatus;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Auth;

/**
 * @extends Factory<EmailTemplate>
 */
class EmailTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => Auth::id() ?? User::factory(),
            'name' => fake()->unique()->randomElement([
                'Cold intro — NGO',
                'Cold intro — business',
                'Follow-up — no reply',
                'Follow-up — second nudge',
                'Re-engagement — old prospect',
                'Proposal follow-up',
                'Referral introduction',
                'Thank you after call',
            ]),
            'category' => fake()->randomElement(TemplateCategory::cases()),
            'subject' => 'A thought about {{company_name}}’s brand',
            'body' => <<<'BODY'
                Hi {{first_name}},

                I came across {{company_name}} while looking at organisations doing
                interesting work in {{industry}}, and one thing stood out to me:
                {{observation}}.

                I am {{my_name}} and I run {{my_business}}, a small studio here in
                Malawi that helps organisations look as credible as the work they do.
                Most of what I do is {{service}}.

                {{suggestion}}

                If that sounds useful, I am happy to share a couple of examples of
                similar work. No obligation either way.

                Kind regards,
                {{my_name}}
                BODY,
            'status' => TemplateStatus::Active,
            'notes' => null,
            'version' => 1,
            'parent_id' => null,
            'times_used' => fake()->numberBetween(0, 30),
            'last_used_at' => fake()->boolean(70) ? fake()->dateTimeBetween('-2 months', 'now') : null,
        ];
    }

    /**
     * Indicate that the template is not ready to be used yet.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TemplateStatus::Draft,
            'times_used' => 0,
            'last_used_at' => null,
        ]);
    }

    /**
     * Place the template in a specific category.
     */
    public function category(TemplateCategory $category): static
    {
        return $this->state(fn (array $attributes): array => [
            'category' => $category,
        ]);
    }
}
