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

            $admin = User::factory()->create([
                'name'         => 'Admin',
                'email'        => 'admin@wagateway.dz',
                'password'     => bcrypt('Admin@123456'),
                'plan_id'      => $plan?->id,
            ]);

            $keys = $admin->generateApiKeys();

            $this->command->warn('─────────────────────────────────────────');
            $this->command->warn(' Dev admin created — SAVE THESE KEYS NOW:');
            $this->command->warn(" Live: {$keys['live']}");
            $this->command->warn(" Test: {$keys['test']}");
            $this->command->warn('─────────────────────────────────────────');
        }
    }
}
