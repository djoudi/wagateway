<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// Private channel: only the owning user receives their device/QR events
Broadcast::channel('user.{userId}', function (User $user, int $userId) {
    return $user->id === $userId;
});
