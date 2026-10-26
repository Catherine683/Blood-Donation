# DonorLink UG — Launch Checklist

This checklist must be executed and signed off by the **Project Owner** and **UBTS Technical Lead** before switching public traffic to the production environment on Laravel Cloud.

---

## 1. Domain & DNS Authentication

- [ ] **Canonical HTTPS Domain:** Ensure `APP_URL` is set to the official domain (e.g. `https://donorlink.ubts.go.ug`) and points to the Laravel Cloud load balancer.
- [ ] **Brevo Sender Domain DNS Records:**
  - [ ] **SPF Record:** `v=spf1 include:spf.brevo.com ~all` configured on the sending domain.
  - [ ] **DKIM Record:** Brevo public key TXT record added to DNS and verified in the Brevo console.
  - [ ] **DMARC Record:** `v=DMARC1; p=quarantine; rua=mailto:dmarc@ubts.go.ug` configured.
  - [ ] **Sender Verification:** `MAIL_FROM_ADDRESS` (`appointments@ubts.go.ug`) verified as an active sender inside Brevo.

---

## 2. Mail Delivery & Budget Safeguards

- [ ] **Brevo Plan Active:** Confirm active plan and sending allowance (e.g. 300 free emails/day or upgraded tier).
- [ ] **Budget Settings Configured Below Plan Limit:**
  - `DONORLINK_MAIL_DAILY_BUDGET=250` (or appropriate limit strictly below plan quota).
  - `DONORLINK_MAIL_TRANSACTIONAL_RESERVE=100` (guaranteed headroom for booking confirmations and reminders).
  - Daily budget must be strictly greater than transactional reserve.

---

## 3. Database & Backup Verification

- [ ] **Managed MySQL / PostgreSQL Running:** Database cluster provisioned with SSL/TLS in Laravel Cloud.
- [ ] **Automated Backups Scheduled:** Point-in-time recovery or daily automated backups scheduled in cloud settings.
- [ ] **Backup Restore Tested:** Execute a test restore of a database snapshot to a staging database to verify backup integrity.
- [ ] **Race Check Re-run against MySQL:** Execute the booking concurrency race check against the target MySQL database:
  ```bash
  php artisan donorlink:booking-race-check
  ```
  Ensure all concurrent booking attempts succeed without overbooking slot capacity.

---

## 4. Privacy & Statutory Compliance (Uganda DPA 2019)

- [ ] **UBTS Privacy Answers Configured:** Verify all statutory privacy variables are populated:
  - `DONORLINK_PRIVACY_CONTACT_EMAIL="privacy@ubts.go.ug"`
  - `DONORLINK_PRIVACY_CONTACT_ADDRESS="Uganda Blood Transfusion Services Headquarters, Nakasero Hill Road, P.O. Box 1772, Kampala"`
  - `DONORLINK_PRIVACY_RETENTION_SUMMARY="Donation records are retained for statutory medical traceability under UBTS guidelines."`
  - `DONORLINK_PRIVACY_REMOVAL_PROCESS="To request account removal, email privacy@ubts.go.ug."`
  - `DONORLINK_PRIVACY_LAST_UPDATED="October 2026"`

---

## 5. Security & Preflight Verification

- [ ] **Run Preflight Command:** In the hosted console, execute:
  ```bash
  php artisan donorlink:preflight
  ```
  **Must report 0 FAILURES.**
- [ ] **Session Cookie Flags:** Confirm `SESSION_SECURE_COOKIE=true` and `SESSION_DRIVER=database`.
- [ ] **Zero Debug Leaks:** Confirm `APP_DEBUG=false`.
- [ ] **Trusted Proxies Configured:** Confirm `TRUSTED_PROXIES=*` so signed links and HTTPS schemes are valid behind cloud load balancers.

---

## 6. Access Control & Account Provisioning

- [ ] **Create Initial Administrator:** Run console invitation command:
  ```bash
  php artisan donorlink:create-admin --invite --name="Lead Administrator" --email="admin@ubts.go.ug"
  ```
- [ ] **Set Admin Password:** Click the generated 72-hour signed link, set password, and verify access.
- [ ] **Two-Factor Authentication:** Enable 2FA on the primary administrator account from `/settings/security`.
- [ ] **Invite Initial Staff Nurses / Coordinators:** From `/admin/staff`, invite key on-site UBTS coordinators with the `Staff` role.

---

## 7. Staging Smoke Test Verification

- [ ] Run the complete staging smoke test script in [docs/SMOKE_TEST.md](file:///e:/Donor%20Link%20UG/docs/SMOKE_TEST.md).
- [ ] Confirm donor registration, verification, slot reservation, cancellation, reschedule, on-site check-in, donation outcome recording, walk-in recording, and drive close-out.
- [ ] Confirm CSV reports and donor data exports download cleanly without formula injection.

---

## 8. Ongoing Monitoring & Rollback Plan

- [ ] **Operational Monitoring Responsibility:**
  - Designated Administrator: **UBTS Lead System Administrator**
  - Check Frequency: Daily review of `/admin/system` (monitoring scheduler heartbeat, worker heartbeat, failed queue jobs, and 24h mail volume).
- [ ] **Emergency Rollback Plan:**
  - If a deployment failure occurs, trigger **Rollback Release** in Laravel Cloud.
  - Because all migrations are strictly additive, previous code releases can run safely against the newer database schema without rolling back the database.
