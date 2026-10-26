<?php

declare(strict_types=1);

namespace App\Livewire\Staff;

use App\Enums\AppointmentStatus;
use App\Enums\DonationOutcome;
use App\Enums\Role;
use App\Exceptions\CloseOutBlockedException;
use App\Exceptions\DonationRecordException;
use App\Exceptions\NotAllowedException;
use App\Models\Appointment;
use App\Models\Drive;
use App\Models\Slot;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\DonationRecorder;
use App\Services\ReportService;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.staff')]
#[Title('Drive Attendance Roster — Staff Portal')]
class DriveRoster extends Component
{
    #[Locked]
    public Drive $drive;

    // Filters & Search
    public string $search = '';

    public string $statusFilter = 'all';

    // Outcome recording modal state
    public bool $showOutcomeModal = false;

    #[Locked]
    public ?int $selectedAppointmentId = null;

    public string $outcome = 'donated';

    public string $staffNote = '';

    // Confirmation modal state for destructive actions
    public bool $showConfirmModal = false;

    #[Locked]
    public ?string $confirmAction = null; // 'no_show', 'cancel', 'undo_check_in'

    #[Locked]
    public ?int $confirmAppointmentId = null;

    #[Locked]
    public string $confirmTitle = '';

    #[Locked]
    public string $confirmMessage = '';

    // Walk-in panel state
    public string $walkInSearch = '';

    #[Locked]
    public ?int $selectedWalkInDonorId = null;

    public string $walkInOutcome = 'donated';

    public string $walkInStaffNote = '';

    // Close-out modal state
    public bool $showCloseOutModal = false;

    #[Locked]
    public int $pendingNoShowsCount = 0;

    /** @var array<int, array{appointment_id: int, donor_name: string, phone: string|null}> */
    #[Locked]
    public array $unresolvedCheckIns = [];

    // Feedback notifications
    public ?string $feedbackMessage = null;

    public string $feedbackType = 'success';

    public function mount(Drive $drive): void
    {
        Gate::authorize('manageRoster', $drive);
        $this->drive = $drive;
    }

    /**
     * Check if check-in is currently allowed for this drive.
     */
    public function canCheckIn(Appointment $appointment): bool
    {
        if ($appointment->status !== AppointmentStatus::Booked || $appointment->checked_in_at !== null) {
            return false;
        }

        if (! ($this->drive->isPublished() || $this->drive->isClosed())) {
            return false;
        }

        $slot = $appointment->slot;
        if (! $slot) {
            return false;
        }

        $todayEat = DateHelper::today();
        $slotDateEat = Carbon::parse($slot->starts_at)->setTimezone('Africa/Kampala')->toDateString();
        $driveStartsEat = Carbon::parse($this->drive->starts_on->toDateString(), 'Africa/Kampala');
        $driveEndsEat = Carbon::parse($this->drive->ends_on->toDateString(), 'Africa/Kampala');

        $isTodaySlot = $slotDateEat === $todayEat->toDateString();
        $isDriveRunning = $todayEat->gte($driveStartsEat) && $todayEat->lte($driveEndsEat);

        return $isTodaySlot || $isDriveRunning;
    }

    /**
     * Perform check-in for an appointment.
     */
    public function checkIn(int $appointmentId, AttendanceService $attendanceService): void
    {
        Gate::authorize('manageRoster', $this->drive);

        $appointment = Appointment::findOrFail($appointmentId);

        try {
            $attendanceService->checkIn($appointment, auth()->user());
            $this->setFeedback("Checked in {$appointment->user->name}.", 'success');
        } catch (NotAllowedException $e) {
            $this->setFeedback($e->getMessage(), 'error');
        }
    }

    /**
     * Request confirmation to undo check-in.
     */
    public function promptUndoCheckIn(int $appointmentId): void
    {
        Gate::authorize('manageRoster', $this->drive);

        $appointment = Appointment::with('user')->findOrFail($appointmentId);

        $this->confirmAction = 'undo_check_in';
        $this->confirmAppointmentId = $appointmentId;
        $this->confirmTitle = 'Undo Check-in';
        $this->confirmMessage = "Are you sure you want to undo check-in for {$appointment->user->name}? Their arrival status will return to pending.";
        $this->showConfirmModal = true;
    }

    /**
     * Request confirmation to mark an appointment as no-show.
     */
    public function promptNoShow(int $appointmentId): void
    {
        Gate::authorize('manageRoster', $this->drive);

        $appointment = Appointment::with('user')->findOrFail($appointmentId);

        $this->confirmAction = 'no_show';
        $this->confirmAppointmentId = $appointmentId;
        $this->confirmTitle = 'Mark as No-Show';
        $this->confirmMessage = "Are you sure you want to mark {$appointment->user->name} as no-show? This appointment slot will remain occupied and not be released.";
        $this->showConfirmModal = true;
    }

    /**
     * Request confirmation to cancel an appointment from the staff side.
     */
    public function promptCancel(int $appointmentId): void
    {
        Gate::authorize('manageRoster', $this->drive);

        $appointment = Appointment::with('user')->findOrFail($appointmentId);

        $this->confirmAction = 'cancel';
        $this->confirmAppointmentId = $appointmentId;
        $this->confirmTitle = 'Cancel Booking (Staff)';
        $this->confirmMessage = "Are you sure you want to cancel the booking for {$appointment->user->name}? One reserved place will be released immediately and an email cancellation notice will be sent to the donor.";
        $this->showConfirmModal = true;
    }

    /**
     * Confirm and execute the pending destructive action.
     */
    public function executeConfirmedAction(AttendanceService $attendanceService): void
    {
        Gate::authorize('manageRoster', $this->drive);

        if (! $this->confirmAppointmentId || ! $this->confirmAction) {
            $this->showConfirmModal = false;

            return;
        }

        $appointment = Appointment::with('user')->findOrFail($this->confirmAppointmentId);

        try {
            match ($this->confirmAction) {
                'undo_check_in' => $attendanceService->undoCheckIn($appointment, auth()->user()),
                'no_show' => $attendanceService->markNoShow($appointment, auth()->user()),
                'cancel' => $attendanceService->cancelByStaff($appointment, auth()->user()),
                default => null,
            };

            $label = match ($this->confirmAction) {
                'undo_check_in' => 'Check-in undone',
                'no_show' => 'Marked as no-show',
                'cancel' => 'Booking cancelled and slot place released',
                default => 'Action completed',
            };

            $this->setFeedback("{$label} for {$appointment->user->name}.", 'success');
        } catch (NotAllowedException $e) {
            $this->setFeedback($e->getMessage(), 'error');
        } finally {
            $this->showConfirmModal = false;
            $this->confirmAppointmentId = null;
            $this->confirmAction = null;
        }
    }

    /**
     * Open outcome recording modal for a booked appointment.
     */
    public function openOutcomeModal(int $appointmentId): void
    {
        Gate::authorize('manageRoster', $this->drive);

        $this->selectedAppointmentId = $appointmentId;
        $this->outcome = 'donated';
        $this->staffNote = '';
        $this->showOutcomeModal = true;
    }

    /**
     * Save donation outcome for appointment.
     */
    public function saveOutcome(DonationRecorder $recorder): void
    {
        Gate::authorize('manageRoster', $this->drive);

        $this->validate([
            'outcome' => ['required', 'in:donated,deferred'],
            'staffNote' => ['nullable', 'string', 'max:255'],
        ]);

        if (! $this->selectedAppointmentId) {
            return;
        }

        $appointment = Appointment::with(['user', 'slot'])->findOrFail($this->selectedAppointmentId);
        $outcomeEnum = DonationOutcome::from($this->outcome);

        try {
            $recorder->record(
                donor: $appointment->user,
                drive: $this->drive,
                outcome: $outcomeEnum,
                staff: auth()->user(),
                appointment: $appointment,
                note: $this->staffNote ?: null,
            );

            $this->setFeedback("Outcome '{$outcomeEnum->label()}' recorded for {$appointment->user->name}.", 'success');
            $this->showOutcomeModal = false;
            $this->selectedAppointmentId = null;
            $this->staffNote = '';
        } catch (DonationRecordException $e) {
            $this->setFeedback($e->getMessage(), 'error');
        }
    }

    /**
     * Record a walk-in donation.
     */
    public function recordWalkIn(DonationRecorder $recorder): void
    {
        Gate::authorize('manageRoster', $this->drive);

        $this->validate([
            'selectedWalkInDonorId' => ['required', 'exists:users,id'],
            'walkInOutcome' => ['required', 'in:donated,deferred'],
            'walkInStaffNote' => ['nullable', 'string', 'max:255'],
        ]);

        $donor = User::findOrFail($this->selectedWalkInDonorId);
        $outcomeEnum = DonationOutcome::from($this->walkInOutcome);

        try {
            $recorder->record(
                donor: $donor,
                drive: $this->drive,
                outcome: $outcomeEnum,
                staff: auth()->user(),
                appointment: null,
                note: $this->walkInStaffNote ?: null,
            );

            $this->setFeedback("Walk-in outcome '{$outcomeEnum->label()}' recorded for {$donor->name}.", 'success');
            $this->selectedWalkInDonorId = null;
            $this->walkInSearch = '';
            $this->walkInStaffNote = '';
            $this->walkInOutcome = 'donated';
        } catch (DonationRecordException $e) {
            $this->setFeedback($e->getMessage(), 'error');
        }
    }

    /**
     * Open close-out modal and inspect preconditions.
     */
    public function openCloseOutModal(): void
    {
        Gate::authorize('closeOut', $this->drive);

        $slots = $this->drive->slots()->get();
        $slotIds = $slots->pluck('id');

        $this->unresolvedCheckIns = Appointment::query()
            ->whereIn('slot_id', $slotIds)
            ->where('status', AppointmentStatus::Booked)
            ->whereNotNull('checked_in_at')
            ->whereDoesntHave('donationRecord')
            ->with('user')
            ->get()
            ->map(fn (Appointment $a) => [
                'appointment_id' => $a->id,
                'donor_name' => $a->user->name,
                'phone' => $a->user->phone,
            ])
            ->toArray();

        $this->pendingNoShowsCount = Appointment::query()
            ->whereIn('slot_id', $slotIds)
            ->where('status', AppointmentStatus::Booked)
            ->whereNull('checked_in_at')
            ->count();

        $this->showCloseOutModal = true;
    }

    /**
     * Confirm close out drive.
     */
    public function confirmCloseOut(AttendanceService $attendanceService): void
    {
        Gate::authorize('closeOut', $this->drive);
        try {
            $result = $attendanceService->closeOutDrive($this->drive, auth()->user());
            $this->setFeedback("Drive closed out. {$result['no_show_count']} remaining bookings marked as no-show.", 'success');
            $this->showCloseOutModal = false;
        } catch (CloseOutBlockedException $e) {
            $this->unresolvedCheckIns = $e->unresolvedDonors->toArray();
            $this->setFeedback('Cannot close out drive while checked-in donors have no recorded outcome.', 'error');
        } catch (NotAllowedException $e) {
            $this->setFeedback($e->getMessage(), 'error');
            $this->showCloseOutModal = false;
        }
    }

    protected function setFeedback(string $message, string $type = 'success'): void
    {
        $this->feedbackMessage = $message;
        $this->feedbackType = $type;
    }

    public function dismissFeedback(): void
    {
        $this->feedbackMessage = null;
    }

    public function render(): View
    {
        $reportService = app(ReportService::class);
        $summaryStats = $reportService->getDriveMetrics($this->drive);

        // Eager-loaded slots and appointments (prevents N+1)
        $slots = $this->drive->slots()
            ->with([
                'appointments' => function ($query) {
                    $query->with(['user.donorProfile', 'donationRecord'])
                        ->when($this->search !== '', function ($q) {
                            $term = '%'.trim($this->search).'%';
                            $q->whereHas('user', function ($uq) use ($term) {
                                $uq->where('name', 'like', $term)
                                    ->orWhere('phone', 'like', $term);
                            });
                        })
                        ->when($this->statusFilter === 'checked_in', function ($q) {
                            $q->where('status', AppointmentStatus::Booked->value)->whereNotNull('checked_in_at');
                        })
                        ->when($this->statusFilter === 'booked', function ($q) {
                            $q->where('status', AppointmentStatus::Booked->value);
                        })
                        ->when($this->statusFilter === 'attended', function ($q) {
                            $q->where('status', AppointmentStatus::Attended->value);
                        })
                        ->when($this->statusFilter === 'no_show', function ($q) {
                            $q->where('status', AppointmentStatus::NoShow->value);
                        })
                        ->when($this->statusFilter === 'cancelled', function ($q) {
                            $q->where('status', AppointmentStatus::Cancelled->value);
                        })
                        ->when($this->statusFilter === 'donated', function ($q) {
                            $q->whereHas('donationRecord', fn ($dq) => $dq->where('outcome', DonationOutcome::Donated->value));
                        })
                        ->when($this->statusFilter === 'deferred', function ($q) {
                            $q->whereHas('donationRecord', fn ($dq) => $dq->where('outcome', DonationOutcome::Deferred->value));
                        });
                },
            ])
            ->orderBy('starts_at')
            ->get();

        // Walk-in donor search results
        $walkInResults = collect();
        if (trim($this->walkInSearch) !== '') {
            $term = '%'.trim($this->walkInSearch).'%';
            $walkInResults = User::query()
                ->where('role', Role::Donor)
                ->where('is_active', true)
                ->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term);
                })
                ->limit(6)
                ->get();
        }

        // Selected walk-in donor details
        $selectedWalkInDonor = $this->selectedWalkInDonorId
            ? User::with('donorProfile')->find($this->selectedWalkInDonorId)
            : null;

        // Selected appointment for outcome modal
        $selectedAppointment = $this->selectedAppointmentId
            ? Appointment::with(['user.donorProfile', 'slot'])->find($this->selectedAppointmentId)
            : null;

        // Check if close out can be offered
        $nowUtc = Carbon::now('UTC');
        $todayEat = DateHelper::today()->toDateString();
        $driveEndsEat = $this->drive->ends_on->format('Y-m-d');
        $allSlotsEnded = $slots->isNotEmpty() && $slots->every(fn (Slot $s) => $s->ends_at->lte($nowUtc));
        $endsOnPassed = $driveEndsEat < $todayEat;
        $canCloseOut = ($allSlotsEnded || $endsOnPassed) && ! $this->drive->isClosed();

        return view('livewire.staff.drive-roster', compact(
            'summaryStats',
            'slots',
            'walkInResults',
            'selectedWalkInDonor',
            'selectedAppointment',
            'canCloseOut'
        ));
    }
}
