<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DriveStatus;
use App\Models\Drive;
use App\Models\User;
use App\Support\DateHelper;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class DriveService
{
    /**
     * Create a new draft drive.
     *
     * @param  array{
     *     title: string,
     *     description?: string|null,
     *     venue: string,
     *     address?: string|null,
     *     starts_on: string,
     *     ends_on: string,
     * }  $data
     *
     * @throws ValidationException
     */
    public function create(array $data, User $creator): Drive
    {
        $todayEat = DateHelper::today()->toDateString();
        $startsOn = CarbonImmutable::parse($data['starts_on'])->toDateString();
        $endsOn = CarbonImmutable::parse($data['ends_on'])->toDateString();

        if ($startsOn < $todayEat) {
            throw ValidationException::withMessages([
                'starts_on' => 'New drives cannot start in the past.',
            ]);
        }

        if ($endsOn < $startsOn) {
            throw ValidationException::withMessages([
                'ends_on' => 'Drive end date must be on or after the start date.',
            ]);
        }

        return Drive::forceCreate([
            'title' => trim($data['title']),
            'description' => ! empty($data['description']) ? trim($data['description']) : null,
            'venue' => trim($data['venue']),
            'address' => ! empty($data['address']) ? trim($data['address']) : null,
            'starts_on' => $startsOn,
            'ends_on' => $endsOn,
            'status' => DriveStatus::Draft,
            'created_by' => $creator->id,
        ]);
    }

    /**
     * Update an existing drive.
     *
     * @param  array{
     *     title?: string,
     *     description?: string|null,
     *     venue?: string,
     *     address?: string|null,
     *     starts_on?: string,
     *     ends_on?: string,
     * }  $data
     *
     * @throws ValidationException
     */
    public function update(Drive $drive, array $data): Drive
    {
        if ($drive->isPast()) {
            throw ValidationException::withMessages([
                'drive' => 'Past drives are read-only and cannot be edited.',
            ]);
        }

        $startsOn = isset($data['starts_on'])
            ? CarbonImmutable::parse($data['starts_on'])->toDateString()
            : $drive->starts_on->toDateString();

        $endsOn = isset($data['ends_on'])
            ? CarbonImmutable::parse($data['ends_on'])->toDateString()
            : $drive->ends_on->toDateString();

        if ($endsOn < $startsOn) {
            throw ValidationException::withMessages([
                'ends_on' => 'Drive end date must be on or after the start date.',
            ]);
        }

        // Shortening date range so that it excludes an existing slot is refused
        $slots = $drive->slots()->get();
        foreach ($slots as $slot) {
            $slotDateEat = DateHelper::toEat($slot->starts_at)?->toDateString();
            if ($slotDateEat && ($slotDateEat < $startsOn || $slotDateEat > $endsOn)) {
                throw ValidationException::withMessages([
                    'dates' => 'Cannot shorten the date range because existing slots would fall outside the new dates.',
                ]);
            }
        }

        $drive->update([
            'title' => isset($data['title']) ? trim($data['title']) : $drive->title,
            'description' => array_key_exists('description', $data)
                ? (! empty($data['description']) ? trim((string) $data['description']) : null)
                : $drive->description,
            'venue' => isset($data['venue']) ? trim($data['venue']) : $drive->venue,
            'address' => array_key_exists('address', $data)
                ? (! empty($data['address']) ? trim((string) $data['address']) : null)
                : $drive->address,
            'starts_on' => $startsOn,
            'ends_on' => $endsOn,
        ]);

        return $drive->fresh();
    }

    /**
     * Publish a draft drive.
     *
     * @throws ValidationException
     */
    public function publish(Drive $drive): Drive
    {
        if ($drive->isPast()) {
            throw ValidationException::withMessages([
                'drive' => 'Cannot publish a drive that is in the past.',
            ]);
        }

        $missing = [];

        if (blank($drive->title)) {
            $missing[] = 'title';
        }

        if (blank($drive->venue)) {
            $missing[] = 'venue';
        }

        if (! $drive->starts_on || ! $drive->ends_on || $drive->ends_on->lt($drive->starts_on)) {
            $missing[] = 'valid start and end dates';
        }

        $hasUpcomingSlot = $drive->slots()
            ->where('starts_at', '>=', Carbon::now('UTC'))
            ->exists();

        if (! $hasUpcomingSlot) {
            $missing[] = 'at least one upcoming slot';
        }

        if (! empty($missing)) {
            $missingList = implode(', ', $missing);
            throw ValidationException::withMessages([
                'publish' => "Cannot publish drive. Missing required items: {$missingList}.",
            ]);
        }

        $drive->status = DriveStatus::Published;
        $drive->save();

        return $drive;
    }

    /**
     * Close a published drive.
     *
     * @throws ValidationException
     */
    public function close(Drive $drive): Drive
    {
        if ($drive->status !== DriveStatus::Published) {
            throw ValidationException::withMessages([
                'status' => 'Only published drives can be closed.',
            ]);
        }

        $drive->status = DriveStatus::Closed;
        $drive->save();

        return $drive;
    }

    /**
     * Reopen a closed drive that has not ended.
     *
     * @throws ValidationException
     */
    public function reopen(Drive $drive): Drive
    {
        if ($drive->status !== DriveStatus::Closed) {
            throw ValidationException::withMessages([
                'status' => 'Only closed drives can be reopened.',
            ]);
        }

        if ($drive->isPast()) {
            throw ValidationException::withMessages([
                'status' => 'Cannot reopen a drive that has already ended.',
            ]);
        }

        $drive->status = DriveStatus::Published;
        $drive->save();

        return $drive;
    }

    /**
     * Delete a drive (draft drives only).
     *
     * @throws ValidationException
     */
    public function delete(Drive $drive): void
    {
        if ($drive->status !== DriveStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => 'Only draft drives can be deleted. Published and closed drives cannot be deleted.',
            ]);
        }

        $drive->delete();
    }
}
