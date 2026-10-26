<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->donor(),
            'slot_id' => Slot::factory(),
            'status' => AppointmentStatus::Booked,
            'cancelled_at' => null,
            'checked_in_at' => null,
        ];
    }

    /**
     * State: Booked
     */
    public function booked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentStatus::Booked,
            'cancelled_at' => null,
            'checked_in_at' => null,
        ]);
    }

    /**
     * State: Cancelled
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentStatus::Cancelled,
            'cancelled_at' => now(),
            'checked_in_at' => null,
        ]);
    }

    /**
     * State: Attended
     */
    public function attended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentStatus::Attended,
            'cancelled_at' => null,
            'checked_in_at' => now(),
        ]);
    }

    /**
     * State: No Show
     */
    public function noShow(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentStatus::NoShow,
            'cancelled_at' => null,
            'checked_in_at' => null,
        ]);
    }
}
