<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Booking Cutoff Window (in minutes)
    |--------------------------------------------------------------------------
    |
    | Donors cannot book or reschedule into a slot that starts within this
    | number of minutes from the current time. Default is 60 minutes.
    |
    */
    'booking_cutoff_minutes' => (int) env('DONORLINK_BOOKING_CUTOFF_MINUTES', 60),

    /*
    |--------------------------------------------------------------------------
    | Email Delivery Budget & Rate Protection (Brevo Free Plan)
    |--------------------------------------------------------------------------
    |
    | Brevo's free sending allowance is capped daily (300 emails/day).
    | We enforce a daily budget, reserve a guaranteed threshold for transactional
    | appointment emails/reminders, and throttle announcements into runs.
    |
    */
    'mail' => [
        'daily_budget' => (int) env('DONORLINK_MAIL_DAILY_BUDGET', 250),
        'transactional_reserve' => (int) env('DONORLINK_MAIL_TRANSACTIONAL_RESERVE', 100),
        'announcement_chunk' => (int) env('DONORLINK_MAIL_ANNOUNCEMENT_CHUNK', 40),
    ],

    /*
    |--------------------------------------------------------------------------
    | Donation Recording Grace Period (Days)
    |--------------------------------------------------------------------------
    |
    | Number of days after a drive ends_on date during which staff can still
    | record walk-in donations. Default is 7 days.
    |
    */
    'recording_grace_days' => (int) env('DONORLINK_RECORDING_GRACE_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Low Fill Rate Threshold
    |--------------------------------------------------------------------------
    |
    | Published drives starting within 3 days with a fill rate strictly under
    | this threshold trigger an operational "Needs Attention" alert on the dashboard.
    | Default is 0.25 (25%).
    |
    */
    'low_fill_threshold' => (float) env('DONORLINK_LOW_FILL_THRESHOLD', 0.25),

    /*
    |--------------------------------------------------------------------------
    | Config-Driven Privacy and Account Erasure Settings
    |--------------------------------------------------------------------------
    |
    | Privacy notice and profile removal process information. When set, each
    | value is rendered escaped in plain text; when unset, sections are omitted.
    |
    */
    'privacy' => [
        'contact_email' => env('DONORLINK_PRIVACY_CONTACT_EMAIL'),
        'contact_address' => env('DONORLINK_PRIVACY_CONTACT_ADDRESS'),
        'retention_summary' => env('DONORLINK_PRIVACY_RETENTION_SUMMARY'),
        'removal_process' => env('DONORLINK_PRIVACY_REMOVAL_PROCESS'),
        'last_updated' => env('DONORLINK_PRIVACY_LAST_UPDATED'),
    ],
];
