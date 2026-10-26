<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DonorProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DonorProfile>
 */
class DonorProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'blood_group' => fake()->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'last_donated_on' => fake()->optional()->dateTimeBetween('-2 years', '-3 months')?->format('Y-m-d'),
            'notes_for_staff' => null,
        ];
    }

    /**
     * State for a newly registered donor with an empty profile.
     */
    public function empty(): static
    {
        return $this->state(fn (array $attributes) => [
            'blood_group' => null,
            'last_donated_on' => null,
            'notes_for_staff' => null,
        ]);
    }
}
