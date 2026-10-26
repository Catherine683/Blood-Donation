<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class PreflightCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'donorlink:preflight';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify production readiness and configuration safety before deploy or handover';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('======================================================================');
        $this->info(' DonorLink UG — Production Readiness Preflight Checks                ');
        $this->info('======================================================================');

        $failCount = 0;
        $warnCount = 0;
        $passCount = 0;

        // 1. APP_ENV is production
        $appEnv = (string) config('app.env');
        if ($appEnv !== 'production') {
            $this->printCheck('FAIL', 'APP_ENV', "Environment is '{$appEnv}', expected 'production'.");
            $failCount++;
        } else {
            $this->printCheck('PASS', 'APP_ENV', 'Environment is configured for production.');
            $passCount++;
        }

        // 2. APP_DEBUG is false
        $appDebug = (bool) config('app.debug');
        if ($appDebug) {
            $this->printCheck('FAIL', 'APP_DEBUG', 'Debug mode is enabled. Must be false in production.');
            $failCount++;
        } else {
            $this->printCheck('PASS', 'APP_DEBUG', 'Debug mode is disabled.');
            $passCount++;
        }

        // 3. APP_KEY is present
        $appKey = (string) config('app.key');
        if (empty($appKey)) {
            $this->printCheck('FAIL', 'APP_KEY', 'Application key is missing.');
            $failCount++;
        } else {
            $this->printCheck('PASS', 'APP_KEY', 'Application encryption key is present.');
            $passCount++;
        }

        // 4. APP_URL is https and not localhost
        $appUrl = (string) config('app.url');
        if (! str_starts_with($appUrl, 'https://') || str_contains($appUrl, 'localhost') || str_contains($appUrl, '127.0.0.1')) {
            $this->printCheck('FAIL', 'APP_URL', "APP_URL ('{$appUrl}') must use https and not point to localhost.");
            $failCount++;
        } else {
            $this->printCheck('PASS', 'APP_URL', "Valid production URL configured ({$appUrl}).");
            $passCount++;
        }

        // 5. Mailer is not log, array, or null
        $mailer = (string) config('mail.default');
        if (in_array($mailer, ['log', 'array', 'null'], true)) {
            $this->printCheck('FAIL', 'MAIL_MAILER', "Mailer is set to '{$mailer}'. Real email delivery required.");
            $failCount++;
        } else {
            $this->printCheck('PASS', 'MAIL_MAILER', "Production mailer configured ('{$mailer}').");
            $passCount++;
        }

        // 6. MAIL_FROM_ADDRESS is valid and not framework default
        $mailFrom = (string) config('mail.from.address');
        if (empty($mailFrom) || $mailFrom === 'hello@example.com') {
            $this->printCheck('FAIL', 'MAIL_FROM_ADDRESS', "MAIL_FROM_ADDRESS ('{$mailFrom}') is missing or set to framework default.");
            $failCount++;
        } else {
            $this->printCheck('PASS', 'MAIL_FROM_ADDRESS', "Valid sender address configured ({$mailFrom}).");
            $passCount++;
        }

        // 7. Queue connection is not sync
        $queueConn = (string) config('queue.default');
        if ($queueConn === 'sync') {
            $this->printCheck('FAIL', 'QUEUE_CONNECTION', "Queue connection is 'sync'. Background queue worker required.");
            $failCount++;
        } else {
            $this->printCheck('PASS', 'QUEUE_CONNECTION', "Asynchronous queue configured ('{$queueConn}').");
            $passCount++;
        }

        // 8. Database is not SQLite
        $defaultDb = (string) config('database.default');
        $dbDriver = (string) config("database.connections.{$defaultDb}.driver", $defaultDb);
        if ($defaultDb === 'sqlite' || $dbDriver === 'sqlite') {
            $this->printCheck('FAIL', 'DATABASE', "Database driver is SQLite ('{$defaultDb}'). MySQL/PostgreSQL required for production.");
            $failCount++;
        } else {
            $this->printCheck('PASS', 'DATABASE', "Production database configured ('{$dbDriver}').");
            $passCount++;
        }

        // 9. Session and cache driver are not file or array
        $sessionDriver = (string) config('session.driver');
        $cacheDriver = (string) config('cache.default');
        if (in_array($sessionDriver, ['file', 'array'], true) || in_array($cacheDriver, ['file', 'array'], true)) {
            $this->printCheck('FAIL', 'SESSION/CACHE', "Session ('{$sessionDriver}') and cache ('{$cacheDriver}') must not use file or array driver on Laravel Cloud.");
            $failCount++;
        } else {
            $this->printCheck('PASS', 'SESSION/CACHE', "Distributed session ('{$sessionDriver}') and cache ('{$cacheDriver}') configured.");
            $passCount++;
        }

        // 10. SESSION_SECURE_COOKIE is true
        $sessionSecure = (bool) config('session.secure');
        if (! $sessionSecure) {
            $this->printCheck('FAIL', 'SESSION_SECURE_COOKIE', 'SESSION_SECURE_COOKIE must be explicitly set to true in production.');
            $failCount++;
        } else {
            $this->printCheck('PASS', 'SESSION_SECURE_COOKIE', 'Session cookies are restricted to HTTPS connections.');
            $passCount++;
        }

        // 11. No pending migrations exist
        $pendingMigrations = $this->getPendingMigrationsCount();
        if ($pendingMigrations > 0) {
            $this->printCheck('FAIL', 'MIGRATIONS', "{$pendingMigrations} pending database migration(s) exist. Run php artisan migrate.");
            $failCount++;
        } else {
            $this->printCheck('PASS', 'MIGRATIONS', 'Database schema is fully up to date.');
            $passCount++;
        }

        // 12. At least one active administrator exists
        try {
            $hasActiveAdmin = User::query()->where('role', Role::Admin)->where('is_active', true)->exists();
        } catch (\Throwable) {
            $hasActiveAdmin = false;
        }

        if (! $hasActiveAdmin) {
            $this->printCheck('FAIL', 'ADMIN_ACCOUNT', 'No active administrator account exists. Run php artisan donorlink:create-admin --invite.');
            $failCount++;
        } else {
            $this->printCheck('PASS', 'ADMIN_ACCOUNT', 'At least one active administrator account is provisioned.');
            $passCount++;
        }

        // 13. Daily email budget is strictly greater than transactional reserve
        $dailyBudget = (int) config('donorlink.mail.daily_budget', 250);
        $reserve = (int) config('donorlink.mail.transactional_reserve', 100);
        if ($dailyBudget <= $reserve) {
            $this->printCheck('FAIL', 'MAIL_BUDGET', "Daily email budget ({$dailyBudget}) must be strictly greater than transactional reserve ({$reserve}).");
            $failCount++;
        } else {
            $this->printCheck('PASS', 'MAIL_BUDGET', "Mail budget configured ({$dailyBudget} daily, {$reserve} transactional reserve).");
            $passCount++;
        }

        // ---------------------------------------------------------------------
        // Advisory WARNINGS
        // ---------------------------------------------------------------------

        // WARN 1: Configuration is not cached
        if (! app()->configurationIsCached()) {
            $this->printCheck('WARN', 'CONFIG_CACHE', 'Configuration is not cached. Run php artisan config:cache.');
            $warnCount++;
        } else {
            $this->printCheck('PASS', 'CONFIG_CACHE', 'Configuration is cached.');
            $passCount++;
        }

        // WARN 2: Privacy contact details not set
        $privacyEmail = config('donorlink.privacy.contact_email');
        $privacyAddress = config('donorlink.privacy.contact_address');
        if (empty($privacyEmail) || empty($privacyAddress)) {
            $this->printCheck('WARN', 'PRIVACY_CONFIG', 'Privacy contact email or physical address is not set in environment.');
            $warnCount++;
        } else {
            $this->printCheck('PASS', 'PRIVACY_CONFIG', 'Privacy contact information is configured.');
            $passCount++;
        }

        // WARN 3: No scheduler heartbeat seen yet
        if (! Cache::has('donorlink:heartbeat:scheduler')) {
            $this->printCheck('WARN', 'SCHEDULER_HEARTBEAT', 'No scheduler heartbeat has been recorded yet. Ensure scheduler is enabled in cloud settings.');
            $warnCount++;
        } else {
            $this->printCheck('PASS', 'SCHEDULER_HEARTBEAT', 'Scheduler heartbeat is present.');
            $passCount++;
        }

        // WARN 4: CSP is still report-only
        if (! (bool) config('donorlink.csp_enforced', false)) {
            $this->printCheck('WARN', 'CSP_ENFORCEMENT', 'Content-Security-Policy is currently in report-only mode.');
            $warnCount++;
        } else {
            $this->printCheck('PASS', 'CSP_ENFORCEMENT', 'Content-Security-Policy is enforced.');
            $passCount++;
        }

        // Summary
        $this->newLine();
        $this->info("Preflight Summary: {$passCount} Passed, {$warnCount} Warning(s), {$failCount} Failure(s).");

        if ($failCount > 0) {
            $this->error("Preflight check FAILED with {$failCount} blocking issue(s). Deployment aborted.");

            return self::FAILURE;
        }

        $this->info('Preflight check PASSED successfully. Environment is ready for production.');

        return self::SUCCESS;
    }

    /**
     * Print a formatted check result line.
     */
    protected function printCheck(string $status, string $check, string $message): void
    {
        $tag = match ($status) {
            'PASS' => '<fg=green;options=bold>[PASS]</>',
            'WARN' => '<fg=yellow;options=bold>[WARN]</>',
            default => '<fg=red;options=bold>[FAIL]</>',
        };

        $checkCol = str_pad($check, 24);
        $this->line(" {$tag} {$checkCol} {$message}");
    }

    /**
     * Count pending database migrations.
     */
    protected function getPendingMigrationsCount(): int
    {
        try {
            $migrator = app('migrator');
            $files = $migrator->getMigrationFiles($migrator->paths());
            $ran = $migrator->getRepository()->getRan();

            return count(array_diff(array_keys($files), $ran));
        } catch (\Throwable) {
            return 1;
        }
    }
}
