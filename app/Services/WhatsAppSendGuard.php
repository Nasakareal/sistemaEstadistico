<?php

namespace App\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class WhatsAppSendGuard
{
    private const RESERVATION_TTL_MINUTES = 45;

    public function reserve(string $context, string $periodKey, string $recipient, int $ttlDays = 7): bool
    {
        $recipient = $this->normalizeRecipient($recipient);

        if ($this->databaseGuardAvailable()) {
            try {
                $this->purgeExpiredDatabaseRows();

                DB::table('whatsapp_send_guards')->insert([
                    'context' => $context,
                    'period_key' => $periodKey,
                    'recipient' => $recipient,
                    'status' => 'sending',
                    'reserved_at' => now(),
                    'expires_at' => now()->addMinutes(self::RESERVATION_TTL_MINUTES),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return true;
            } catch (QueryException $e) {
                if ($this->isDuplicateKey($e)) {
                    return false;
                }

                Log::warning('No se pudo reservar envio WhatsApp en DB; se intentara cache/fail-open.', [
                    'context' => $context,
                    'period_key' => $periodKey,
                    'recipient' => $recipient,
                    'error' => $e->getMessage(),
                ]);
            } catch (Throwable $e) {
                Log::warning('No se pudo reservar envio WhatsApp en DB; se intentara cache/fail-open.', [
                    'context' => $context,
                    'period_key' => $periodKey,
                    'recipient' => $recipient,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->reserveInCache($context, $periodKey, $recipient, $ttlDays);
    }

    public function markSent(string $context, string $periodKey, string $recipient, ?string $messageId = null, int $ttlDays = 7): void
    {
        $recipient = $this->normalizeRecipient($recipient);

        if ($this->databaseGuardAvailable()) {
            try {
                DB::table('whatsapp_send_guards')->updateOrInsert(
                    [
                        'context' => $context,
                        'period_key' => $periodKey,
                        'recipient' => $recipient,
                    ],
                    [
                        'status' => 'sent',
                        'message_id' => $messageId,
                        'sent_at' => now(),
                        'expires_at' => now()->addDays($ttlDays),
                        'updated_at' => now(),
                    ]
                );

                $this->releaseInCache($context, $periodKey, $recipient);

                return;
            } catch (Throwable $e) {
                Log::warning('No se pudo marcar envio WhatsApp en DB; se usara cache si es posible.', [
                    'context' => $context,
                    'period_key' => $periodKey,
                    'recipient' => $recipient,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->markSentInCache($context, $periodKey, $recipient, $messageId, $ttlDays);
    }

    public function release(string $context, string $periodKey, string $recipient): void
    {
        $recipient = $this->normalizeRecipient($recipient);

        if ($this->databaseGuardAvailable()) {
            try {
                DB::table('whatsapp_send_guards')
                    ->where('context', $context)
                    ->where('period_key', $periodKey)
                    ->where('recipient', $recipient)
                    ->delete();
            } catch (Throwable $e) {
                Log::warning('No se pudo liberar envio WhatsApp en DB; se intentara cache.', [
                    'context' => $context,
                    'period_key' => $periodKey,
                    'recipient' => $recipient,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->releaseInCache($context, $periodKey, $recipient);
    }

    protected function key(string $context, string $periodKey, string $recipient): string
    {
        $recipient = $this->normalizeRecipient($recipient);
        $hash = sha1($context . '|' . $periodKey . '|' . $recipient);

        return 'whatsapp:send_guard:' . $hash;
    }

    protected function normalizeRecipient(string $recipient): string
    {
        return preg_replace('/\D+/', '', $recipient) ?: $recipient;
    }

    protected function databaseGuardAvailable(): bool
    {
        try {
            return Schema::hasTable('whatsapp_send_guards');
        } catch (Throwable $e) {
            Log::warning('No se pudo verificar tabla whatsapp_send_guards; se usara cache/fail-open.', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function purgeExpiredDatabaseRows(): void
    {
        try {
            DB::table('whatsapp_send_guards')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now())
                ->delete();
        } catch (Throwable $e) {
            Log::warning('No se pudieron depurar reservas WhatsApp expiradas.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function isDuplicateKey(QueryException $e): bool
    {
        $sqlState = $e->errorInfo[0] ?? null;
        $driverCode = (string) ($e->errorInfo[1] ?? '');
        $message = $e->getMessage();

        return ($sqlState === '23000'
            && in_array($driverCode, ['1062', '19', '1555'], true)
        )
            || strpos($message, 'Duplicate entry') !== false
            || strpos($message, 'UNIQUE constraint failed') !== false;
    }

    protected function reserveInCache(string $context, string $periodKey, string $recipient, int $ttlDays): bool
    {
        try {
            return Cache::add(
                $this->key($context, $periodKey, $recipient),
                [
                    'status' => 'sending',
                    'reserved_at' => now()->toIso8601String(),
                ],
                now()->addMinutes(self::RESERVATION_TTL_MINUTES)
            );
        } catch (Throwable $e) {
            Log::warning('No se pudo reservar envio WhatsApp en cache; se permite envio para no perderlo.', [
                'context' => $context,
                'period_key' => $periodKey,
                'recipient' => $recipient,
                'error' => $e->getMessage(),
            ]);

            return true;
        }
    }

    protected function markSentInCache(string $context, string $periodKey, string $recipient, ?string $messageId, int $ttlDays): void
    {
        try {
            Cache::put(
                $this->key($context, $periodKey, $recipient),
                [
                    'status' => 'sent',
                    'message_id' => $messageId,
                    'sent_at' => now()->toIso8601String(),
                ],
                now()->addDays($ttlDays)
            );
        } catch (Throwable $e) {
            Log::warning('No se pudo marcar envio WhatsApp en cache.', [
                'context' => $context,
                'period_key' => $periodKey,
                'recipient' => $recipient,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function releaseInCache(string $context, string $periodKey, string $recipient): void
    {
        try {
            Cache::forget($this->key($context, $periodKey, $recipient));
        } catch (Throwable $e) {
            Log::warning('No se pudo liberar envio WhatsApp en cache.', [
                'context' => $context,
                'period_key' => $periodKey,
                'recipient' => $recipient,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
