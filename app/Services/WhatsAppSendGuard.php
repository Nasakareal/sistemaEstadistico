<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class WhatsAppSendGuard
{
    public function reserve(string $context, string $periodKey, string $recipient, int $ttlDays = 7): bool
    {
        return Cache::add(
            $this->key($context, $periodKey, $recipient),
            [
                'status' => 'sending',
                'reserved_at' => now()->toIso8601String(),
            ],
            now()->addDays($ttlDays)
        );
    }

    public function markSent(string $context, string $periodKey, string $recipient, ?string $messageId = null, int $ttlDays = 7): void
    {
        Cache::put(
            $this->key($context, $periodKey, $recipient),
            [
                'status' => 'sent',
                'message_id' => $messageId,
                'sent_at' => now()->toIso8601String(),
            ],
            now()->addDays($ttlDays)
        );
    }

    public function release(string $context, string $periodKey, string $recipient): void
    {
        Cache::forget($this->key($context, $periodKey, $recipient));
    }

    protected function key(string $context, string $periodKey, string $recipient): string
    {
        $recipient = preg_replace('/\D+/', '', $recipient) ?: $recipient;
        $hash = sha1($context . '|' . $periodKey . '|' . $recipient);

        return 'whatsapp:send_guard:' . $hash;
    }
}
