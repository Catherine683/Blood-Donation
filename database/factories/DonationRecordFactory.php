<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DonationOutcome;
use App\Models\Appointment;
use App\Models\DonationRecord;
use App\Models\Drive;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DonationRecord>
 */
class DonationRecordFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<DonationRecord>
     */
    protected $model = DonationRecord::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'drive_id' => Drive::factory(),
            'appointment_id' => Appointment::factory(),
            'outcome' => DonationOutcome::Donated,
            'recorded_by' => User::factory()->staff(),
            'recorded_at' => now(),
            'staff_note' => null,
        ];
    }

    /**
     * State: Successful donation.
     */
    public function donated(): static
    {
        return $this->state(fn (array $attributes) => [
            'outcome' => DonationOutcome::Donated,
        ]);
    }

    /**
     * State: Deferred attendance (did not donate).
     */
    public function deferred(): static
    {
        return $this->state(fn (array $attributes) => [
            'outcome' => DonationOutcome::Deferred,
        ]);
    }

    /**
     * State: Walk-in donor (no appointment).
     */
    public function walkIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'appointment_id' => null,
        ]);
    }
}
