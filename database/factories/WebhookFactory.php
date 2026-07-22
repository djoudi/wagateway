<?php

namespace Database\Factories;

use App\Enums\WebhookEvent;
use App\Models\User;
use App\Models\Webhook;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WebhookFactory extends Factory
{
    protected $model = Webhook::class;

    public function definition(): array
    {
        return [
            'uuid'      => Str::uuid(),
            'user_id'   => User::factory(),
            'name'      => $this->faker->words(2, true) . ' hook',
            'url'       => 'https://' . $this->faker->domainName() . '/webhook/wa',
            'secret'    => Str::random(40),
            'events'    => [WebhookEvent::MessageReceived->value, WebhookEvent::DeviceConnected->value],
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
