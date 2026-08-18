<?php

namespace App\Models;

use App\Enums\MessageStatus;
use App\Enums\MessageType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Message extends Model
{
    use HasFactory;
    protected $fillable = [
        'uuid','user_id','device_id','to_number','type','content',
        'status','wa_message_id','error_message','retry_count',
        'sent_at','delivered_at','read_at','failed_at','bulk_job_id','is_test',
    ];

    protected $casts = [
        'type'         => MessageType::class,
        'status'       => MessageStatus::class,
        'content'      => 'array',
        'sent_at'      => 'datetime',
        'delivered_at' => 'datetime',
        'read_at'      => 'datetime',
        'failed_at'    => 'datetime',
        'is_test'      => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(fn (Message $m) => $m->uuid ??= Str::uuid());
    }

    public function user(): BelongsTo   { return $this->belongsTo(User::class); }
    public function device(): BelongsTo { return $this->belongsTo(Device::class); }
    public function bulkJob(): BelongsTo{ return $this->belongsTo(BulkJob::class); }

    public function markSent(string $waId): void
    {
        $this->update(['status' => MessageStatus::Sent, 'wa_message_id' => $waId, 'sent_at' => now()]);
    }

    public function markFailed(string $reason): void
    {
        $this->update(['status' => MessageStatus::Failed, 'error_message' => $reason, 'failed_at' => now()]);
    }

    public function getRouteKeyName(): string { return 'uuid'; }
}
