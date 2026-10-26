<?php

declare(strict_types=1);

namespace App\Http\Controllers\Donor;

use App\Enums\AppointmentStatus;
use App\Exceptions\AlreadyBookedException;
use App\Exceptions\BookingWindowClosedException;
use App\Exceptions\DriveNotOpenException;
use App\Exceptions\NotAllowedException;
use App\Exceptions\SlotFullException;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Slot;
use App\Services\BookingService;
use App\Support\DateHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function __construct(
        protected BookingService $bookingService,
    ) {}

    /**
     * Display a listing of the donor's appointments (Upcoming and Past).
     */
    public function index(Request $request): View
    {
        $appointments = $request->user()->appointments()
            ->with(['slot.drive'])
            ->get();

        $nowUtc = Carbon::now('UTC');

        // Appointments whose slot has NOT yet ended and whose status is Booked stay in Upcoming
        $upcomingAppointments = $appointments->filter(function (Appointment $appt) use ($nowUtc) {
            return $appt->status === AppointmentStatus::Booked
                && $appt->slot
                && $appt->slot->ends_at->gte($nowUtc);
        })->sortBy(fn (Appointment $appt) => $appt->slot?->starts_at);

        // Appointments whose slot has already ended belong in Past whatever their status, along with non-booked appointments
        $pastAppointments = $appointments->filter(function (Appointment $appt) use ($nowUtc) {
            return $appt->status !== AppointmentStatus::Booked
                || ($appt->slot && $appt->slot->ends_at->lt($nowUtc));
        })->sortByDesc(fn (Appointment $appt) => $appt->slot?->starts_at ?? $appt->created_at);

        return view('pages.donor.appointments', [
            'upcomingAppointments' => $upcomingAppointments,
            'pastAppointments' => $pastAppointments,
        ]);
    }

    /**
     * Book a new appointment.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slot_id' => ['required', 'integer', 'exists:slots,id'],
        ]);

        $slot = Slot::with('drive')->findOrFail($validated['slot_id']);

        try {
            $appointment = $this->bookingService->book($request->user(), $slot);

            $dateFormatted = DateHelper::formatDate($slot->starts_at);
            $rangeFormatted = DateHelper::formatRange($slot->starts_at, $slot->ends_at);

            return redirect()->route('donor.appointments')
                ->with('status', "Your appointment has been confirmed for {$dateFormatted} ({$rangeFormatted}). A confirmation is on its way to your email address.");
        } catch (SlotFullException $e) {
            return back()->with('error', 'That slot has just been filled. Please choose another time.');
        } catch (BookingWindowClosedException $e) {
            return back()->with('error', 'Booking has closed for this slot because it starts soon.');
        } catch (AlreadyBookedException $e) {
            return back()->with('error', 'You already have an appointment booked for this donation drive.');
        } catch (DriveNotOpenException|NotAllowedException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel an existing appointment.
     */
    public function destroy(Request $request, Appointment $appointment): RedirectResponse
    {
        Gate::authorize('cancel', $appointment);

        try {
            $this->bookingService->cancel($appointment, $request->user());

            return redirect()->route('donor.appointments')
                ->with('status', 'Your appointment has been cancelled. A confirmation is on its way to your email address.');
        } catch (NotAllowedException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reschedule an appointment to a new slot on the same drive.
     */
    public function reschedule(Request $request, Appointment $appointment): RedirectResponse
    {
        Gate::authorize('reschedule', $appointment);

        $validated = $request->validate([
            'new_slot_id' => ['required', 'integer', 'exists:slots,id'],
        ]);

        $newSlot = Slot::with('drive')->findOrFail($validated['new_slot_id']);

        try {
            $this->bookingService->reschedule($appointment, $newSlot, $request->user());

            $dateFormatted = DateHelper::formatDate($newSlot->starts_at);
            $rangeFormatted = DateHelper::formatRange($newSlot->starts_at, $newSlot->ends_at);

            return redirect()->route('donor.appointments')
                ->with('status', "Your appointment has been rescheduled to {$dateFormatted} ({$rangeFormatted}). A confirmation is on its way to your email address.");
        } catch (SlotFullException $e) {
            return back()->with('error', 'That slot has just been filled. Please choose another time.');
        } catch (BookingWindowClosedException $e) {
            return back()->with('error', 'Booking for the selected time slot has closed.');
        } catch (DriveNotOpenException|NotAllowedException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
