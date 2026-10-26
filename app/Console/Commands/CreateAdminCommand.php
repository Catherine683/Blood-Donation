<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'donorlink:create-admin
                            {--force : Allow creating an admin account even if active administrators already exist}
                            {--invite : Provision admin with no usable password and output a 72-hour signed set-password link}
                            {--name= : Full name of the administrator}
                            {--email= : Email address of the administrator}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bootstrap the initial UBTS administrator account interactively or via invitation link';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('====================================================');
        $this->info(' DonorLink UG — Initial Administrator Bootstrap     ');
        $this->info('====================================================');

        $activeAdminCount = User::query()
            ->where('role', Role::Admin)
            ->where('is_active', true)
            ->count();

        if ($activeAdminCount > 0 && ! $this->option('force')) {
            $this->error("Refused: An active administrator account already exists ({$activeAdminCount} active admin(s) found).");
            $this->warn('Use the --force option if you explicitly wish to provision an additional administrator via console.');

            return self::FAILURE;
        }

        // 1. Full Name
        $name = trim((string) ($this->option('name') ?: $this->ask('Full Name')));
        while ($name === '') {
            $this->error('Full name is required.');
            $name = trim((string) $this->ask('Full Name'));
        }

        // 2. Email Address
        $email = strtolower(trim((string) ($this->option('email') ?: $this->ask('Email address'))));
        $emailValidator = Validator::make(['email' => $email], [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
        ]);

        while ($emailValidator->fails()) {
            $this->error($emailValidator->errors()->first('email'));
            $email = strtolower(trim((string) $this->ask('Email address')));
            $emailValidator = Validator::make(['email' => $email], [
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            ]);
        }

        // 3. Handle --invite mode
        if ($this->option('invite')) {
            // Create user with unusable random password hash and unverified email
            $admin = User::forceCreate([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(60)),
                'role' => Role::Admin,
                'is_active' => true,
                'notify_new_drives' => false,
                'consent_given_at' => now(),
            ]);

            // Generate one-time 72-hour signed set-password invitation link
            $invitationUrl = URL::temporarySignedRoute(
                'staff.invitation.accept',
                now()->addHours(72),
                ['user' => $admin->id]
            );

            AuditLogger::record(null, 'admin.bootstrapped.invited', $admin, [
                'user_id' => $admin->id,
                'email' => $admin->email,
            ]);

            $this->newLine();
            $this->info("Administrator account created for {$name} ({$email}).");
            $this->info('One-time signed invitation link (valid for 72 hours):');
            $this->line("<fg=cyan;options=bold>{$invitationUrl}</>");
            $this->warn('Use this link to set your password and activate the administrator account.');

            return self::SUCCESS;
        }

        // 4. Interactive Password Creation
        $password = (string) $this->secret('Password (min 8 characters)');
        while (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters long.');
            $password = (string) $this->secret('Password (min 8 characters)');
        }

        $passwordConfirmation = (string) $this->secret('Confirm Password');
        while ($password !== $passwordConfirmation) {
            $this->error('Passwords do not match. Please re-enter.');
            $password = (string) $this->secret('Password (min 8 characters)');
            while (strlen($password) < 8) {
                $this->error('Password must be at least 8 characters long.');
                $password = (string) $this->secret('Password (min 8 characters)');
            }
            $passwordConfirmation = (string) $this->secret('Confirm Password');
        }

        // 5. Create verified active Administrator account
        $admin = User::forceCreate([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => Role::Admin,
            'is_active' => true,
            'notify_new_drives' => false,
            'consent_given_at' => now(),
        ]);

        $admin->email_verified_at = now();
        $admin->save();

        AuditLogger::record(null, 'admin.bootstrapped', $admin, [
            'user_id' => $admin->id,
            'email' => $admin->email,
        ]);

        $this->newLine();
        $this->info("Administrator account created successfully for {$name} ({$email}).");
        $this->info('Email marked as verified. You can now sign in at /login.');

        return self::SUCCESS;
    }
}
