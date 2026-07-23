<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Request;

class SecurityEvent extends Model
{
    protected $fillable = ['user_id','event','ip_address','user_agent','context'];
    protected $casts    = ['context' => 'array'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    /**
     * Log a security-relevant event. Fire-and-forget — never blocks the request.
     */
    public static function log(string $event, ?int $userId = null, array $context = []): void
    {
        try {
            static::create([
                'user_id'    => $userId,
                'event'      => $event,
                'ip_address' => Request::ip(),
                'user_agent' => substr((string) Request::userAgent(), 0, 255),
                'context'    => $context,
            ]);
        } catch (\Throwable $e) {
            // Audit logging must never break the request flow
            \Illuminate\Support\Facades\Log::error("SecurityEvent log failed: {$e->getMessage()}");
        }
    }
}
