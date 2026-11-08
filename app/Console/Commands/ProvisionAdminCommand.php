<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('app:provision-admin')]
#[Description('Provision the single administrative account')]
class ProvisionAdminCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = Str::lower(trim((string) config('auth.admin.email')));
        $password = (string) config('auth.admin.password');

        if ($email === '') {
            $this->error('ADMIN_EMAIL is required.');

            return self::FAILURE;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('ADMIN_EMAIL must be a valid email address.');

            return self::FAILURE;
        }

        if ($password === '') {
            $this->error('ADMIN_PASSWORD is required.');

            return self::FAILURE;
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($user !== null) {
            $this->info('Administrative account already exists.');

            return self::SUCCESS;
        }

        User::create([
            'name' => 'Librarium Administrator',
            'email' => $email,
            'password' => $password,
        ]);

        $this->info('Administrative account created.');

        return self::SUCCESS;
    }
}
