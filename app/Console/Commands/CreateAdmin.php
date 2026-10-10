<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * The ONLY way to create the admin account (there is no public registration).
 * v1 is single-user, so the command refuses to run when an account exists.
 * The password is never passed as an argument (shell history / process
 * lists). It is typed interactively, or — for hosts without SSH, where the
 * command is run once from a DirectAdmin cron entry — generated with
 * --generate-password and written to a private file (never to the output,
 * which cron may e-mail). Delete that file after the first sign-in.
 */
class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin {--name= : Display name} {--email= : Login email} {--generate-password : Non-interactive: generate a password into storage/app/private (for cron-only hosts)}';

    public const PASSWORD_FILE = 'app/private/initial-admin-password.txt';

    protected $description = 'Create the single admin account (interactive password prompt)';

    public function handle(): int
    {
        if (User::query()->exists()) {
            $this->error('An admin account already exists. v1 supports a single account; use "forgot password" to recover access.');

            return self::FAILURE;
        }

        $generate = (bool) $this->option('generate-password');
        if ($generate && (! $this->option('name') || ! $this->option('email'))) {
            $this->error('--generate-password needs --name and --email.');

            return self::FAILURE;
        }

        $name = $this->option('name') ?: $this->ask('Name');
        $email = mb_strtolower(trim((string) ($this->option('email') ?: $this->ask('Email'))));
        if ($generate) {
            // 24 chars from an unambiguous alphabet ≈ 137 bits.
            $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
            $password = '';
            for ($i = 0; $i < 24; $i++) {
                $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $confirmation = $password;
        } else {
            $password = (string) $this->secret('Password (min 12 characters)');
            $confirmation = (string) $this->secret('Repeat password');
        }

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password, 'password_confirmation' => $confirmation],
            [
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'string', 'email', 'max:191'],
                'password' => ['required', 'string', Password::default(), 'confirmed'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = User::query()->forceCreate([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        ActivityLog::create(['user_id' => $user->id, 'event' => 'auth.admin_created', 'properties' => ['via' => $generate ? 'cli-generated' : 'cli']]);

        if ($generate) {
            $file = storage_path(self::PASSWORD_FILE);
            @mkdir(dirname($file), 0700, true);
            file_put_contents($file, "Email: {$email}\nPassword: {$password}\n\nDelete this file after signing in, then change the password and enable two-factor authentication.\n");
            @chmod($file, 0600);
            $this->warn('Password written to storage/'.self::PASSWORD_FILE.' — read it, DELETE the file, then change the password after signing in.');
        }

        $this->info("Admin account created for {$email}. Sign in at /".config('admin.path').'/login and enable two-factor authentication.');

        return self::SUCCESS;
    }
}
