<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Drive;
use App\Models\Slot;
use App\Support\DateHelper;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Slot>
 */
class SlotFactory extends Factory
{
    protected $model = Slot::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $drive = Drive::factory();
        $date = DateHelper::today()->addDays(2);
        $startsAtUtc = DateHelper::toUtc($date, '09:00:00');
        $endsAtUtc = DateHelper::toUtc($date, '09:30:00');

        return [
            'drive_id' => $drive,
            'starts_at' => $startsAtUtc,
            'ends_at' => $endsAtUtc,
            'capacity' => 10,
            'booked_count' => 0,
        ];
    }

    /**
     * Set specific start and end times in EAT.
     */
    public function eatTime(string $date, string $startTime, string $endTime): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => DateHelper::toUtc($date, $startTime),
            'ends_at' => DateHelper::toUtc($date, $endTime),
        ]);
    }
}
