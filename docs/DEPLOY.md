# Deployment Guide (Laravel Cloud)

This guide documents deploying DonorLink UG to **Laravel Cloud** with MySQL or PostgreSQL, database sessions, database cache, database queues, and Brevo SMTP.

---

## 1. Environment Variables Configuration

Configure these environment variables in your Laravel Cloud environment settings. **Never commit secrets to git.**

| Variable | Required | Purpose | Production Example |
| :--- | :---: | :--- | :--- |
| `APP_NAME` | Yes | Application name for page titles and branding | `DonorLink UG` |
| `APP_ENV` | Yes | Application environment (must be `production`) | `production` |
| `APP_KEY` | Yes | 32-character AES application encryption key | `base64:...` |
| `APP_DEBUG` | Yes | Stack trace visibility (must be `false`) | `false` |
| `APP_URL` | Yes | Canonical public HTTPS base URL | `https://donorlink.ubts.go.ug` |
| `DB_CONNECTION` | Yes | Database connection driver | `mysql` (or `pgsql`) |
| `DB_HOST` | Yes | Database cluster host | `10.0.0.10` |
| `DB_PORT` | Yes | Database cluster port | `3306` (or `5432`) |
| `DB_DATABASE` | Yes | Database name | `donorlink_prod` |
| `DB_USERNAME` | Yes | Database username | `donorlink_app` |
| `DB_PASSWORD` | Yes | Database password | `(secret)` |
| `SESSION_DRIVER` | Yes | Session persistence backend | `database` |
| `SESSION_LIFETIME` | No | Session idle lifetime in minutes | `120` |
| `SESSION_SECURE_COOKIE` | Yes | Restrict session cookies to HTTPS | `true` |
| `CACHE_STORE` | Yes | Application cache backend | `database` |
| `QUEUE_CONNECTION` | Yes | Asynchronous task queue driver | `database` |
| `MAIL_MAILER` | Yes | Mail transport driver | `smtp` |
| `MAIL_HOST` | Yes | Brevo SMTP hostname | `smtp-relay.brevo.com` |
| `MAIL_PORT` | Yes | Brevo SMTP TLS port | `587` |
| `MAIL_USERNAME` | Yes | Brevo SMTP login username / API key | `ubts-notifications@brevo.com` |
| `MAIL_PASSWORD` | Yes | Brevo SMTP password / key | `(secret)` |
| `MAIL_ENCRYPTION` | Yes | Transport layer security encryption | `tls` |
| `MAIL_FROM_ADDRESS` | Yes | Verified UBTS sender email address | `appointments@ubts.go.ug` |
| `MAIL_FROM_NAME` | Yes | Friendly sender name for notifications | `DonorLink UG` |
| `TRUSTED_PROXIES` | Yes | Proxies trusted behind cloud load balancer | `*` |
| `DONORLINK_BOOKING_CUTOFF_MINUTES` | No | Cutoff window before slot start | `60` |
| `DONORLINK_MAIL_DAILY_BUDGET` | Yes | Total 24h email limit (Brevo allowance) | `250` |
| `DONORLINK_MAIL_TRANSACTIONAL_RESERVE` | Yes | Reserved sending quota for appointments | `100` |
| `DONORLINK_MAIL_ANNOUNCEMENT_CHUNK` | No | Batch size for announcement runs | `40` |
| `DONORLINK_RECORDING_GRACE_DAYS` | No | Post-drive walk-in recording window | `7` |
| `DONORLINK_LOW_FILL_THRESHOLD` | No | Fill rate alert threshold (0.25 = 25%) | `0.25` |
| `DONORLINK_PRIVACY_CONTACT_EMAIL` | Yes | DPO privacy contact email | `privacy@ubts.go.ug` |
| `DONORLINK_PRIVACY_CONTACT_ADDRESS` | Yes | UBTS headquarters address | `Nakasero Hill Road, Kampala` |
| `DONORLINK_PRIVACY_RETENTION_SUMMARY` | Yes | Summary of data retention period | `5 years from last verified visit` |
| `DONORLINK_PRIVACY_REMOVAL_PROCESS` | Yes | Contact details for account erasure | `Email privacy@ubts.go.ug` |
| `DONORLINK_PRIVACY_LAST_UPDATED` | Yes | Privacy notice revision date | `October 2026` |

---

## 2. First-Deploy Order vs. Subsequent Deploys

> [!IMPORTANT]
> On a brand-new cloud database, `donorlink:preflight` will intentionally block deployments until at least one active administrator is present (Preflight Check 12). Follow this exact three-step sequence for initial launch.

### Step 1: Initial Deploy (Bootstrap)
Set the build and deploy commands in Laravel Cloud:

**Build Command:**
```bash
composer install --no-dev --optimize-autoloader && npm ci && npm run build
```

**Deploy Hook Commands (First deploy only):**
```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Trigger the first deployment. The site will deploy and database tables will be created.

### Step 2: Bootstrap Initial Administrator
In the Laravel Cloud hosted terminal or command runner, execute the single-use invitation command:

```bash
php artisan donorlink:create-admin --invite --name="UBTS System Admin" --email="admin@ubts.go.ug"
```

**Terminal Output:**
```text
Administrator account created for UBTS System Admin (admin@ubts.go.ug).
One-time signed invitation link (valid for 72 hours):
https://donorlink.ubts.go.ug/staff/invitation/1?signature=...
Use this link to set your password and activate the administrator account.
```

1. Open the URL in your browser.
2. Set a strong password (minimum 8 characters, recommended 14+).
3. The account will be verified and activated. The invitation link becomes single-use and cannot be reused.

### Step 3: Enable Preflight Command for Subsequent Deploys
Once the initial administrator exists, update the **Deploy Hook Commands** in Laravel Cloud to include `donorlink:preflight`:

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan donorlink:preflight
```

From this point forward, every subsequent code release will run `donorlink:preflight` automatically. If any environment variable, migration, or critical security setting is misconfigured, the deployment will fail immediately before traffic reaches users.

---

## 3. Background Workers & Scheduler Setup

Laravel Cloud manages background processes natively through its UI:

1. **Scheduler:** In the Laravel Cloud application settings, toggle **Scheduler** to **ON**. Laravel Cloud will invoke `php artisan schedule:run` once per minute. Do not configure custom crontabs.
2. **Queue Worker:** Under Background Workers, add a worker process:
   - Command: `php artisan queue:work database --sleep=3 --tries=3 --timeout=90`
   - Processes: 1–2
3. **Verify Observability:** Sign in as administrator and navigate to `/admin/system` to verify both the scheduler and queue worker heartbeats report **Healthy**.

---

## 4. Additive Schema Evolution and Release Rollback Safety

All DonorLink UG database migrations are strictly **additive** (adding new tables or nullable/defaulted columns). No columns or tables are renamed or dropped in production migrations.

Because migrations are additive:
- Running `php artisan migrate --force` during deployment will never break code running on active containers.
- If a release ever needs to be rolled back in Laravel Cloud, the previous application release can continue running safely against the database schema without running reverse migrations or risking data loss.
