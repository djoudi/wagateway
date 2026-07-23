<?php

namespace App\Models;

use App\Enums\DeviceStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Device extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid','user_id','name','phone_number','status',
        'qr_code','qr_expires_at',
        'session_data',       // legacy - kept for migration compat
        'session_data_enc',   // encrypted going forward
        'connected_at','last_seen_at',
        'messages_sent_today','messages_sent_total',
        'last_count_reset','is_active',
    ];

    protected $casts = [
        'status'           => DeviceStatus::class,
        'qr_expires_at'    => 'datetime',
        'connected_at'     => 'datetime',
        'last_seen_at'     => 'datetime',
        'last_count_reset' => 'date',
        'is_active'        => 'boolean',
    ];

    protected $hidden = ['session_data', 'session_data_enc'];

    protected static function booted(): void
    {
        static::creating(fn (Device $d) => $d->uuid ??= (string) Str::uuid());
    }

    // ─── Encrypted session data ───────────────────────────────────────────────

    protected function sessionDataEnc(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (! $value) return null;
                try {
                    return decrypt($value);
                } catch (\Exception) {
                    return null; // corrupted or old format
                }
            },
            set: fn ($value) => $value ? encrypt($value) : null,
        );
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function user(): BelongsTo  { return $this->belongsTo(User::class); }
    public function messages(): HasMany { return $this->hasMany(Message::class); }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeConnected($query)
    {
        return $query->where('status', DeviceStatus::Connected);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ─── Business logic ───────────────────────────────────────────────────────

    public function isConnected(): bool
    {
        return $this->status === DeviceStatus::Connected;
    }

    public function isQrExpired(): bool
    {
        return $this->qr_expires_at?->isPast() ?? true;
    }

    public function resetDailyCount(): void
    {
        $this->update([
            'messages_sent_today' => 0,
            'last_count_reset'    => today(),
        ]);
    }

    public function getRouteKeyName(): string { return 'uuid'; }
}
