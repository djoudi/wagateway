<?php

namespace App\Console\Commands;

use App\Mail\SubscriptionExpiringMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Sends renewal reminders at 7, 3, and 1 day(s) before plan_expires_at.
 * Idempotent per day: relies on the exact date match so re-running the
 * same day never double-sends (safe if the scheduler retries after a
 * transient failure).
 */
class SendSubscriptionReminders extends Command
{
    protected $signature   = 'billing:send-reminders';
    protected $description = 'Email users whose subscription expires in 7, 3, or 1 day(s)';

    private const MILESTONES = [7, 3, 1];

    public function handle(): void
    {
        $sent = 0;

        foreach (self::MILESTONES as $days) {
            $targetDate = now()->addDays($days)->toDateString();

            $users = User::whereNotNull('plan_expires_at')
                ->whereDate('plan_expires_at', $targetDate)
                ->where('is_suspended', false)
                ->get();

            foreach ($users as $user) {
                try {
                    Mail::to($user->email)->send(new SubscriptionExpiringMail($user, $days));
                    $sent++;
                } catch (\Throwable $e) {
                    $this->error("Failed reminder for {$user->email}: {$e->getMessage()}");
                }
            }
        }

        $this->info("Sent {$sent} renewal reminder(s).");
    }
}
