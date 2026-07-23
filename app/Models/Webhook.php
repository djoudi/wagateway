<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Webhook extends Model
{
    protected $fillable = [
        'uuid','user_id','name','url','secret',
        'events','is_active','success_count','failure_count','last_triggered_at',
    ];

    protected $casts = [
        'events'            => 'array',
        'is_active'         => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    protected $hidden = ['secret'];

    protected static function booted(): void
    {
        static::creating(function (Webhook $w) {
            $w->uuid   ??= Str::uuid();
            $w->secret ??= Str::random(40);
        });
    }

    public function user(): BelongsTo         { return $this->belongsTo(User::class); }
    public function deliveries(): HasMany      { return $this->hasMany(WebhookDelivery::class); }

    public function listensTo(string $event): bool
    {
        return in_array($event, $this->events ?? []);
    }

    public function getRouteKeyName(): string { return 'uuid'; }
}
