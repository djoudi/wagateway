<?php

namespace App\Console\Commands;

use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SecurityAuditReport extends Command
{
    protected $signature   = 'security:audit-check';
    protected $description = 'Scan security_events for suspicious patterns and flag accounts';

    public function handle(): void
    {
        // ── Pattern 1: >10 failed logins in the last hour → flag ──────────────
        $suspiciousEmails = SecurityEvent::where('event', 'login_failed')
            ->where('created_at', '>=', now()->subHour())
            ->get()
            ->groupBy(fn ($e) => $e->context['email'] ?? 'unknown')
            ->filter(fn ($group) => $group->count() >= 10);

        foreach ($suspiciousEmails as $email => $events) {
            Log::warning("[Security] Possible credential stuffing: {$email} ({$events->count()} failed attempts/hour)");
        }

        // ── Pattern 2: >50 invalid API key attempts from one IP → flag ────────
        $suspiciousIps = SecurityEvent::where('event', 'api_key_invalid')
            ->where('created_at', '>=', now()->subHour())
            ->get()
            ->groupBy('ip_address')
            ->filter(fn ($group) => $group->count() >= 50);

        foreach ($suspiciousIps as $ip => $events) {
            Log::warning("[Security] Possible API key enumeration from IP: {$ip} ({$events->count()} attempts/hour)");
        }

        $this->info("Audit check complete. Flagged: " . ($suspiciousEmails->count() + $suspiciousIps->count()));
    }
}
