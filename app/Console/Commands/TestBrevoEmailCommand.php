<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TestBrevoEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'donorlink:test-email
                            {recipient? : Recipient email address to send the test message to}
                            {--mailer= : Specific mailer to test (defaults to configured default mailer)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email delivery and verify Brevo SMTP credentials and connectivity';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('======================================================================');
        $this->info(' DonorLink UG — Email & Brevo SMTP Diagnostic Tester                  ');
        $this->info('======================================================================');

        $mailerName = (string) ($this->option('mailer') ?: config('mail.default'));
        $mailerConfig = config("mail.mailers.{$mailerName}", []);

        $host = $mailerConfig['host'] ?? 'N/A';
        $port = $mailerConfig['port'] ?? 'N/A';
        $scheme = $mailerConfig['scheme'] ?? 'default';
        $username = $mailerConfig['username'] ?? null;
        $fromAddress = config('mail.from.address');
        $fromName = config('mail.from.name');

        $this->table(
            ['Setting', 'Configured Value'],
            [
                ['Active Mailer', $mailerName],
                ['SMTP Host', (string) $host],
                ['SMTP Port', (string) $port],
                ['Encryption / Scheme', (string) $scheme],
                ['Username / Key ID', $username ? (substr((string) $username, 0, 3).'***'.substr((string) $username, -3)) : '<not configured>'],
                ['From Address', (string) $fromAddress],
                ['From Name', (string) $fromName],
            ]
        );

        if ($mailerName === 'smtp' && ($host === '127.0.0.1' || $host === 'localhost')) {
            $this->warn('Warning: Host is set to local loopback (127.0.0.1). If using Brevo, set MAIL_HOST=smtp-relay.brevo.com in your .env');
        }

        if ($mailerName === 'smtp' && empty($username)) {
            $this->error('Error: MAIL_USERNAME is empty in .env. Brevo SMTP requires your Brevo account login or SMTP login.');
        }

        $recipient = $this->argument('recipient');
        if (! $recipient) {
            $recipient = $this->ask('Enter destination email address to receive test message');
        }

        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->error("Invalid email address: '{$recipient}'");

            return self::FAILURE;
        }

        $this->newLine();
        $this->info("Sending diagnostic test email to {$recipient} via [{$mailerName}]...");

        try {
            $subject = 'DonorLink UG — Brevo Delivery Test with Official Logo ('.now('Africa/Kampala')->format('H:i:s').')';

            Mail::mailer($mailerName)->send(
                'emails.test-email',
                [
                    'subject' => $subject,
                    'heading' => 'Email System Diagnostic Test',
                    'mailerName' => $mailerName,
                    'host' => $host,
                    'port' => $port,
                    'fromAddress' => $fromAddress,
                    'recipient' => $recipient,
                    'timestamp' => now('Africa/Kampala')->format('d M Y, H:i:s'),
                    'actionText' => 'Visit DonorLink UG',
                    'actionUrl' => config('app.url'),
                ],
                function ($message) use ($recipient, $fromAddress, $fromName, $subject): void {
                    $message->to((string) $recipient)
                        ->from((string) $fromAddress, (string) $fromName)
                        ->subject($subject);
                }
            );

            $this->info("✓ Success! Branded test email with logo was successfully accepted by [{$mailerName}] for delivery to {$recipient}.");
            $this->comment('Check your inbox (and spam/junk folder) for the test message with the official logo.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->newLine();
            $this->error('✗ Email dispatch failed: '.$e->getMessage());

            $this->newLine();
            $this->warn('--- Diagnostic Troubleshooting Tips ---');

            $message = strtolower($e->getMessage());

            if (str_contains($message, '535') || str_contains($message, 'authentication') || str_contains($message, 'credentials')) {
                $this->line('1. [Brevo Auth Error]: Make sure you are using an SMTP Key generated under Brevo Dashboard > SMTP & API > SMTP Keys, NOT your personal account login password.');
                $this->line('2. Verify that MAIL_USERNAME exactly matches the SMTP login provided by Brevo.');
            } elseif (str_contains($message, 'connection') || str_contains($message, 'timeout') || str_contains($message, 'refused')) {
                $this->line('1. [Connection / Firewall]: Outbound connections on port 587 may be blocked by your network/ISP.');
                $this->line('2. Try alternative ports: set MAIL_PORT=465 and MAIL_SCHEME=smtps in .env, or try port 2525.');
            } elseif (str_contains($message, 'sender') || str_contains($message, '550') || str_contains($message, 'not allowed')) {
                $this->line('1. [Unverified Sender]: Brevo requires MAIL_FROM_ADDRESS to be a verified sender or under a verified domain.');
                $this->line('2. Check Brevo Dashboard > Senders & IPs > Senders to add or verify '.$fromAddress.'.');
            } else {
                $this->line('1. Check Brevo dashboard logs for real-time reject or bounce notices.');
                $this->line('2. Verify DNS SPF and DKIM records for your sender domain.');
            }

            return self::FAILURE;
        }
    }
}
