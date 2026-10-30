<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Drive;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('development seeder keeps only admin and staff demo accounts and removes legacy demo drives', function () {
    Drive::factory()->create(['title' => '[DEMO] Legacy sample drive']);
    $realDrive = Drive::factory()->create(['title' => 'Community blood drive']);

    User::factory()->create([
        'email' => 'donor@donorlink.test',
        'role' => Role::Donor,
    ]);

    (new DatabaseSeeder)->run();

    $this->assertDatabaseMissing('drives', ['title' => '[DEMO] Legacy sample drive']);
    $this->assertDatabaseHas('drives', ['id' => $realDrive->id, 'title' => 'Community blood drive']);
    $this->assertDatabaseHas('users', ['email' => 'admin@donorlink.test', 'role' => Role::Admin->value]);
    $this->assertDatabaseHas('users', ['email' => 'staff@donorlink.test', 'role' => Role::Staff->value]);
    $this->assertDatabaseMissing('users', ['email' => 'donor@donorlink.test']);
    $this->assertDatabaseMissing('drives', ['title' => 'Sample Past Blood Donation Camp (Closed)']);
});
