<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MakeAdminUser extends Command
{
    protected $signature = 'user:make-admin
                            {email : Email that must also be listed in ADMIN_EMAILS}
                            {--name=Admin : Display name}
                            {--password= : Password (random if omitted)}';

    protected $description = 'Create or update a user that can sign in to /admin';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $name = (string) $this->option('name');
        $password = $this->option('password') ?: Str::password(16);
        $generated = ! $this->option('password');

        $user = User::query()->firstOrNew(['email' => $email]);
        $user->name = $name !== '' ? $name : ($user->name ?: 'Admin');
        $user->password = Hash::make($password);
        $user->email_verified_at ??= now();
        $user->save();

        if (! $user->api_key_hash) {
            $user->generateApiKeys();
        }

        $listed = $user->isAdmin();

        $this->info("User ready: {$user->email}");
        if ($generated) {
            $this->warn("Password: {$password}");
        }
        $this->warn('Open Filament at /admin (not /login).');

        if (! $listed) {
            $this->error("{$user->email} is not in ADMIN_EMAILS. Add it, then run: php artisan config:cache");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
