<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ScheduledMessage extends Model
{
    protected $fillable = [
        'uuid','user_id','device_id','to_number',
        'message_data','scheduled_at','status','message_id',
    ];

    protected $casts = [
        'message_data' => 'array',
        'scheduled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn (ScheduledMessage $s) => $s->uuid ??= Str::uuid());
    }

    public function user(): BelongsTo    { return $this->belongsTo(User::class); }
    public function device(): BelongsTo  { return $this->belongsTo(Device::class); }
    public function message(): BelongsTo { return $this->belongsTo(Message::class); }

    public function scopePending($query)
    {
        return $query->where('status', 'pending')->where('scheduled_at', '<=', now());
    }

    public function getRouteKeyName(): string { return 'uuid'; }
}
