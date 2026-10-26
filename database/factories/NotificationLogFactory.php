<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NotificationStatus;
use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationLog>
 */
class NotificationLogFactory extends Factory
{
    protected $model = NotificationLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'appointment_confirmed',
            'channel' => 'mail',
            'status' => NotificationStatus::Queued,
            'sent_at' => null,
            'related_type' => null,
            'related_id' => null,
            'error' => null,
            'dedupe_key' => fake()->unique()->uuid(),
        ];
    }

    /**
     * State: Queued.
     */
    public function queued(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NotificationStatus::Queued,
            'sent_at' => null,
            'error' => null,
        ]);
    }

    /**
     * State: Sent.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NotificationStatus::Sent,
            'sent_at' => now(),
            'error' => null,
        ]);
    }

    /**
     * State: Failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NotificationStatus::Failed,
            'sent_at' => null,
            'error' => 'Simulated delivery failure.',
        ]);
    }

    /**
     * State: Skipped.
     */
    public function skipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NotificationStatus::Skipped,
            'sent_at' => null,
            'error' => 'Appointment cancelled before send.',
        ]);
    }
}
