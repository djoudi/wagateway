<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name'                => 'Starter',
                'slug'                => 'starter',
                'daily_message_limit' => 1000,
                'max_devices'         => 2,
                'max_webhooks'        => 3,
                'max_templates'       => 10,
                'bulk_batch_limit'    => 100,
                'price_monthly'       => 500.00,
                'price_yearly'        => 4800.00,
                'features'            => ['webhooks','templates'],
                'sort_order'          => 1,
            ],
            [
                'name'                => 'Pro',
                'slug'                => 'pro',
                'daily_message_limit' => 10000,
                'max_devices'         => 10,
                'max_webhooks'        => 10,
                'max_templates'       => 50,
                'bulk_batch_limit'    => 1000,
                'price_monthly'       => 1500.00,
                'price_yearly'        => 14400.00,
                'features'            => ['webhooks','templates','bulk_send','scheduling'],
                'sort_order'          => 2,
            ],
            [
                'name'                => 'Business',
                'slug'                => 'business',
                'daily_message_limit' => 100000,
                'max_devices'         => 30,
                'max_webhooks'        => 30,
                'max_templates'       => 200,
                'bulk_batch_limit'    => 10000,
                'price_monthly'       => 2500.00,
                'price_yearly'        => 24000.00,
                'features'            => ['webhooks','templates','bulk_send','scheduling','priority_support'],
                'sort_order'          => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
