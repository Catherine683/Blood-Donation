<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Enums\DriveStatus;
use App\Enums\NotificationStatus;
use App\Enums\Role;
use App\Exceptions\CloseOutBlockedException;
use App\Exceptions\NotAllowedException;
use App\Http\Controllers\Controller;
use App\Models\Drive;
use App\Models\NotificationLog;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\AuditLogger;
use App\Services\DriveService;
use App\Services\MailBudget;
use App\Support\DateHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DriveController extends Controller
{
    public function __construct(
        protected DriveService $driveService,
    ) {}

    /**
     * Display a listing of drives with filters and aggregates.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Drive::class);

        $query = Drive::query()
            ->withCount('slots')
            ->withSum('slots as total_capacity', 'capacity')
            ->withSum('slots as total_booked', 'booked_count');

        // Search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('venue', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($status = $request->input('status')) {
            if ($status !== 'all' && DriveStatus::tryFrom($status)) {
                $query->where('status', $status);
            }
        }

        // Timeline filter: upcoming vs past
        $timeline = $request->input('timeline', 'all');
        $todayEat = DateHelper::today()->toDateString();
        if ($timeline === 'upcoming') {
            $query->where('ends_on', '>=', $todayEat);
        } elseif ($timeline === 'past') {
            $query->where('ends_on', '<', $todayEat);
        }

        $drives = $query->orderByDesc('starts_on')
            ->paginate(15)
            ->withQueryString();

        return view('pages.staff.drives.index', compact('drives'));
    }

    /**
     * Show form for creating a new drive.
     */
    public function create(): View
    {
        Gate::authorize('create', Drive::class);

        return view('pages.staff.drives.create');
    }

    /**
     * Store a newly created drive.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Drive::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'venue' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
        ]);

        try {
            $drive = $this->driveService->create($validated, $request->user());

            return redirect()->route('staff.drives.show', $drive)
                ->with('status', 'Drive created as a draft. You can now add slots and publish when ready.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }
    }

    /**
     * Display the specified drive with slot management.
     */
    public function show(Drive $drive): View
    {
        Gate::authorize('view', $drive);

        $slots = $drive->slots()
            ->orderBy('starts_at')
            ->get();

        $groupedSlots = $slots->groupBy(function ($slot) {
            return DateHelper::formatDate($slot->starts_at, 'Y-m-d');
        });

        $announcementLogs = NotificationLog::query()
            ->where('related_type', Drive::class)
            ->where('related_id', $drive->id)
            ->where('type', 'new_drive_announcement')
            ->get();

        $announcementStats = [
            'total' => $announcementLogs->count(),
            'queued' => $announcementLogs->where('status', NotificationStatus::Queued)->count(),
            'sent' => $announcementLogs->where('status', NotificationStatus::Sent)->count(),
            'failed' => $announcementLogs->where('status', NotificationStatus::Failed)->count(),
            'skipped' => $announcementLogs->where('status', NotificationStatus::Skipped)->count(),
        ];

        $optedInRecipientsCount = User::query()
            ->where('role', Role::Donor)
            ->where('is_active', true)
            ->whereNotNull('email_verified_at')
            ->where('notify_new_drives', true)
            ->count();

        $mailBudget = app(MailBudget::class);
        $remainingBudget = $mailBudget->remaining();
        $chunkSize = $mailBudget->announcementChunk();
        $estimatedRuns = $chunkSize > 0 ? (int) ceil($optedInRecipientsCount / $chunkSize) : 1;

        return view('pages.staff.drives.show', compact(
            'drive',
            'groupedSlots',
            'slots',
            'announcementStats',
            'optedInRecipientsCount',
            'remainingBudget',
            'estimatedRuns'
        ));
    }

    /**
     * Queue new drive announcement to opted-in donors.
     */
    public function sendAnnouncement(Drive $drive): RedirectResponse
    {
        Gate::authorize('publish', $drive);

        if (! $drive->isPublished()) {
            return back()->with('error', 'Announcements can only be sent for published blood drives.');
        }

        $alreadyQueued = NotificationLog::query()
            ->where('related_type', Drive::class)
            ->where('related_id', $drive->id)
            ->where('type', 'new_drive_announcement')
            ->exists();

        if ($alreadyQueued) {
            return back()->with('error', 'An announcement has already been queued or sent for this blood drive.');
        }

        $recipients = User::query()
            ->where('role', Role::Donor)
            ->where('is_active', true)
            ->whereNotNull('email_verified_at')
            ->where('notify_new_drives', true)
            ->get(['id']);

        if ($recipients->isEmpty()) {
            return back()->with('error', 'No recipients who have opted in are currently available.');
        }

        $now = Carbon::now('UTC');
        $rows = [];
        foreach ($recipients as $recipient) {
            $rows[] = [
                'user_id' => $recipient->id,
                'type' => 'new_drive_announcement',
                'channel' => 'mail',
                'status' => NotificationStatus::Queued->value,
                'sent_at' => null,
                'related_type' => Drive::class,
                'related_id' => $drive->id,
                'error' => null,
                'dedupe_key' => "announcement:drive:{$drive->id}:user:{$recipient->id}",
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            NotificationLog::insertOrIgnore($chunk);
        }

        AuditLogger::record(auth()->user(), 'drive.announcement_sent', $drive, [
            'drive_id' => $drive->id,
            'recipient_count' => $recipients->count(),
        ]);

        return redirect()->route('staff.drives.show', $drive)
            ->with('status', "Announcement queued for {$recipients->count()} recipients who have opted in. Dispatch will occur in 10-minute scheduled batches.");
    }

    /**
     * Close out drive marking remaining booked appointments as no-show.
     */
    public function closeOut(Drive $drive, AttendanceService $attendanceService): RedirectResponse
    {
        Gate::authorize('closeOut', $drive);

        try {
            $result = $attendanceService->closeOutDrive($drive, auth()->user());

            return redirect()->route('staff.drives.show', $drive)
                ->with('status', "Drive closed out successfully. {$result['no_show_count']} remaining bookings marked as no-show.");
        } catch (CloseOutBlockedException $e) {
            $names = $e->unresolvedDonors->pluck('donor_name')->join(', ');

            return redirect()->route('staff.drives.show', $drive)
                ->with('error', "Cannot close out drive while checked-in donors have no recorded outcome: {$names}.");
        } catch (NotAllowedException $e) {
            return redirect()->route('staff.drives.show', $drive)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Show form for editing an existing drive.
     */
    public function edit(Drive $drive): View
    {
        Gate::authorize('update', $drive);

        if ($drive->isPast()) {
            return back()->with('error', 'Past drives are read-only and cannot be edited.');
        }

        return view('pages.staff.drives.edit', compact('drive'));
    }

    /**
     * Update the specified drive.
     */
    public function update(Request $request, Drive $drive): RedirectResponse
    {
        Gate::authorize('update', $drive);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'venue' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
        ]);

        try {
            $this->driveService->update($drive, $validated);

            return redirect()->route('staff.drives.show', $drive)
                ->with('status', 'Drive details updated successfully.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }
    }

    /**
     * Publish the drive.
     */
    public function publish(Drive $drive): RedirectResponse
    {
        Gate::authorize('publish', $drive);

        try {
            $this->driveService->publish($drive);

            return redirect()->route('staff.drives.show', $drive)
                ->with('status', 'Drive published successfully! It is now visible to the public.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }
    }

    /**
     * Close the drive.
     */
    public function close(Drive $drive): RedirectResponse
    {
        Gate::authorize('close', $drive);

        try {
            $this->driveService->close($drive);

            return redirect()->route('staff.drives.show', $drive)
                ->with('status', 'Drive has been closed.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }
    }

    /**
     * Reopen a closed drive.
     */
    public function reopen(Drive $drive): RedirectResponse
    {
        Gate::authorize('reopen', $drive);

        try {
            $this->driveService->reopen($drive);

            return redirect()->route('staff.drives.show', $drive)
                ->with('status', 'Drive reopened and published again.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }
    }

    /**
     * Delete a draft drive.
     */
    public function destroy(Drive $drive): RedirectResponse
    {
        Gate::authorize('delete', $drive);

        try {
            $this->driveService->delete($drive);

            return redirect()->route('staff.drives')
                ->with('status', 'Draft drive deleted.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }
    }
}
