<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Webhook;

class WebhookPolicy
{
    public function view(User $user, Webhook $webhook): bool   { return $user->id === $webhook->user_id; }
    public function update(User $user, Webhook $webhook): bool { return $user->id === $webhook->user_id; }
    public function delete(User $user, Webhook $webhook): bool { return $user->id === $webhook->user_id; }
}
