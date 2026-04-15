<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Cache;

class WhatsAppStateService
{
    protected int $ttlMinutes = 30;

    protected function key(string $telefono): string
    {
        return 'wa_state_' . preg_replace('/\D+/', '', $telefono);
    }

    public function getContext(string $telefono): array
    {
        return Cache::get($this->key($telefono), []);
    }

    public function putContext(string $telefono, array $data): void
    {
        Cache::put($this->key($telefono), $data, now()->addMinutes($this->ttlMinutes));
    }

    public function clear(string $telefono): void
    {
        Cache::forget($this->key($telefono));
    }
}
