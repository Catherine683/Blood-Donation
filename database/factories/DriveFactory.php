<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DriveStatus;
use App\Models\Drive;
use App\Models\User;
use App\Support\DateHelper;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Drive>
 */
class DriveFactory extends Factory
{
    protected $model = Drive::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $todayEat = DateHelper::today();
        $startsOn = $todayEat->addDays(fake()->numberBetween(1, 10));
        $endsOn = $startsOn->addDays(fake()->numberBetween(0, 3));

        return [
            'title' => fake()->randomElement(['Kampala City Blood Drive', 'Makerere University Blood Drive', 'Nakawa Market Blood Drive', 'Kololo Airstrip Blood Donation Camp']),
            'description' => fake()->paragraph(),
            'venue' => fake()->randomElement(['City Square Ground', 'Makerere Main Hall', 'Nakawa Community Centre', 'Railway Grounds']),
            'address' => fake()->streetAddress().', Kampala',
            'starts_on' => $startsOn->toDateString(),
            'ends_on' => $endsOn->toDateString(),
            'status' => DriveStatus::Draft,
            'created_by' => User::factory()->staff(),
        ];
    }

    /**
     * State: Draft
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DriveStatus::Draft,
        ]);
    }

    /**
     * State: Published
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DriveStatus::Published,
        ]);
    }

    /**
     * State: Closed
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DriveStatus::Closed,
        ]);
    }

    /**
     * State: Past (ended in the past)
     */
    public function past(): static
    {
        return $this->state(function (array $attributes) {
            $todayEat = DateHelper::today();
            $startsOn = $todayEat->subDays(10);
            $endsOn = $todayEat->subDays(8);

            return [
                'starts_on' => $startsOn->toDateString(),
                'ends_on' => $endsOn->toDateString(),
            ];
        });
    }
}
