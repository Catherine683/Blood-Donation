# Staging Smoke Test Script

This manual walk-through verifies the full lifecycle of donor booking, communications, attendance, clinical recording, analytics, and staff administration on a staging environment.

---

## 1. Donor Registration, Verification & Booking

1. Visit `/register`.
2. Fill in the donor registration form:
   - Full Name: `Joan Nakato`
   - Email: `joan.test@example.com`
   - Phone: `0772123456`
   - Date of Birth: `1998-05-15`
   - Password: `Password123!@#`
   - Check the consent box.
3. Submit the form. Notice redirection to the email verification notice page (`/email/verify`).
4. Click the verification link received via email (or generated in staging mail logs).
5. Confirm successful redirection to the donor dashboard (`/donor`).
6. Click **Browse upcoming drives** (`/drives`).
7. Select an upcoming published drive and click an available 30-minute time slot.
8. Confirm reservation. Verify that the appointment appears as `Booked` on `/donor/appointments`.
9. Verify confirmation email received with an attached `.ics` calendar invitation (`METHOD:PUBLISH`).

---

## 2. Appointment Reminders Sweep

> [!NOTE]
> The `donorlink:send-reminders --now="YYYY-MM-DD HH:MM"` option is **development-only** and strictly refused when `APP_ENV=production`.
>
> **How to verify on Staging without `--now`:**
> 1. In staging, create a test slot starting 1 hour from current time (inside the 2-hour reminder window) or 23 hours from now (inside the 24-hour reminder window).
> 2. Book an appointment into that slot.
> 3. Run `php artisan donorlink:send-reminders` (or wait for the 10-minute scheduled cron).
> 4. Verify in `notification_logs` (or `/admin/notifications`) that an `appointment_reminder` notification was logged as `sent` with a unique `dedupe_key`.

---

## 3. Reschedule & Cancellation

1. In the donor dashboard (`/donor/appointments`), click **Reschedule** on the active appointment.
2. Select a different time slot on the same drive and confirm.
3. Verify that the previous slot's `booked_count` decremented by 1, the new slot's `booked_count` incremented by 1, and an updated `.ics` calendar attachment is received.
4. Click **Cancel appointment**. Provide a reason or confirm cancellation.
5. Verify status changes to `Cancelled`, slot capacity is restored, and cancellation email reminds the donor to remove the appointment from their calendar.

---

## 4. Staff Check-In, Clinical Outcome & Close-Out

1. Log in as a staff member or admin (`/login`).
2. Navigate to **Upcoming Drives** &rarr; select a published drive &rarr; click **Attendance Roster** (`/staff/drives/{drive}/roster`).
3. For an expected booked donor, click **Check In**. Verify timestamp is recorded and status remains `Booked` (checked in).
4. Click **Record Outcome**:
   - Choose `Donated` (or `Deferred`).
   - Add an optional staff note: `Standard voluntary blood donation completed.`
   - Confirm.
5. Verify appointment status updates to `Attended` and a verified `DonationRecord` is created.
6. **Walk-In Donor:**
   - In the Walk-in panel on the roster, search for an existing registered donor who did not book in advance.
   - Click **Record Walk-in Donation**.
   - Choose outcome and confirm. Verify a walk-in record with `appointment_id = null` is created.
7. **Close-Out Drive:**
   - Click **Close Out Drive**.
   - If un-checked-in booked appointments remain, choose whether to mark them as `No-Show`.
   - Confirm close-out. Verify drive status transitions to `Closed`.

---

## 5. Analytics & Dashboard Agreement

1. Navigate to the Staff Dashboard (`/staff`).
2. Open the **"About these numbers"** calculation guide.
3. Verify that:
   - **Booked turnout:** Matches the total appointments that were booked on the roster.
   - **Units collected:** Matches the count of records with outcome `donated`.
   - **Deferred:** Matches the count of records with outcome `deferred`.
   - **No-shows:** Matches marked no-shows.
4. Export the Period Summary CSV (`/staff/export/period-summary`) and the Drive Summary CSV (`/staff/drives/{drive}/export/summary`).
5. Open the CSV files in Excel/Numbers: verify numbers match the UI exactly and dangerous formula prefixes (`=`, `+`, `-`, `@`) are sanitized.

---

## 6. Staff Invitation & Account Management

1. Log in as an administrator &rarr; go to `/admin/staff`.
2. Click **Invite Staff Member**:
   - Name: `Nurse Sarah Nakabugo`
   - Email: `sarah.nurse@example.com`
   - Role: `Staff`
3. Click **Send Invitation**.
4. Verify an invitation email is queued/sent with a signed 72-hour link.
5. In a private browser window, open the signed link: verify the set-password form renders.
6. Set a password and log in. Verify access to staff routes and refusal on admin routes (403).
7. Return to the admin session on `/admin/staff`:
   - Click **Deactivate Account** on the new staff account.
   - Confirm.
   - In the other window, reload: verify the account is logged out or blocked with an inactive account notice.
