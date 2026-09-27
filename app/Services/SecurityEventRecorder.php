<?php

namespace App\Services;

use App\Models\SecurityEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SecurityEventRecorder
{
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'token',
        'access_token',
        'refresh_token',
        'authorization',
        'cookie',
        'secret',
        'api_key',
        'apikey',
        '_token',
        'body',
        'content',
    ];

    public function record(
        string $eventCode,
        string $description,
        string $severity = 'warning',
        string $category = 'http',
        ?Request $request = null,
        array $metadata = []
    ): void {
        if (!config('security_logging.enabled', true)) {
            return;
        }

        $request = $request ?: (app()->bound('request') ? request() : null);
        $now = Carbon::now();
        $bucket = $now->copy()->startOfMinute();
        $path = $request ? '/' . ltrim((string) $request->path(), '/') : null;
        $method = $request ? strtoupper((string) $request->method()) : null;
        $ip = $request ? $request->ip() : null;
        $user = $request ? $request->user() : null;
        $userId = $user
            ? (int) $user->getAuthIdentifier()
            : (isset($metadata['user_id']) && $metadata['user_id'] !== null ? (int) $metadata['user_id'] : null);
        $route = $request ? $request->route() : null;
        $routeName = is_object($route) ? $route->getName() : null;
        $requestId = $request ? $this->requestId($request) : null;

        $fingerprint = hash('sha256', implode('|', [
            $eventCode,
            $severity,
            $category,
            (string) $ip,
            (string) $userId,
            (string) $method,
            (string) $path,
        ]));

        try {
            $existing = SecurityEvent::query()
                ->where('fingerprint', $fingerprint)
                ->where('bucket_at', $bucket)
                ->first();

            if ($existing) {
                $existing->forceFill([
                    'occurred_at' => $now,
                    'last_seen_at' => $now,
                    'occurrences' => $existing->occurrences + 1,
                    'status_code' => $metadata['status_code'] ?? $existing->status_code,
                    'metadata' => $this->sanitizeMetadata(array_merge(
                        (array) $existing->metadata,
                        $metadata
                    )),
                ])->save();

                return;
            }

            SecurityEvent::create([
                'occurred_at' => $now,
                'first_seen_at' => $now,
                'last_seen_at' => $now,
                'bucket_at' => $bucket,
                'occurrences' => 1,
                'severity' => $this->normalizeSeverity($severity),
                'category' => Str::limit($category, 50, ''),
                'event_code' => Str::limit($eventCode, 100, ''),
                'description' => Str::limit($description, 500, ''),
                'ip_address' => $ip ? Str::limit($ip, 45, '') : null,
                'user_id' => $userId,
                'method' => $method ? Str::limit($method, 10, '') : null,
                'path' => $path ? Str::limit($path, 2000, '') : null,
                'route_name' => $routeName ? Str::limit($routeName, 191, '') : null,
                'status_code' => isset($metadata['status_code']) ? (int) $metadata['status_code'] : null,
                'request_id' => $requestId,
                'user_agent' => $request ? Str::limit((string) $request->userAgent(), 1000, '') : null,
                'metadata' => $this->sanitizeMetadata($metadata),
                'fingerprint' => $fingerprint,
            ]);
        } catch (Throwable $exception) {
            Log::warning('No se pudo guardar un evento de seguridad.', [
                'event_code' => $eventCode,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function maskedIdentity(?string $identity): ?string
    {
        $identity = trim((string) $identity);

        if ($identity === '') {
            return null;
        }

        if (strpos($identity, '@') !== false) {
            [$local, $domain] = array_pad(explode('@', $identity, 2), 2, '');
            $visible = mb_substr($local, 0, min(2, mb_strlen($local)));

            return $visible . str_repeat('*', max(1, mb_strlen($local) - mb_strlen($visible))) . '@' . $domain;
        }

        $length = mb_strlen($identity);

        return str_repeat('*', max(0, $length - 4)) . mb_substr($identity, -4);
    }

    private function requestId(Request $request): string
    {
        $requestId = trim((string) $request->headers->get('X-Request-ID', ''));

        return Str::limit($requestId !== '' ? $requestId : (string) Str::uuid(), 64, '');
    }

    private function normalizeSeverity(string $severity): string
    {
        $severity = strtolower(trim($severity));

        return in_array($severity, ['info', 'warning', 'high', 'critical'], true)
            ? $severity
            : 'warning';
    }

    private function sanitizeMetadata(array $metadata): array
    {
        $clean = [];

        foreach ($metadata as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            if ($this->isSensitiveKey($normalizedKey)) {
                $clean[$key] = '[REDACTED]';
                continue;
            }

            if (is_array($value)) {
                $clean[$key] = $this->sanitizeMetadata($value);
            } elseif (is_scalar($value) || $value === null) {
                $clean[$key] = is_string($value) ? Str::limit($value, 1000, '') : $value;
            } else {
                $clean[$key] = gettype($value);
            }
        }

        return $clean;
    }

    private function isSensitiveKey(string $key): bool
    {
        foreach (self::SENSITIVE_KEYS as $sensitive) {
            if ($key === $sensitive || Str::contains($key, $sensitive)) {
                return true;
            }
        }

        return false;
    }
}
