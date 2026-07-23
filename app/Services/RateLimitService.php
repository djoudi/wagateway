<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Redis;

class RateLimitService
{
    private const TTL = 86400; // 24 hours

    /**
     * Check if user can send a message.
     * Returns [allowed: bool, remaining: int, limit: int]
     */
    public function check(User $user): array
    {
        $limit   = $user->plan->daily_message_limit ?? 100;
        $key     = $this->dailyKey($user->id);
        $current = (int) Redis::get($key) ?: 0;
        $allowed = $current < $limit;

        return [
            'allowed'   => $allowed,
            'remaining' => max(0, $limit - $current),
            'limit'     => $limit,
            'used'      => $current,
        ];
    }

    /**
     * Increment usage counter after a message is sent.
     */
    public function increment(User $user): void
    {
        $key = $this->dailyKey($user->id);
        $new = Redis::incr($key);

        // Set TTL only on first write of the day
        if ($new === 1) {
            Redis::expireat($key, $this->tomorrowMidnight());
        }
    }

    /**
     * Get current daily usage without modifying it.
     */
    public function usage(User $user): int
    {
        return (int) Redis::get($this->dailyKey($user->id)) ?: 0;
    }

    private function dailyKey(int $userId): string
    {
        return sprintf('ratelimit:msg:%d:%s', $userId, now()->toDateString());
    }

    private function tomorrowMidnight(): int
    {
        return now()->addDay()->startOfDay()->timestamp;
    }
}
