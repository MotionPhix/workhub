<?php

namespace Database\Factories;

use App\Enums\EmailDirection;
use App\Enums\EmailStatus;
use App\Enums\OutreachStrategy;
use App\Models\Contact;
use App\Models\EmailMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * @extends Factory<EmailMessage>
 */
class EmailMessageFactory extends Factory
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
            'contact_id' => Contact::factory(),
            'company_id' => null,
            'email_template_id' => null,
            'outreach_cycle_id' => null,
            'outreach_assignment_id' => null,
            'parent_id' => null,
            'direction' => EmailDirection::Outbound,
            'strategy' => OutreachStrategy::Cold,
            'status' => EmailStatus::Draft,
            'subject' => 'A thought about your brand',
            'body' => fake()->paragraphs(3, true),
            'variables' => null,
            'to_email' => fn (array $attributes): string => $this->contactEmail($attributes),
            'to_name' => null,
            'from_email' => Auth::user()?->email ?? fake()->safeEmail(),
            'from_name' => Auth::user()?->name ?? fake()->name(),
            'reply_to' => null,
            'queued_at' => null,
            'sent_at' => null,
            'delivered_at' => null,
            'opened_at' => null,
            'replied_at' => null,
            'failed_at' => null,
            'failure_reason' => null,
            'message_id' => null,
            'opens' => 0,
        ];
    }

    /**
     * Indicate that the message was sent the given number of days ago.
     */
    public function sent(int $daysAgo = 3): static
    {
        return $this->state(function (array $attributes) use ($daysAgo): array {
            $sentAt = Carbon::now()->subDays($daysAgo);

            return [
                'status' => EmailStatus::Sent,
                'queued_at' => $sentAt->copy()->subMinute(),
                'sent_at' => $sentAt,
                'message_id' => '<'.fake()->uuid().'@workhub.test>',
            ];
        });
    }

    /**
     * Indicate that the recipient opened the message.
     */
    public function opened(int $daysAgo = 2): static
    {
        return $this->sent($daysAgo + 1)->state(fn (array $attributes): array => [
            'status' => EmailStatus::Opened,
            'delivered_at' => Carbon::now()->subDays($daysAgo + 1)->addMinutes(2),
            'opened_at' => Carbon::now()->subDays($daysAgo),
            'opens' => fake()->numberBetween(1, 5),
        ]);
    }

    /**
     * Indicate that the recipient replied.
     */
    public function replied(int $daysAgo = 1): static
    {
        return $this->opened($daysAgo + 1)->state(fn (array $attributes): array => [
            'status' => EmailStatus::Replied,
            'replied_at' => Carbon::now()->subDays($daysAgo),
        ]);
    }

    /**
     * Indicate that the message could not be delivered.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EmailStatus::Failed,
            'queued_at' => Carbon::now()->subMinutes(10),
            'failed_at' => Carbon::now()->subMinutes(9),
            'failure_reason' => 'Connection to the mail server timed out.',
        ]);
    }

    /**
     * Indicate that the message is an inbound reply to another message.
     */
    public function inbound(): static
    {
        return $this->state(fn (array $attributes): array => [
            'direction' => EmailDirection::Inbound,
            'status' => EmailStatus::Delivered,
            'subject' => 'Re: A thought about your brand',
            'body' => 'Thanks for reaching out. Could you send over some examples and pricing?',
            'sent_at' => Carbon::now()->subDay(),
        ]);
    }

    /**
     * The recipient address, taken from the contact the message belongs to.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function contactEmail(array $attributes): string
    {
        $contactId = $attributes['contact_id'] ?? null;

        $contact = is_string($contactId)
            ? Contact::withoutGlobalScopes()->find($contactId)
            : null;

        return $contact?->email ?? fake()->safeEmail();
    }
}
