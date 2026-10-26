# DonorLink UG — Operations Runbook

This runbook provides emergency diagnosis and resolution procedures for system administrators and UBTS operational personnel.

---

## Escalation & Contact Points

- **Primary Contact:** UBTS Principal System Administrator (`admin@ubts.go.ug` / +256 700 000 001)
- **Medical Records Desk:** Nakasero Headquarters Donation Registry (`records@ubts.go.ug`)
- **Hosting / Cloud Provider:** Laravel Cloud Support Portal / Infrastructure Team

> [!CAUTION]
> **WHAT NOT TO DO UNDER ANY CIRCUMSTANCES:**
> 1. **DO NOT run `php artisan migrate:fresh` or `migrate:reset` on production.** This will erase real donor appointment history, verified outcomes, and audit logs.
> 2. **DO NOT run `php artisan db:seed` on production.** The seeder contains mock `[DEMO]` records and is strictly blocked by code in production.
> 3. **DO NOT delete or prune rows from `audit_logs` or `notification_logs`.** These are required for statutory legal compliance under the Uganda Data Protection and Privacy Act.
> 4. **DO NOT manually update `booked_count` in the database.** Always inspect appointments and allow `BookingService` to maintain atomic capacity.

---

## Operational Symptoms & Remediation

### 1. Reminders Not Sending

- **Symptom:** Donors report they did not receive their 24-hour or 2-hour appointment reminder.
- **Check:**
  1. Check the warning banner on `/staff` dashboard or visit `/admin/system`.
  2. Check the Scheduler Heartbeat age (if > 3 minutes, scheduler is stalled).
  3. Check the Queue Worker Heartbeat age (if > 15 minutes, queue worker is stalled).
  4. Run `SELECT * FROM notification_logs WHERE type = 'appointment_reminder' ORDER BY id DESC LIMIT 20;`.
- **Fix:**
  1. In Laravel Cloud, check that **Scheduler** is toggled **ON**.
  2. In Laravel Cloud, ensure the queue worker process (`php artisan queue:work database`) is active and has not crashed.
  3. Verify Brevo mail credentials in environment settings.
  4. Manually trigger a scheduler sweep from the hosted console if necessary: `php artisan donorlink:send-reminders`.

---

### 2. Emails Failing or Brevo Daily Allowance Reached

- **Symptom:** Notifications in `notification_logs` show status `failed` or warning `Rolling 24-hour email volume has reached...`.
- **Check:**
  1. Visit `/admin/system` and review **24h Email Volume** against **Daily Budget**.
  2. Visit `/admin/notifications` and filter by status `Failed` to view error messages.
  3. Check Brevo dashboard sending quotas.
- **Fix:**
  1. If quota reached (300/day on Brevo free plan), drive announcements automatically pause to preserve remaining headroom for transactional booking/rescheduling emails.
  2. If emergency quota is exhausted, upgrade the Brevo plan or increase `DONORLINK_MAIL_DAILY_BUDGET` after purchasing additional credits.
  3. If failing due to invalid SMTP credentials, verify `MAIL_USERNAME` and `MAIL_PASSWORD` in Laravel Cloud environment variables.

---

### 3. Queue Backlog or Failed Jobs

- **Symptom:** Pending jobs count on `/admin/system` keeps rising; notifications or background tasks are delayed.
- **Check:**
  1. Visit `/admin/system` &rarr; review **Recent Failed Queue Jobs** table.
  2. Inspect the truncated exception message (payloads and sensitive arguments are safely hidden).
- **Fix:**
  1. Restart the queue worker process in Laravel Cloud.
  2. To retry failed jobs: `php artisan queue:retry all`.
  3. Failed jobs are pruned automatically after 7 days (`queue:prune-failed --hours=168`).

---

### 4. Scheduler Not Running

- **Symptom:** Admin dashboard displays red warning banner: `"Reminders may not be sending: Background scheduler or queue worker heartbeat is missing or stale."`
- **Check:**
  1. Visit `/admin/system` &rarr; **Scheduler Heartbeat** status says `Stale` or `Never Run`.
- **Fix:**
  1. In Laravel Cloud, navigate to **Settings** &rarr; **Cron / Scheduler** and confirm it is enabled.
  2. Trigger an immediate heartbeat via the button on `/admin/system` or run `php artisan donorlink:heartbeat` in the console.

---

### 5. A Donor Cannot Sign In or Verify Email

- **Symptom:** Donor reports they cannot log in or verification link expired.
- **Check:**
  1. Visit `/staff/donors` and search for the donor by name, email, or Ugandan phone number (`07...` or `+256...`).
  2. Check if `is_active` is true. If false, account was deactivated.
  3. Check if email verification is pending.
- **Fix:**
  1. If account is inactive, a staff member or admin can toggle status back to active from the donor detail page.
  2. If verification email was lost, donor can request a fresh verification link from `/email/verify` or staff can assist them.
  3. If password was forgotten, direct donor to `/forgot-password`. (Password reset responses never reveal whether an email exists).

---

### 6. An Administrator Is Locked Out

- **Symptom:** No active administrator can sign in, or credentials were lost.
- **Check:**
  1. Check active administrators: `php artisan tinker --execute="echo App\Models\User::where('role', 'admin')->where('is_active', true)->count();"`.
- **Fix:**
  1. In the hosted console, run:
     ```bash
     php artisan donorlink:create-admin --invite --force --name="Recovery Admin" --email="admin-recovery@ubts.go.ug"
     ```
  2. Copy the generated signed 72-hour link from the console output.
  3. Open the link in a browser, set a new password, and sign in.
  4. From `/admin/staff`, reset or reactivate existing administrator accounts as required.

---

### 7. A Drive Was Closed Out by Mistake

- **Symptom:** Staff member closed out a drive prematurely while donors were still arriving.
- **Check:**
  1. Check drive status on `/staff/drives/{drive}`. If status is `closed`, attendees can still have walk-in outcomes recorded within the 7-day grace period.
- **Fix:**
  1. Open the drive detail page on `/staff/drives/{drive}`.
  2. Click the **Reopen Drive** button.
  3. The drive status returns to `published` (or `closed`), restoring public visibility and slot bookings if dates allow.

---

### 8. A Donor Reports an Erroneous Donation Record

- **Symptom:** A donor or staff member notes an outcome was incorrectly recorded as `deferred` instead of `donated` (or vice versa).
- **Check:**
  1. Visit `/staff/donors/{donor}` &rarr; **Donation History**.
  2. Verify medical paper documentation or collection bag barcode with Nakasero records.
- **Fix:**
  1. In the donor's record list, click **Correct Outcome**.
  2. Switch the outcome (`donated` &harr; `deferred`) and provide a mandatory explanation note.
  3. The system updates the record, recalculates the donor's derived `last_donated_on` date, and writes a permanent audit entry to `audit_logs` tracking who made the correction and why.

---

### 9. Slot Capacity or Booking Count Looks Inconsistent

- **Symptom:** Slot shows full but fewer active appointments are visible.
- **Check:**
  1. Run the database race check and integrity audit:
     ```bash
     php artisan donorlink:booking-race-check
     ```
- **Fix:**
  1. The command verifies row-level consistency between `slots.booked_count` and live `appointments` where `status = 'booked'`.
  2. If an anomaly is found, re-sync `booked_count` using the atomic count of active bookings:
     ```bash
     php artisan tinker --execute="App\Models\Slot::all()->each(function(\$s){ \$s->update(['booked_count' => \$s->appointments()->where('status', 'booked')->count()]); });"
     ```
