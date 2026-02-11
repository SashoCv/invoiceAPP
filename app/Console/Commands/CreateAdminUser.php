<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create {--email=} {--name=} {--password=}';

    protected $description = 'Create a new admin user or promote an existing user to admin';

    public function handle(): int
    {
        $email = $this->option('email') ?? $this->ask('Email address');
        $user = User::where('email', $email)->first();

        if ($user) {
            $user->update(['role' => 'admin']);
            $this->info("User {$email} has been promoted to admin.");

            return self::SUCCESS;
        }

        $name = $this->option('name') ?? $this->ask('Name');
        $password = $this->option('password') ?? $this->secret('Password');

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->info("Admin user {$email} created successfully.");

        return self::SUCCESS;
    }
}
