<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Drive;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed development administrator and staff accounts.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('DatabaseSeeder is for development only and refuses to run in production.');
        }

        $demoDrives = Drive::query()
            ->get()
            ->filter(fn (Drive $drive): bool => str_starts_with($drive->title, '[DEMO]'));
        $demoDriveIds = $demoDrives->modelKeys();
        $demoSlotIds = $demoDriveIds === []
            ? collect()
            : Slot::query()->whereIn('drive_id', $demoDriveIds)->pluck('id');

        $relatedDemoDonorIds = $demoDriveIds === []
            ? collect()
            : User::query()
                ->where('role', Role::Donor)
                ->where(function ($query) use ($demoDriveIds, $demoSlotIds) {
                    $query->whereHas('appointments', fn ($query) => $query->whereIn('slot_id', $demoSlotIds))
                        ->orWhereHas('donationRecords', fn ($query) => $query->whereIn('drive_id', $demoDriveIds));
                })
                ->pluck('id');

        $demoDonorIdsWithoutOtherActivity = $relatedDemoDonorIds->isEmpty()
            ? collect()
            : User::query()
                ->whereIn('id', $relatedDemoDonorIds)
                ->whereDoesntHave('appointments', fn ($query) => $query->whereNotIn('slot_id', $demoSlotIds))
                ->whereDoesntHave('donationRecords', fn ($query) => $query->whereNotIn('drive_id', $demoDriveIds))
                ->pluck('id');

        foreach ($demoDrives as $drive) {
            $drive->delete();
        }

        if ($demoDonorIdsWithoutOtherActivity->isNotEmpty()) {
            User::query()->whereIn('id', $demoDonorIdsWithoutOtherActivity)->delete();
        }

        User::query()
            ->where('role', Role::Donor)
            ->whereIn('email', [
                'donor@donorlink.test',
                'donor2@donorlink.test',
                'donor3@donorlink.test',
                'donor4@donorlink.test',
                'donor5@donorlink.test',
            ])
            ->delete();

        $this->command?->info('Removing legacy demo drives and seeding development accounts...');

        User::updateOrCreate(
            ['email' => 'admin@donorlink.test'],
            [
                'name' => 'UBTS Administrator',
                'phone' => '+256756788112',
                'role' => Role::Admin,
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'staff@donorlink.test'],
            [
                'name' => 'UBTS Staff Nurse',
                'phone' => '+256700000002',
                'role' => Role::Staff,
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );
    }
}
