<?php

namespace App\Livewire\ApiKeys;

use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class ApiKeyManager extends Component
{
    // Confirmation gate before revealing
    public bool   $showConfirmReveal = false;
    public string $revealType        = '';   // 'live' | 'test'
    public string $confirmPassword   = '';
    public bool   $passwordError     = false;

    // One-time flash after regeneration
    public ?array $newKeyFlash = null;   // ['type'=>'live','key'=>'wg_live_...']

    // Regeneration confirm
    public bool   $showRegenConfirm = false;
    public string $regenType        = '';

    // ── Reveal with password gate ─────────────────────────────────────────────

    public function requestReveal(string $type): void
    {
        $this->revealType        = $type;
        $this->confirmPassword   = '';
        $this->passwordError     = false;
        $this->showConfirmReveal = true;
    }

    public function confirmReveal(): void
    {
        $user = auth()->user();

        if (! Hash::check($this->confirmPassword, $user->password)) {
            $this->passwordError = true;
            return;
        }

        // Key is not stored in DB anymore — we cannot reveal it
        // Show a message explaining this, and offer to regenerate
        $this->showConfirmReveal = false;
        $this->confirmPassword   = '';

        $this->dispatch('notify',
            type: 'warning',
            message: 'For security, keys are stored as hashes and cannot be retrieved. Regenerate to get a new key.'
        );
    }

    public function cancelReveal(): void
    {
        $this->showConfirmReveal = false;
        $this->confirmPassword   = '';
        $this->passwordError     = false;
    }

    // ── Regenerate ────────────────────────────────────────────────────────────

    public function requestRegenerate(string $type): void
    {
        $this->regenType        = $type;
        $this->confirmPassword  = '';
        $this->passwordError    = false;
        $this->showRegenConfirm = true;
    }

    public function confirmRegenerate(): void
    {
        $user = auth()->user();

        if (! Hash::check($this->confirmPassword, $user->password)) {
            $this->passwordError = true;
            return;
        }

        // Generate new keys
        $keys = $user->generateApiKeys();

        \App\Models\SecurityEvent::log('api_key_regenerated', $user->id, ['type' => $this->regenType]);

        $this->showRegenConfirm = false;
        $this->confirmPassword  = '';
        $this->regenType        = '';

        // Flash new key — ONE TIME only
        $this->newKeyFlash = [
            'type' => $this->regenType ?: 'live',
            'key'  => $this->regenType === 'test' ? $keys['test'] : $keys['live'],
        ];

        $this->dispatch('notify',
            type: 'success',
            message: 'API key regenerated. Copy it now — it will not be shown again.'
        );
    }

    public function cancelRegen(): void
    {
        $this->showRegenConfirm = false;
        $this->confirmPassword  = '';
        $this->passwordError    = false;
    }

    public function dismissFlash(): void
    {
        $this->newKeyFlash = null;
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.api-keys.api-key-manager', [
            'liveKeyDisplay' => $user->apiKeyDisplay(),
            'testKeyDisplay' => $user->apiKeyTestDisplay(),
            'liveKeyExists'  => ! empty($user->api_key_prefix),
            'testKeyExists'  => ! empty($user->api_key_test_prefix),
            'lastUsedAt'     => $user->api_key_last_used_at,
            'lastUsedIp'     => $user->api_key_last_used_ip,
        ]);
    }
}
