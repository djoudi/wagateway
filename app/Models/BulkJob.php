<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BulkJob extends Model
{
    protected $fillable = [
        'uuid','user_id','device_id','name','status',
        'message_template','total_recipients',
        'sent_count','delivered_count','failed_count',
        'delay_min_seconds','delay_max_seconds',
        'started_at','completed_at',
    ];

    protected $casts = [
        'message_template' => 'array',
        'started_at'       => 'datetime',
        'completed_at'     => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn (BulkJob $b) => $b->uuid ??= Str::uuid());
    }

    public function user(): BelongsTo    { return $this->belongsTo(User::class); }
    public function device(): BelongsTo  { return $this->belongsTo(Device::class); }
    public function messages(): HasMany  { return $this->hasMany(Message::class, 'bulk_job_id'); }

    public function progressPercent(): float
    {
        if ($this->total_recipients === 0) return 0;
        return round(($this->sent_count / $this->total_recipients) * 100, 1);
    }

    public function getRouteKeyName(): string { return 'uuid'; }
}
