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
 * The password is always typed interactively — never passed as an argument,
 * so it does not end up in shell history or process lists.
 */
class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin {--name= : Display name} {--email= : Login email}';

    protected $description = 'Create the single admin account (interactive password prompt)';

    public function handle(): int
    {
        if (User::query()->exists()) {
            $this->error('An admin account already exists. v1 supports a single account; use "forgot password" to recover access.');

            return self::FAILURE;
        }

        $name = $this->option('name') ?: $this->ask('Name');
        $email = mb_strtolower(trim((string) ($this->option('email') ?: $this->ask('Email'))));
        $password = (string) $this->secret('Password (min 12 characters)');
        $confirmation = (string) $this->secret('Repeat password');

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

        ActivityLog::create(['user_id' => $user->id, 'event' => 'auth.admin_created', 'properties' => ['via' => 'cli']]);

        $this->info("Admin account created for {$email}. Sign in at /".config('admin.path').'/login and enable two-factor authentication.');

        return self::SUCCESS;
    }
}
