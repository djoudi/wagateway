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

            $admin = User::query()->firstOrNew(['email' => $adminEmail]);
            $admin->name = $admin->name ?: 'Admin';
            $admin->plan_id = $admin->plan_id ?: $plan?->id;
            $admin->is_admin = true;
            if (! $admin->exists) {
                $admin->password = 'Admin@123456';
            }
            $admin->save();

            if (! $admin->api_key_hash) {
                $keys = $admin->generateApiKeys();
                $this->command->warn('─────────────────────────────────────────');
                $this->command->warn(' Dev admin created — SAVE THESE KEYS NOW:');
                $this->command->warn(" Email: {$admin->email}");
                $this->command->warn(' Password: Admin@123456');
                $this->command->warn(" Live: {$keys['live']}");
                $this->command->warn(" Test: {$keys['test']}");
                $this->command->warn(' Sign in at /admin');
                $this->command->warn('─────────────────────────────────────────');
            }
        }
    }
}
