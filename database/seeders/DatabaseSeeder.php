<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PlanSeeder::class);

        if (app()->environment(['local', 'staging'])) {
            $plan = Plan::where('slug', 'pro')->first();
            $adminEmail = config('wagateway.admin_emails')[0] ?? 'admin@wagateway.dz';

            $admin = User::query()->firstOrCreate(
                ['email' => $adminEmail],
                [
                    'name'     => 'Admin',
                    'password' => bcrypt('Admin@123456'),
                    'plan_id'  => $plan?->id,
                ],
            );

            if (! $admin->api_key_hash) {
                $keys = $admin->generateApiKeys();
                $this->command->warn('─────────────────────────────────────────');
                $this->command->warn(' Dev admin created — SAVE THESE KEYS NOW:');
                $this->command->warn(" Email: {$admin->email}");
                $this->command->warn(' Password: Admin@123456');
                $this->command->warn(" Live: {$keys['live']}");
                $this->command->warn(" Test: {$keys['test']}");
                $this->command->warn(' Set ADMIN_EMAILS to this address to open /admin.');
                $this->command->warn('─────────────────────────────────────────');
            }
        }
    }
}
