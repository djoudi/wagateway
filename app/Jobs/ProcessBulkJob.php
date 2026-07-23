<?php

namespace App\Jobs;

use App\Models\BulkJob;
use App\Services\MessageDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProcessBulkJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue;

    public int $timeout = 3600;
    public int $tries   = 1;

    public function __construct(
        private readonly BulkJob $bulkJob,
        private readonly array   $recipients,
    ) {}

    public function handle(MessageDispatcher $dispatcher): void
    {
        // ── Security: verify ownership at execution time ──────────────────────
        $this->bulkJob->refresh();

        if ($this->bulkJob->device->user_id !== $this->bulkJob->user_id) {
            Log::error("[BulkJob {$this->bulkJob->uuid}] Device ownership mismatch — aborting");
            $this->bulkJob->update(['status' => 'failed']);
            return;
        }

        // ── Check job not cancelled before starting ───────────────────────────
        if ($this->bulkJob->status === 'cancelled') {
            return;
        }

        $this->bulkJob->update(['status' => 'running', 'started_at' => now()]);

        $device = $this->bulkJob->device;
        $user   = $this->bulkJob->user;
        $tpl    = $this->bulkJob->message_template;

        foreach ($this->recipients as $number) {
            // Poll for cancellation every message
            $this->bulkJob->refresh();
            if ($this->bulkJob->status === 'cancelled') {
                Log::info("[BulkJob {$this->bulkJob->uuid}] Cancelled at {$this->bulkJob->sent_count} msgs");
                return;
            }

            // Skip empty / invalid numbers
            $clean = preg_replace('/[^0-9]/', '', $number);
            if (strlen($clean) < 7 || strlen($clean) > 15) {
                $this->bulkJob->increment('failed_count');
                continue;
            }

            try {
                $dispatcher->send($user, $device, array_merge($tpl, ['to' => $clean]));
                $this->bulkJob->increment('sent_count');
            } catch (\Exception $e) {
                $this->bulkJob->increment('failed_count');
                Log::warning("[BulkJob {$this->bulkJob->uuid}] Failed →{$clean}: {$e->getMessage()}");
            }

            // Randomised human-like delay
            $min = (int) ($this->bulkJob->delay_min_seconds * 1_000_000);
            $max = (int) ($this->bulkJob->delay_max_seconds * 1_000_000);
            if ($max > $min) {
                usleep(rand($min, $max));
            }
        }

        $this->bulkJob->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function failed(\Throwable $e): void
    {
        $this->bulkJob->update(['status' => 'failed']);
        Log::error("[BulkJob {$this->bulkJob->uuid}] Job failed: {$e->getMessage()}");
    }
}
