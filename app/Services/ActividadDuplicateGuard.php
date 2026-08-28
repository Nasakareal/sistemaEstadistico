<?php

namespace App\Services;

use App\Models\Actividad;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Str;

class ActividadDuplicateGuard
{
    public const WINDOW_MINUTES = 2;
    public const MESSAGE = 'Parece que esta actividad ya fue capturada hace menos de 2 minutos. No se guardo para evitar duplicados.';

    private const FINGERPRINT_FIELDS = [
        'actividad_categoria_id',
        'actividad_subcategoria_id',
        'folio_c5i',
        'unidad_org_id',
        'delegacion_id',
        'destacamento_id',
        'fecha',
        'lugar',
        'municipio',
        'carretera',
        'tramo',
        'kilometro',
        'lat',
        'lng',
        'km_recorridos',
        'coordenadas_texto',
        'motivo',
        'narrativa',
        'acciones_realizadas',
        'observaciones',
        'personas_alcanzadas',
        'personas_participantes',
        'personas_detenidas',
        'elementos_participantes_texto',
        'patrullas_participantes_texto',
    ];

    private const DETAIL_FIELDS = [
        'folio_c5i',
        'lugar',
        'municipio',
        'carretera',
        'tramo',
        'kilometro',
        'coordenadas_texto',
        'motivo',
        'narrativa',
        'acciones_realizadas',
        'observaciones',
        'elementos_participantes_texto',
        'patrullas_participantes_texto',
    ];

    public function hashUploadedFiles(iterable $files): array
    {
        $hashes = [];

        foreach ($files as $file) {
            if (!$file || !method_exists($file, 'getRealPath')) {
                continue;
            }

            $realPath = $file->getRealPath();

            if (!$realPath) {
                continue;
            }

            $hashes[] = hash_file('sha256', $realPath);
        }

        return $hashes;
    }

    public function hasRepeatedHashes(array $hashes): bool
    {
        $hashes = array_values(array_filter($hashes));

        return count($hashes) !== count(array_unique($hashes));
    }

    public function findRecentDuplicate(int $userId, array $payload, array $fotoHashes, ?int $exceptActividadId = null): ?Actividad
    {
        if ($userId <= 0) {
            return null;
        }

        $since = now($this->timezone())->subMinutes(self::WINDOW_MINUTES);
        $baseQuery = Actividad::query()
            ->where('created_by', $userId)
            ->where('created_at', '>=', $since)
            ->when($exceptActividadId, function ($query) use ($exceptActividadId) {
                $query->where('id', '!=', $exceptActividadId);
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $fotoHashes = array_values(array_unique(array_filter($fotoHashes)));

        $canCompareExactPayload = $this->payloadHasEnoughDetail($payload);
        $fingerprint = $canCompareExactPayload ? $this->payloadFingerprint($payload) : null;
        $candidates = (clone $baseQuery)
            ->with(['fotosTodas' => function ($query) {
                $query->whereNull('foto_eliminada_at');
            }])
            ->limit(20)
            ->get();

        foreach ($candidates as $candidate) {
            $photoMatches = !empty($fotoHashes) && $this->activityHasAnyPhotoHash($candidate, $fotoHashes);

            if ($photoMatches && $this->payloadsLookSimilar($payload, $candidate)) {
                return $candidate;
            }

            if ($canCompareExactPayload && $this->payloadFingerprint($candidate) === $fingerprint) {
                return $candidate;
            }
        }

        return null;
    }

    private function activityHasAnyPhotoHash(Actividad $actividad, array $fotoHashes): bool
    {
        if ($actividad->foto_hash && in_array($actividad->foto_hash, $fotoHashes, true)) {
            return true;
        }

        return $actividad->fotosTodas
            ->contains(function ($foto) use ($fotoHashes) {
                return $foto->foto_hash && in_array($foto->foto_hash, $fotoHashes, true);
            });
    }

    private function payloadsLookSimilar(array $payload, Actividad $candidate): bool
    {
        foreach (['actividad_categoria_id', 'actividad_subcategoria_id', 'fecha'] as $field) {
            if ($this->normalizedValue($field, $payload[$field] ?? null) !== $this->normalizedValue($field, $candidate->{$field})) {
                return false;
            }
        }

        $matchingDetails = 0;

        foreach (self::DETAIL_FIELDS as $field) {
            $payloadValue = $this->normalizedValue($field, $payload[$field] ?? null);
            $candidateValue = $this->normalizedValue($field, $candidate->{$field});

            if ($payloadValue !== null && $candidateValue !== null && $payloadValue === $candidateValue) {
                $matchingDetails++;
            }
        }

        $payloadLat = $this->normalizedValue('lat', $payload['lat'] ?? null);
        $payloadLng = $this->normalizedValue('lng', $payload['lng'] ?? null);
        $candidateLat = $this->normalizedValue('lat', $candidate->lat);
        $candidateLng = $this->normalizedValue('lng', $candidate->lng);

        if ($payloadLat !== null && $payloadLng !== null && $payloadLat === $candidateLat && $payloadLng === $candidateLng) {
            $matchingDetails++;
        }

        return $matchingDetails >= 2;
    }

    private function payloadHasEnoughDetail(array $payload): bool
    {
        $categoryId = (int) ($this->normalizedValue('actividad_categoria_id', $payload['actividad_categoria_id'] ?? null) ?? 0);
        $subcategoryId = (int) ($this->normalizedValue('actividad_subcategoria_id', $payload['actividad_subcategoria_id'] ?? null) ?? 0);
        $date = $this->normalizedValue('fecha', $payload['fecha'] ?? null);

        if ($categoryId <= 0 || $subcategoryId <= 0 || $date === null) {
            return false;
        }

        $detailCount = 0;

        foreach (self::DETAIL_FIELDS as $field) {
            if ($this->normalizedValue($field, $payload[$field] ?? null) !== null) {
                $detailCount++;
            }
        }

        $hasCoordinates = $this->normalizedValue('lat', $payload['lat'] ?? null) !== null
            && $this->normalizedValue('lng', $payload['lng'] ?? null) !== null;

        return $detailCount >= 2 || ($hasCoordinates && $detailCount >= 1);
    }

    private function payloadFingerprint($payload): string
    {
        $normalized = [];

        foreach (self::FINGERPRINT_FIELDS as $field) {
            $normalized[$field] = $this->normalizedValue($field, $this->value($payload, $field));
        }

        return sha1(json_encode($normalized));
    }

    private function value($payload, string $field)
    {
        if ($payload instanceof Actividad) {
            return $payload->{$field};
        }

        return is_array($payload) ? ($payload[$field] ?? null) : null;
    }

    private function normalizedValue(string $field, $value)
    {
        if ($value === null) {
            return null;
        }

        if ($field === 'fecha') {
            return $this->normalizeDate($value);
        }

        if (in_array($field, [
            'actividad_categoria_id',
            'actividad_subcategoria_id',
            'unidad_org_id',
            'delegacion_id',
            'destacamento_id',
            'personas_alcanzadas',
            'personas_participantes',
            'personas_detenidas',
        ], true)) {
            return $value === '' ? null : (int) $value;
        }

        if (in_array($field, ['lat', 'lng'], true)) {
            return is_numeric($value) ? number_format((float) $value, 5, '.', '') : null;
        }

        if ($field === 'km_recorridos') {
            return is_numeric($value) ? number_format((float) $value, 2, '.', '') : null;
        }

        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        $text = mb_strtoupper((string) preg_replace('/\s+/u', ' ', $text), 'UTF-8');

        return Str::ascii($text);
    }

    private function normalizeDate($value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        try {
            return Carbon::parse($text, $this->timezone())->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function timezone(): string
    {
        return config('app.timezone', 'America/Mexico_City');
    }
}
