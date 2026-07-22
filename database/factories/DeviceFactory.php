<?php

namespace Database\Factories;

use App\Enums\DeviceStatus;
use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition(): array
    {
        return [
            'uuid'                => Str::uuid(),
            'user_id'             => User::factory(),
            'name'                => $this->faker->randomElement(['Marketing', 'Support', 'Sales', 'Backup']),
            'phone_number'        => '213' . $this->faker->numerify('7########'),
            'status'              => DeviceStatus::Disconnected,
            'messages_sent_today' => $this->faker->numberBetween(0, 500),
            'messages_sent_total' => $this->faker->numberBetween(0, 50000),
            'is_active'           => true,
        ];
    }

    public function connected(): static
    {
        return $this->state(fn () => [
            'status'       => DeviceStatus::Connected,
            'connected_at' => now()->subHours(2),
            'last_seen_at' => now(),
        ]);
    }

    public function disconnected(): static
    {
        return $this->state(fn () => ['status' => DeviceStatus::Disconnected]);
    }

    public function banned(): static
    {
        return $this->state(fn () => ['status' => DeviceStatus::Banned]);
    }
}
