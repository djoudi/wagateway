<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements MustVerifyEmail, FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name','email','password',
        'plan_id',
        'api_key','api_key_hash','api_key_prefix',
        'api_key_test','api_key_test_hash','api_key_test_prefix',
        'api_key_last_used_at','api_key_last_used_ip',
        'plan_expires_at','is_suspended','suspension_reason',
    ];

    protected $hidden = [
        'password','remember_token',
        'api_key','api_key_test',          // never expose raw keys in serialization
        'api_key_hash','api_key_test_hash',
    ];

    protected $casts = [
        'email_verified_at'     => 'datetime',
        'plan_expires_at'       => 'datetime',
        'api_key_last_used_at'  => 'datetime',
        'is_suspended'          => 'boolean',
        'password'              => 'hashed',
    ];

    // ─── Relations ───────────────────────────────────────────────────────────

    public function plan(): BelongsTo              { return $this->belongsTo(Plan::class); }
    public function devices(): HasMany             { return $this->hasMany(Device::class); }
    public function messages(): HasMany            { return $this->hasMany(Message::class); }
    public function webhooks(): HasMany            { return $this->hasMany(Webhook::class); }
    public function templates(): HasMany           { return $this->hasMany(Template::class); }
    public function bulkJobs(): HasMany            { return $this->hasMany(BulkJob::class); }
    public function scheduledMessages(): HasMany   { return $this->hasMany(ScheduledMessage::class); }

    // ─── API Key Management ──────────────────────────────────────────────────

    /**
     * Generate both API keys. Raw key shown once, hash stored.
     * Returns the raw keys for one-time display.
     */
    public function generateApiKeys(): array
    {
        $liveRaw = 'wg_live_' . Str::random(40);
        $testRaw = 'wg_test_' . Str::random(40);

        $this->update([
            'api_key'            => null,            // no longer stored raw
            'api_key_hash'       => hash('sha256', $liveRaw),
            'api_key_prefix'     => substr($liveRaw, 0, 12),
            'api_key_test'       => null,
            'api_key_test_hash'  => hash('sha256', $testRaw),
            'api_key_test_prefix'=> substr($testRaw, 0, 12),
        ]);

        return ['live' => $liveRaw, 'test' => $testRaw];
    }

    /**
     * Verify an incoming token against stored hashes.
     */
    public static function findByApiKey(string $token): ?self
    {
        $hash = hash('sha256', $token);

        return self::where('api_key_hash', $hash)
            ->orWhere('api_key_test_hash', $hash)
            ->first();
    }

    /**
     * Record key usage (non-blocking — queued).
     */
    public function touchApiKeyUsage(): void
    {
        self::where('id', $this->id)->update([
            'api_key_last_used_at' => now(),
            'api_key_last_used_ip' => Request::ip(),
        ]);
    }

    /**
     * Display-safe representation of keys (never exposes raw).
     */
    public function apiKeyDisplay(): string
    {
        if (! $this->api_key_prefix) return 'Not generated';
        return $this->api_key_prefix . str_repeat('•', 32) . '••••';
    }

    public function apiKeyTestDisplay(): string
    {
        if (! $this->api_key_test_prefix) return 'Not generated';
        return $this->api_key_test_prefix . str_repeat('•', 32) . '••••';
    }

    // ─── Business logic ───────────────────────────────────────────────────────

    public function hasActivePlan(): bool
    {
        return $this->plan_id !== null
            && (is_null($this->plan_expires_at) || $this->plan_expires_at->isFuture());
    }

    public function isAdmin(): bool
    {
        return in_array($this->email, config('wagateway.admin_emails', []));
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin();
    }

    public function scopeActive($query)
    {
        return $query->where('is_suspended', false)
            ->where(fn ($q) => $q->whereNull('plan_expires_at')
                ->orWhere('plan_expires_at', '>', now()));
    }

    /**
     * Onboarding completion status.
     */
    public function onboardingSteps(): array
    {
        $deviceCount  = $this->devices()->count();
        $messageCount = $this->messages()->count();
        $webhookCount = $this->webhooks()->count();

        return [
            'account_created'   => true,
            'device_connected'  => $deviceCount > 0,
            'first_message_sent'=> $messageCount > 0,
            'webhook_configured'=> $webhookCount > 0,
            'completed'         => $deviceCount > 0 && $messageCount > 0,
        ];
    }

    public function onboardingPercent(): int
    {
        $steps    = $this->onboardingSteps();
        $done     = count(array_filter($steps));
        $total    = count($steps) - 1; // exclude 'completed' key
        return (int) round(($done / $total) * 100);
    }
}
