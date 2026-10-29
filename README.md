# DonorLink UG

DonorLink UG is a voluntary blood donation appointment scheduling, capacity management, and email notification platform for the Uganda Blood Transfusion Services (UBTS), headquartered on Nakasero Hill Road, Kampala.

---

## Requirements

- **PHP 8.3+** with SQLite PDO, cURL, MBString, and OpenSSL extensions
- **Composer 2.x**
- **Node.js 20+** and **npm**

---

## Local Setup

1. **Clone and enter the repository:**
   ```bash
   git clone <repo-url> donorlink-ug
   cd donorlink-ug
   ```

2. **Install dependencies:**
   ```bash
   composer install
   npm install
   ```

3. **Configure environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Prepare the database & run dev seeders:**
   ```bash
   php artisan migrate --seed
   ```
   *Note: The development seeder creates local administrator and staff accounts with password `password`:*
   - Administrator: `admin@donorlink.test`
   - Staff: `staff@donorlink.test`

   Running `php artisan db:seed` removes legacy `[DEMO]` drives and their demo-only donor accounts. It does not create sample drives or donor accounts.

5. **Build or run frontend assets:**
   ```bash
   # Development mode with Hot Module Replacement (HMR)
   npm run dev

   # Or compile production bundle
   npm run build
   ```

6. **Serve the application:**
   ```bash
   php artisan serve
   ```
   Open [http://localhost:8000](http://localhost:8000) in your browser.

---

## Asynchronous Notifications & Scheduling

DonorLink UG uses queued email notifications via Laravel's mail channel and scheduled artisan commands for time-sensitive sweeps.

### Queue Worker
The application uses the `database` queue driver. In local development or production, run:
```bash
# Run worker continuously
php artisan queue:work

# Or process existing jobs and exit (ideal for automated test runs)
php artisan queue:work --stop-when-empty
```

### Scheduler
Scheduled commands (reminders sweep and announcement batching) run every 10 minutes:
- **Local Development:**
  ```bash
  php artisan schedule:work
  ```
- **Laravel Cloud:**
  Enable the **Scheduler** toggle in the Laravel Cloud application environment settings.

---

## Email & Send Budget Management

Email is our **only** communication channel. Because we run on Brevo's free tier, sending reliability, rate protection, and transactional reserves are actively enforced.

### Reading Emails with the `log` Mailer
In local development, set `MAIL_MAILER=log` in `.env`. All outbound emails (with full headers, plain-text alternatives, and raw `.ics` calendar attachments) are written to `storage/logs/laravel.log`.

Inspect outgoing emails:
- **PowerShell (Windows):**
  ```powershell
  Get-Content storage\logs\laravel.log -Tail 100
  ```
- **Bash / Linux / macOS:**
  ```bash
  tail -f storage/logs/laravel.log
  ```

### Simulating Reminder Sweeps (`--now`)
Reminders are computed dynamically from the current state rather than relying on delayed jobs. To test the 24-hour and 2-hour reminder windows locally, use the simulated time flag:
```bash
php artisan donorlink:send-reminders --now="2026-10-24 09:00"
```
*Note: The `--now` parameter is restricted to non-production environments and will refuse to execute when `APP_ENV=production`.*

### Testing Brevo SMTP Delivery
To test Brevo SMTP connectivity:
1. Set Brevo credentials in `.env`:
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=smtp-relay.brevo.com
   MAIL_PORT=587
   MAIL_USERNAME=your-brevo-login-email
   MAIL_PASSWORD=your-brevo-smtp-key
   MAIL_FROM_ADDRESS="no-reply@<your-domain.ug>"
   MAIL_FROM_NAME="DonorLink UG"
   ```
2. Send a test email via tinker:
   ```bash
   php artisan tinker --execute="Mail::raw('Test email from DonorLink UG', fn(\$m) => \$m->to('your-email@example.com')->subject('Brevo Test'));"
   ```

### Brevo Free Plan Send Budget
Configured in `config/donorlink.php` via `.env`:
- `DONORLINK_MAIL_DAILY_BUDGET=250` (Daily sending ceiling)
- `DONORLINK_MAIL_TRANSACTIONAL_RESERVE=100` (Guaranteed capacity reserved strictly for confirmations, reschedules, cancellations, reminders, and verification)
- `DONORLINK_MAIL_ANNOUNCEMENT_CHUNK=40` (Maximum announcements sent per 10-minute dispatch run)

Transactional messages are never blocked. If rolling 24-hour volume exceeds 90% of `daily_budget`, a system warning is logged. Announcements automatically halt when remaining capacity drops below the transactional reserve.

---

## Production Launch Checklist (Laravel Cloud)

Before launching into live production:

- [ ] **Sender Domain Authentication:** Configure SPF, DKIM, and DMARC DNS records for `<your-domain.ug>` in Brevo and verify that domain reputation passes Brevo verification.
- [ ] **Production `APP_URL`:** Configure `APP_URL=https://<your-domain.ug>` in Laravel Cloud environment variables so signed unsubscribe routes and email verification links generate valid HTTPS URLs.
- [ ] **Queue Worker (Laravel Cloud):** Enable the **Queue Worker** toggle in the Laravel Cloud application settings.
- [ ] **Scheduler (Laravel Cloud):** Enable the **Scheduler** toggle in the Laravel Cloud application settings.
- [ ] **Confirm Brevo Plan Limits:** Verify current daily allowance on Brevo's website and set `DONORLINK_MAIL_DAILY_BUDGET` below the verified account limit.
- [ ] **Concurrency Race-Check:** Re-run the concurrent capacity check against the production database engine (MySQL/PostgreSQL) on staging:
  ```bash
  php artisan donorlink:booking-race-check --capacity=5 --attempts=20
  ```

---

---

## Operational Documentation

Comprehensive architectural, deployment, and runbook guides are located in the `docs/` directory:

- [docs/ARCHITECTURE.md](file:///e:/Donor%20Link%20UG/docs/ARCHITECTURE.md): System modules, services, events, background jobs, ER diagram, and foundational business rules.
- [docs/DEPLOY.md](file:///e:/Donor%20Link%20UG/docs/DEPLOY.md): Laravel Cloud deployment instructions, environment variables table, preflight verification, and worker setup.
- [docs/RUNBOOK.md](file:///e:/Donor%20Link%20UG/docs/RUNBOOK.md): Production operational incident response procedures (symptom &rarr; check &rarr; fix).
- [docs/LAUNCH_CHECKLIST.md](file:///e:/Donor%20Link%20UG/docs/LAUNCH_CHECKLIST.md): Pre-launch verification checklist for the project owner and technical leads.
- [docs/SMOKE_TEST.md](file:///e:/Donor%20Link%20UG/docs/SMOKE_TEST.md): Step-by-step manual staging verification script for donor, staff, and admin workflows.

---

## Configuration Reference (`config/donorlink.php`)

All operational thresholds, email budgets, and statutory privacy parameters can be adjusted via environment variables:

| Setting Key | Environment Variable | Default | Meaning & Operational Context |
| :--- | :--- | :--- | :--- |
| `donorlink.booking_cutoff_minutes` | `DONORLINK_BOOKING_CUTOFF_MINUTES` | `60` | Number of minutes before slot start time after which new bookings and reschedules into the slot are refused. |
| `donorlink.mail.daily_budget` | `DONORLINK_MAIL_DAILY_BUDGET` | `250` | Maximum email volume allowed across any rolling 24-hour window (protects Brevo sending limits). |
| `donorlink.mail.transactional_reserve` | `DONORLINK_MAIL_TRANSACTIONAL_RESERVE` | `100` | Guaranteed email capacity reserved strictly for transactional confirmations, reschedules, cancellations, reminders, and auth verification. |
| `donorlink.mail.announcement_chunk` | `DONORLINK_MAIL_ANNOUNCEMENT_CHUNK` | `40` | Maximum announcements dispatched per 10-minute scheduled sweep to prevent SMTP rate-bursts. |
| `donorlink.recording_grace_days` | `DONORLINK_RECORDING_GRACE_DAYS` | `7` | Number of days following drive `ends_on` date during which staff can still record walk-in donations. |
| `donorlink.low_fill_threshold` | `DONORLINK_LOW_FILL_THRESHOLD` | `0.25` | Fill rate fraction (25%) below which upcoming drives starting within 3 days trigger a dashboard alert. |
| `donorlink.trusted_proxies` | `TRUSTED_PROXIES` | `*` | Proxies trusted behind cloud load balancers for HTTPS scheme detection and signed URLs. |
| `donorlink.privacy.contact_email` | `DONORLINK_PRIVACY_CONTACT_EMAIL` | `null` | DPO contact email rendered in statutory privacy statements (Uganda DPA 2019). |
| `donorlink.privacy.contact_address` | `DONORLINK_PRIVACY_CONTACT_ADDRESS` | `null` | Physical postal address of UBTS headquarters rendered in privacy statements. |
| `donorlink.privacy.retention_summary` | `DONORLINK_PRIVACY_RETENTION_SUMMARY` | `null` | Plain text summary of medical data retention policies. |
| `donorlink.privacy.removal_process` | `DONORLINK_PRIVACY_REMOVAL_PROCESS` | `null` | Plain text instructions for requesting donor account deletion. |
| `donorlink.privacy.last_updated` | `DONORLINK_PRIVACY_LAST_UPDATED` | `null` | Revision date rendered on public privacy notice pages. |

---

## Testing & Quality

Run the combined lint check (Pint) and Pest test suite:
```bash
composer check
```

Run test suite against a dedicated MySQL database:
```bash
php artisan test --configuration=phpunit.mysql.xml
```
#   D o n o r l i n k  
 #   B l o o d - D o n a t i o n  
 