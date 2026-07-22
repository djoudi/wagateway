<?php

namespace Database\Factories;

use App\Enums\MessageStatus;
use App\Enums\MessageType;
use App\Models\Device;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'uuid'      => Str::uuid(),
            'user_id'   => User::factory(),
            'device_id' => Device::factory(),
            'to_number' => '213' . $this->faker->numerify('7########'),
            'type'      => MessageType::Text,
            'content'   => ['body' => $this->faker->sentence()],
            'status'    => MessageStatus::Sent,
            'sent_at'   => now(),
            'is_test'   => false,
        ];
    }

    public function delivered(): static
    {
        return $this->state(fn () => [
            'status'       => MessageStatus::Delivered,
            'delivered_at' => now(),
        ]);
    }

    public function read(): static
    {
        return $this->state(fn () => [
            'status'       => MessageStatus::Read,
            'delivered_at' => now()->subMinutes(2),
            'read_at'      => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status'        => MessageStatus::Failed,
            'error_message' => 'Connection timeout',
            'failed_at'     => now(),
        ]);
    }
}
