<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\ReportDeliveryLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportDeliveryLogFactory extends Factory
{
    protected $model = ReportDeliveryLog::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(['pending', 'delivered', 'failed']);
        $attemptedAt = $this->faker->dateTimeBetween('-1 week', 'now');

        return [
            'report_id' => Report::factory(),
            'recipient_email' => $this->faker->email(),
            'delivery_method' => 'email',
            'status' => $status,
            'attempted_at' => $attemptedAt,
            'delivered_at' => $status === 'delivered' ? $this->faker->dateTimeBetween($attemptedAt, 'now') : null,
            'failed_at' => $status === 'failed' ? $this->faker->dateTimeBetween($attemptedAt, 'now') : null,
            'error_message' => $status === 'failed' ? $this->faker->sentence() : null,
            'retry_count' => $status === 'failed' ? $this->faker->numberBetween(1, 3) : 0,
            'metadata' => [
                'user_agent' => $this->faker->userAgent(),
                'ip_address' => $this->faker->ipv4(),
                'delivery_options' => [
                    'priority' => $this->faker->randomElement(['low', 'normal', 'high']),
                ],
            ],
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'delivered_at' => null,
            'failed_at' => null,
            'error_message' => null,
            'retry_count' => 0,
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'delivered_at' => $this->faker->dateTimeBetween($attributes['attempted_at'] ?? '-1 hour', 'now'),
            'failed_at' => null,
            'error_message' => null,
            'retry_count' => 0,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'delivered_at' => null,
            'failed_at' => $this->faker->dateTimeBetween($attributes['attempted_at'] ?? '-1 hour', 'now'),
            'error_message' => $this->faker->randomElement([
                'SMTP connection failed',
                'Invalid email address',
                'Mailbox full',
                'Server timeout',
                'Authentication failed',
            ]),
            'retry_count' => $this->faker->numberBetween(1, 3),
        ]);
    }

    public function withRetries(int $retries): static
    {
        return $this->state(fn (array $attributes) => [
            'retry_count' => $retries,
            'status' => $retries >= 3 ? 'failed' : 'pending',
        ]);
    }
}
