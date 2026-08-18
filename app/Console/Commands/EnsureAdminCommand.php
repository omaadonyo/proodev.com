<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class EnsureAdminCommand extends Command
{
    protected $signature = 'os:ensure-admin
        {--email=adonyo@proodev.com : Email address of the platform admin account}
        {--password= : Password to set (defaults to the platform admin credential)}';

    protected $description = 'Create or update the platform admin account (adonyo@proodev.com) and reset its password';

    public function handle(): int
    {
        $email = $this->option('email');
        $password = $this->option('password') ?: 'O+256M777007531A';

        $admin = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'ProoDev Admin',
                'username' => 'proodev-admin',
                'password' => Hash::make($password),
            ],
        );

        $admin->forceFill([
            'name' => $admin->name ?: 'ProoDev Admin',
            'username' => $admin->username ?: 'proodev-admin',
            'password' => Hash::make($password),
            'email_verified_at' => now(),
            'is_admin' => true,
        ])->save();

        // Keep a single, stable platform admin — legacy admins are demoted so the
        // admin account is never duplicated or visible as a regular user.
        $demoted = User::where('is_admin', true)->where('id', '!=', $admin->id)->update(['is_admin' => false]);

        $this->info(($admin->wasRecentlyCreated ? 'Created' : 'Updated')." platform admin: {$admin->email}");
        $this->info('Admin password reset.');

        if ($demoted > 0) {
            $this->warn("Demoted {$demoted} legacy admin account(s) so the platform keeps a single admin.");
        }

        return self::SUCCESS;
    }
}
