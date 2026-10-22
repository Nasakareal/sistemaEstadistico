<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HoneypotRequestAnalyzer
{
    private SecurityEventRecorder $recorder;

    public function __construct(SecurityEventRecorder $recorder)
    {
        $this->recorder = $recorder;
    }

    public function inspect(Request $request, string $decoyPath): array
    {
        $signals = [];
        $sqlPatterns = $this->sqlInjectionPatterns($request);

        if ($sqlPatterns !== []) {
            $signals[] = 'sql_injection';
        }

        $bulkIndicators = $this->bulkQueryIndicators($request);
        if ($bulkIndicators !== []) {
            $signals[] = 'mass_query';
        }

        $restricted = $this->isRestrictedPath($decoyPath);
        if ($restricted) {
            $signals[] = 'restricted_resource';
        }

        $distinctPaths = $this->rememberPath($request, $decoyPath);
        $enumerationThreshold = (int) config('honeypot_api.enumeration_threshold', 5);
        if ($distinctPaths >= $enumerationThreshold) {
            $signals[] = 'endpoint_enumeration';
        }

        [$eventCode, $description, $severity, $category] = $this->classification($signals);
        $rawBody = (string) $request->getContent();
        $queryString = (string) $request->server->get('QUERY_STRING', '');

        $metadata = [
            'honeypot' => true,
            'signals' => array_values(array_unique($signals)),
            'sql_pattern_labels' => $sqlPatterns,
            'bulk_indicators' => $bulkIndicators,
            'distinct_decoy_paths_in_window' => $distinctPaths,
            'decoy_path' => '/' . ltrim($decoyPath, '/'),
            'input_fields' => array_values(array_slice(array_keys($request->all()), 0, 40)),
            'query_fields' => array_values(array_slice(array_keys($request->query()), 0, 40)),
            'body_bytes' => strlen($rawBody),
            'payload_fingerprint' => hash('sha256', $queryString . "\n" . $rawBody),
            'status_code' => $restricted ? 403 : null,
        ];

        $this->recorder->record(
            $eventCode,
            $description,
            $severity,
            $category,
            $request,
            $metadata
        );

        return [
            'restricted' => $restricted,
            'signals' => $metadata['signals'],
            'event_code' => $eventCode,
        ];
    }

    private function sqlInjectionPatterns(Request $request): array
    {
        $haystack = strtolower($this->safePayloadText($request));
        $patterns = [
            'union_select' => '/\bunion\s+(?:all\s+)?select\b/i',
            'boolean_tautology' => '/(?:\bor\b|\band\b)\s+[\'\"]?\d+[\'\"]?\s*=\s*[\'\"]?\d+/i',
            'time_delay' => '/\b(?:sleep|benchmark|pg_sleep|waitfor\s+delay)\s*\(/i',
            'schema_discovery' => '/\b(?:information_schema|pg_catalog|sqlite_master)\b/i',
            'file_access' => '/\b(?:load_file|into\s+outfile|xp_cmdshell)\b/i',
            'sql_comment' => '/(?:--(?:\s|$)|\/\*|#\s*$)/m',
            'stacked_statement' => '/;\s*(?:select|insert|update|delete|drop|alter|create)\b/i',
        ];

        $matches = [];
        foreach ($patterns as $label => $pattern) {
            if (preg_match($pattern, $haystack) === 1) {
                $matches[] = $label;
            }
        }

        return $matches;
    }

    private function safePayloadText(Request $request): string
    {
        $parts = [(string) $request->server->get('QUERY_STRING', '')];
        $body = (string) $request->getContent();

        // Detection is bounded. The content is inspected as inert text and is never
        // interpolated into SQL, evaluated, deserialized or written to a real model.
        $parts[] = substr($body, 0, (int) config('honeypot_api.max_inspection_bytes', 32768));

        foreach ($request->all() as $value) {
            $this->appendScalarValues($parts, $value);
            if (count($parts) >= 200) {
                break;
            }
        }

        $text = implode("\n", $parts);
        for ($i = 0; $i < 2; $i++) {
            $decoded = rawurldecode($text);
            if ($decoded === $text) {
                break;
            }
            $text = $decoded;
        }

        return substr($text, 0, (int) config('honeypot_api.max_inspection_bytes', 32768));
    }

    private function appendScalarValues(array &$parts, $value): void
    {
        if (is_array($value)) {
            foreach (array_slice($value, 0, 50) as $nested) {
                $this->appendScalarValues($parts, $nested);
            }
            return;
        }

        if (is_scalar($value)) {
            $parts[] = substr((string) $value, 0, 1000);
        }
    }

    private function bulkQueryIndicators(Request $request): array
    {
        $indicators = [];
        $limit = max(
            (int) $request->input('limit', 0),
            (int) $request->input('per_page', 0),
            (int) $request->input('page_size', 0)
        );

        if ($limit > (int) config('honeypot_api.mass_query_limit', 250)) {
            $indicators[] = 'excessive_page_size';
        }

        foreach (['export', 'download', 'include_deleted', 'with_secrets'] as $field) {
            if ($request->has($field) && filter_var($request->input($field), FILTER_VALIDATE_BOOLEAN)) {
                $indicators[] = $field;
            }
        }

        if (in_array(strtolower((string) $request->input('scope', '')), ['all', '*', 'unrestricted'], true)) {
            $indicators[] = 'unrestricted_scope';
        }

        $ids = $request->input('ids');
        if (is_array($ids) && count($ids) > 100) {
            $indicators[] = 'large_id_batch';
        }

        return $indicators;
    }

    private function isRestrictedPath(string $path): bool
    {
        return preg_match('#(?:^|/)(?:admin|audit|backups?|credentials?|tokens?|users?/export|classified)(?:/|$)#i', $path) === 1;
    }

    private function rememberPath(Request $request, string $path): int
    {
        $key = 'honeypot-enumeration:' . hash('sha256', (string) $request->ip());
        $paths = Cache::get($key, []);
        $paths = is_array($paths) ? $paths : [];
        $paths[hash('sha256', strtolower('/' . trim($path, '/')))] = true;
        $paths = array_slice($paths, -50, null, true);

        Cache::put(
            $key,
            $paths,
            now()->addMinutes((int) config('honeypot_api.enumeration_window_minutes', 10))
        );

        return count($paths);
    }

    private function classification(array $signals): array
    {
        if (in_array('sql_injection', $signals, true)) {
            return ['honeypot_sql_injection_attempt', 'El señuelo recibió un payload compatible con inyección SQL.', 'critical', 'injection'];
        }

        if (in_array('endpoint_enumeration', $signals, true)) {
            return ['honeypot_endpoint_enumeration', 'Se detectó enumeración de endpoints en la API señuelo.', 'high', 'reconnaissance'];
        }

        if (in_array('mass_query', $signals, true)) {
            return ['honeypot_mass_query_attempt', 'El señuelo recibió una solicitud compatible con extracción masiva.', 'high', 'exfiltration'];
        }

        if (in_array('restricted_resource', $signals, true)) {
            return ['honeypot_restricted_access', 'Se intentó acceder a un recurso restringido de la API señuelo.', 'high', 'authorization'];
        }

        return ['honeypot_interaction', 'Se recibió una interacción con la API señuelo.', 'warning', 'deception'];
    }
}
