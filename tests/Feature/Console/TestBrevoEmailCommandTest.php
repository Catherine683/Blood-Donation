<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;

test('test email command executes successfully with array mailer', function () {
    Mail::fake();

    $this->artisan('donorlink:test-email test@example.com --mailer=array')
        ->expectsOutputToContain('Email & Brevo SMTP Diagnostic Tester')
        ->expectsOutputToContain('Active Mailer')
        ->assertExitCode(0);
});

test('test email command fails when invalid email is provided', function () {
    $this->artisan('donorlink:test-email not-an-email --mailer=array')
        ->expectsOutputToContain('Invalid email address')
        ->assertExitCode(1);
});
