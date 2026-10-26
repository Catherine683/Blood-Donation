<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<AuditLog>
     */
    protected $model = AuditLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor_id' => User::factory()->staff(),
            'action' => 'donor.viewed',
            'subject_type' => User::class,
            'subject_id' => 1,
            'metadata' => ['donor_id' => 1],
            'ip' => '127.0.0.1',
            'created_at' => now(),
        ];
    }
}
