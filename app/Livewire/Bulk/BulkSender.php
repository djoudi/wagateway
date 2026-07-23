<?php

namespace App\Livewire\Bulk;

use App\Jobs\ProcessBulkJob;
use App\Models\BulkJob;
use App\Models\Device;
use App\Services\RateLimitService;
use Livewire\Component;
use Livewire\WithPagination;

class BulkSender extends Component
{
    use WithPagination;

    // Form
    public string $selectedDevice = '';
    public string $messageType    = 'text';
    public string $messageBody    = '';
    public string $mediaUrl       = '';
    public string $mediaCaption   = '';
    public string $recipients     = '';
    public bool   $randomDelay    = true;
    public int    $delayMin       = 1;
    public int    $delayMax       = 3;
    public string $jobName        = '';

    // UI state
    public bool    $showConfirm    = false;
    public int     $recipientCount = 0;
    public ?array  $previewStats   = null;

    // Live progress tracking
    public ?string $activeJobUuid     = null;
    public int     $activeJobProgress = 0;
    public array   $activeJobStats    = [];
    public string  $activeJobStatus   = '';

    public function mount(): void
    {
        // Check if a job is already running for this user
        $running = BulkJob::where('user_id', auth()->id())
            ->whereIn('status', ['pending','running'])
            ->latest()
            ->first();

        if ($running) {
            $this->activeJobUuid   = $running->uuid;
            $this->activeJobStatus = $running->status;
            $this->syncProgress($running);
        }
    }

    // ── Wire poll: called every 3s when active job exists ────────────────────
    public function pollProgress(): void
    {
        if (! $this->activeJobUuid) return;

        $job = BulkJob::where('uuid', $this->activeJobUuid)
            ->where('user_id', auth()->id())
            ->first();

        if (! $job) {
            $this->activeJobUuid = null;
            return;
        }

        $this->syncProgress($job);

        if (in_array($job->status, ['completed','failed','cancelled'])) {
            $this->activeJobStatus = $job->status;
            // Keep showing final stats for 10s then clear
            $this->dispatch('notify',
                type: $job->status === 'completed' ? 'success' : 'error',
                message: match($job->status) {
                    'completed' => "Broadcast complete — {$job->sent_count} sent, {$job->failed_count} failed.",
                    'cancelled' => 'Broadcast cancelled.',
                    default     => 'Broadcast failed. Check logs.',
                }
            );
        }
    }

    private function syncProgress(BulkJob $job): void
    {
        $this->activeJobProgress = (int) $job->progressPercent();
        $this->activeJobStatus   = $job->status;
        $this->activeJobStats    = [
            'name'    => $job->name,
            'sent'    => $job->sent_count,
            'failed'  => $job->failed_count,
            'total'   => $job->total_recipients,
            'percent' => $job->progressPercent(),
        ];
    }

    // ── Recipients live count ─────────────────────────────────────────────────
    public function updatedRecipients(): void
    {
        $lines = array_filter(
            array_map('trim', explode("\n", $this->recipients)),
            fn ($l) => preg_match('/^[0-9+\s\-()]{7,20}$/', $l)
        );
        $this->recipientCount = count($lines);
    }

    // ── Preview modal ─────────────────────────────────────────────────────────
    public function preview(): void
    {
        $this->validate($this->rules());

        $user      = auth()->user();
        $limit     = $user->plan?->daily_message_limit ?? 100;
        $used      = app(RateLimitService::class)->usage($user);
        $remaining = max(0, $limit - $used);

        $this->previewStats = [
            'recipients' => $this->recipientCount,
            'remaining'  => $remaining,
            'can_send'   => min($this->recipientCount, $remaining),
            'blocked'    => max(0, $this->recipientCount - $remaining),
            'est_minutes'=> $this->randomDelay
                ? round(($this->recipientCount * (($this->delayMin + $this->delayMax) / 2)) / 60, 1)
                : 0,
        ];

        $this->showConfirm = true;
    }

    // ── Dispatch bulk job ─────────────────────────────────────────────────────
    public function send(): void
    {
        $this->validate($this->rules());

        $user   = auth()->user();
        $device = Device::where('uuid', $this->selectedDevice)
            ->where('user_id', $user->id)
            ->where('status', 'connected')
            ->firstOrFail();

        $recipients = array_values(array_filter(
            array_map('trim', explode("\n", $this->recipients)),
            fn ($l) => preg_match('/^[0-9+\s\-()]{7,20}$/', $l)
        ));

        // Clean numbers
        $recipients = array_map(
            fn ($n) => preg_replace('/[^0-9]/', '', $n),
            $recipients
        );

        $messageTemplate = array_filter([
            'type'    => $this->messageType,
            'body'    => $this->messageType === 'text' ? $this->messageBody : null,
            'url'     => $this->messageType !== 'text' ? $this->mediaUrl   : null,
            'caption' => $this->mediaCaption ?: null,
        ]);

        $job = BulkJob::create([
            'user_id'           => $user->id,
            'device_id'         => $device->id,
            'name'              => $this->jobName ?: 'Broadcast — ' . now()->format('d/m H:i'),
            'status'            => 'pending',
            'message_template'  => $messageTemplate,
            'total_recipients'  => count($recipients),
            'delay_min_seconds' => $this->randomDelay ? $this->delayMin : 0,
            'delay_max_seconds' => $this->randomDelay ? $this->delayMax : 0,
        ]);

        ProcessBulkJob::dispatch($job, $recipients)->onQueue('bulk');

        // Track in live progress widget
        $this->activeJobUuid   = $job->uuid;
        $this->activeJobStatus = 'pending';
        $this->activeJobStats  = ['name' => $job->name, 'sent' => 0, 'failed' => 0, 'total' => count($recipients), 'percent' => 0];

        $this->reset(['messageBody','recipients','mediaUrl','mediaCaption','jobName','showConfirm','previewStats','recipientCount']);
        $this->resetPage();

        $this->dispatch('notify', type: 'success',
            message: "Broadcast queued — {$job->total_recipients} messages. Sending now…");
    }

    // ── Cancel job ────────────────────────────────────────────────────────────
    public function cancelJob(string $uuid): void
    {
        BulkJob::where('uuid', $uuid)
            ->where('user_id', auth()->id())
            ->whereIn('status', ['pending','running'])
            ->update(['status' => 'cancelled']);

        $this->activeJobUuid = null;
    }

    protected function rules(): array
    {
        return [
            'selectedDevice' => 'required|string',
            'messageType'    => 'required|in:text,image,document',
            'messageBody'    => 'required_if:messageType,text|string|max:4096',
            'mediaUrl'       => 'required_if:messageType,image,document|url',
            'recipients'     => 'required|string',
            'delayMin'       => 'integer|min:0|max:30',
            'delayMax'       => 'integer|min:0|max:60',
        ];
    }

    public function render()
    {
        $devices    = Device::where('user_id', auth()->id())->where('status','connected')->get();
        $recentJobs = BulkJob::where('user_id', auth()->id())->latest()->paginate(8);

        return view('livewire.bulk.bulk-sender', compact('devices','recentJobs'));
    }
}
