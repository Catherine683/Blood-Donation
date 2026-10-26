<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Drive;
use App\Models\Slot;
use App\Services\SlotGenerator;
use App\Services\SlotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SlotController extends Controller
{
    public function __construct(
        protected SlotService $slotService,
        protected SlotGenerator $slotGenerator,
    ) {}

    /**
     * Store a single slot for a drive.
     */
    public function store(Request $request, Drive $drive): RedirectResponse
    {
        Gate::authorize('create', Slot::class);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'start_time' => ['required', 'string'],
            'end_time' => ['required', 'string'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $this->slotService->create($drive, $validated);

            return redirect()->route('staff.drives.show', $drive)
                ->with('status', 'Slot added successfully.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }
    }

    /**
     * Preview slot generation (JSON API for interactive UI or modal preview).
     */
    public function preview(Request $request, Drive $drive): JsonResponse|RedirectResponse
    {
        Gate::authorize('create', Slot::class);

        $validated = $request->validate([
            'dates' => ['required'],
            'start_time' => ['required', 'string'],
            'end_time' => ['required', 'string'],
            'slot_length' => ['required', 'integer', 'in:10,15,20,30,45,60'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
            'break_start_time' => ['nullable', 'string'],
            'break_end_time' => ['nullable', 'string'],
        ]);

        if (is_string($validated['dates'])) {
            $validated['dates'] = array_filter(array_map('trim', explode(',', $validated['dates'])));
        }

        try {
            $preview = $this->slotGenerator->preview($drive, $validated);

            if ($request->wantsJson()) {
                return response()->json($preview);
            }

            return back()->with('generator_preview', $preview)->withInput();
        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                return response()->json(['errors' => $e->errors()], 422);
            }

            return back()->withErrors($e->errors())->withInput();
        }
    }

    /**
     * Execute slot generation.
     */
    public function generate(Request $request, Drive $drive): RedirectResponse
    {
        Gate::authorize('create', Slot::class);

        $validated = $request->validate([
            'dates' => ['required'],
            'start_time' => ['required', 'string'],
            'end_time' => ['required', 'string'],
            'slot_length' => ['required', 'integer', 'in:10,15,20,30,45,60'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
            'break_start_time' => ['nullable', 'string'],
            'break_end_time' => ['nullable', 'string'],
        ]);

        if (is_string($validated['dates'])) {
            $validated['dates'] = array_filter(array_map('trim', explode(',', $validated['dates'])));
        }

        try {
            $result = $this->slotGenerator->generate($drive, $validated);

            $message = "Generated {$result['created']} slots successfully.";
            if ($result['skipped'] > 0) {
                $reasons = [];
                if ($result['skipped_collisions'] > 0) {
                    $reasons[] = "{$result['skipped_collisions']} collisions";
                }
                if ($result['skipped_breaks'] > 0) {
                    $reasons[] = "{$result['skipped_breaks']} break overlaps";
                }
                if ($result['skipped_past'] > 0) {
                    $reasons[] = "{$result['skipped_past']} past times";
                }
                $message .= ' Skipped '.$result['skipped'].' slots ('.implode(', ', $reasons).').';
            }

            return redirect()->route('staff.drives.show', $drive)
                ->with('status', $message);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }
    }

    /**
     * Update an existing slot.
     */
    public function update(Request $request, Slot $slot): RedirectResponse
    {
        Gate::authorize('update', $slot);

        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'start_time' => ['nullable', 'string'],
            'end_time' => ['nullable', 'string'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $this->slotService->update($slot, $validated);

            return redirect()->route('staff.drives.show', $slot->drive_id)
                ->with('status', 'Slot updated successfully.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }
    }

    /**
     * Delete an existing slot.
     */
    public function destroy(Slot $slot): RedirectResponse
    {
        Gate::authorize('delete', $slot);

        $driveId = $slot->drive_id;

        try {
            $this->slotService->delete($slot);

            return redirect()->route('staff.drives.show', $driveId)
                ->with('status', 'Slot deleted successfully.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }
    }
}
