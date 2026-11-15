<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class VerifySierseSignature
{
    public function handle(Request $request, Closure $next)
    {
        if (!config('sierse.enabled')) {
            return $this->reject($request, 404, 'disabled');
        }

        $clientId = trim((string) $request->header('X-SIERSE-Client'));
        $keyId = trim((string) $request->header('X-SIERSE-Key-Id'));
        $timestamp = trim((string) $request->header('X-SIERSE-Timestamp'));
        $nonce = trim((string) $request->header('X-SIERSE-Nonce'));
        $signature = strtolower(trim((string) $request->header('X-SIERSE-Signature')));
        $client = config('sierse.clients.' . $clientId);

        if (
            !preg_match('/^[A-Za-z0-9_-]{1,80}$/', $clientId)
            || !is_array($client)
            || strlen((string) ($client['secret'] ?? '')) < 32
        ) {
            return $this->reject($request, 401, 'unknown_client', $clientId);
        }

        if (
            !preg_match('/^[A-Za-z0-9._-]{1,120}$/', $keyId)
            || !hash_equals((string) ($client['key_id'] ?? ''), $keyId)
            || !$this->secretIsCurrent((string) ($client['secret_expires_at'] ?? ''))
        ) {
            return $this->reject($request, 401, 'inactive_key', $clientId);
        }

        if (!$this->clientCertificateIsValid($request, $client)) {
            return $this->reject($request, 403, 'invalid_client_certificate', $clientId);
        }

        if (!$this->ipIsAllowed((string) $request->ip(), (array) ($client['allowed_ips'] ?? []))) {
            return $this->reject($request, 403, 'ip_not_allowed', $clientId);
        }

        $skew = max(30, (int) config('sierse.clock_skew_seconds', 300));
        if (!ctype_digit($timestamp) || abs(time() - (int) $timestamp) > $skew) {
            return $this->reject($request, 401, 'expired_timestamp', $clientId);
        }

        if (!preg_match('/^[A-Za-z0-9_-]{16,128}$/', $nonce) || !preg_match('/^[a-f0-9]{64}$/', $signature)) {
            return $this->reject($request, 401, 'invalid_headers', $clientId);
        }

        $canonical = implode("\n", [
            strtoupper($request->method()),
            $request->getRequestUri(),
            $clientId,
            $keyId,
            $timestamp,
            $nonce,
            hash('sha256', (string) $request->getContent()),
        ]);
        $expected = hash_hmac('sha256', $canonical, (string) $client['secret']);

        if (!hash_equals($expected, $signature)) {
            return $this->reject($request, 401, 'invalid_signature', $clientId);
        }

        $nonceKey = 'sierse:nonce:' . hash('sha256', $clientId . '|' . $keyId . '|' . $nonce);
        $ttl = max($skew, (int) config('sierse.nonce_ttl_seconds', 600));
        if (!Cache::add($nonceKey, true, now()->addSeconds($ttl))) {
            return $this->reject($request, 409, 'replayed_request', $clientId);
        }

        $request->attributes->set('sierse_client_id', $clientId);
        $request->attributes->set('sierse_client_name', (string) ($client['name'] ?? $clientId));
        $request->attributes->set('sierse_key_id', $keyId);

        Log::info('SIERSE API request accepted', $this->context($request, $clientId));

        return $next($request);
    }

    private function reject(Request $request, int $status, string $reason, string $clientId = '')
    {
        Log::warning('SIERSE API request rejected', array_merge(
            $this->context($request, $clientId),
            ['reason' => $reason]
        ));

        // La respuesta no revela si el cliente, la IP o la firma fueron validos.
        $message = $status === 404 ? 'Recurso no encontrado.' : 'Solicitud no autorizada.';

        return response()->json(['ok' => false, 'message' => $message], $status)
            ->header('Cache-Control', 'no-store, private');
    }

    private function context(Request $request, string $clientId): array
    {
        return [
            'client_id' => substr($clientId, 0, 80),
            'key_id' => substr((string) $request->header('X-SIERSE-Key-Id'), 0, 120),
            'ip' => $request->ip(),
            'method' => $request->method(),
            'path' => $request->path(),
        ];
    }

    private function ipIsAllowed(string $ip, array $allowed): bool
    {
        foreach ($allowed as $rule) {
            $rule = trim((string) $rule);
            if ($rule !== '' && ($ip === $rule || $this->ipMatchesCidr($ip, $rule))) {
                return true;
            }
        }

        return false;
    }

    private function clientCertificateIsValid(Request $request, array $client): bool
    {
        if (!config('sierse.require_mtls', false)) {
            return true;
        }

        if (strtoupper((string) $request->server('SSL_CLIENT_VERIFY')) !== 'SUCCESS') {
            return false;
        }

        $certificate = (string) $request->server('SSL_CLIENT_CERT', '');
        if ($certificate === '' || !function_exists('openssl_x509_fingerprint')) {
            return false;
        }

        $fingerprint = openssl_x509_fingerprint($certificate, 'sha256');
        if (!is_string($fingerprint) || $fingerprint === '') {
            return false;
        }

        $parsed = openssl_x509_parse($certificate);
        $now = time();
        if (
            !is_array($parsed)
            || (int) ($parsed['validFrom_time_t'] ?? 0) > $now
            || (int) ($parsed['validTo_time_t'] ?? 0) < $now
        ) {
            return false;
        }

        $fingerprint = $this->normalizeFingerprint($fingerprint);
        foreach ((array) ($client['certificate_fingerprints'] ?? []) as $allowed) {
            $allowed = $this->normalizeFingerprint((string) $allowed);
            if ($allowed !== '' && hash_equals($allowed, $fingerprint)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeFingerprint(string $fingerprint): string
    {
        return strtolower(preg_replace('/[^a-fA-F0-9]/', '', $fingerprint) ?: '');
    }

    private function secretIsCurrent(string $expiresAt): bool
    {
        if (trim($expiresAt) === '') {
            return false;
        }

        $expiration = strtotime($expiresAt);

        return $expiration !== false && $expiration > time();
    }

    private function ipMatchesCidr(string $ip, string $cidr): bool
    {
        if (strpos($cidr, '/') === false) {
            return false;
        }

        [$network, $prefix] = array_pad(explode('/', $cidr, 2), 2, null);
        $ipBinary = @inet_pton($ip);
        $networkBinary = @inet_pton((string) $network);

        if ($ipBinary === false || $networkBinary === false || strlen($ipBinary) !== strlen($networkBinary)) {
            return false;
        }

        $bits = strlen($ipBinary) * 8;
        if (!ctype_digit((string) $prefix) || (int) $prefix < 0 || (int) $prefix > $bits) {
            return false;
        }

        $bytes = intdiv((int) $prefix, 8);
        $remainingBits = (int) $prefix % 8;

        if ($bytes > 0 && substr($ipBinary, 0, $bytes) !== substr($networkBinary, 0, $bytes)) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;

        return (ord($ipBinary[$bytes]) & $mask) === (ord($networkBinary[$bytes]) & $mask);
    }
}
