<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityEvent extends Model
{
    protected $fillable = [
        'occurred_at',
        'first_seen_at',
        'last_seen_at',
        'bucket_at',
        'occurrences',
        'severity',
        'category',
        'event_code',
        'description',
        'ip_address',
        'user_id',
        'method',
        'path',
        'route_name',
        'status_code',
        'request_id',
        'user_agent',
        'metadata',
        'fingerprint',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'bucket_at' => 'datetime',
        'metadata' => 'array',
        'occurrences' => 'integer',
        'status_code' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeWithoutKnownOperationalNoise($query)
    {
        return $query->whereRaw(
            "NOT (
                (COALESCE(method, '') = ? AND COALESCE(status_code, 0) = ? AND COALESCE(path, '') = ?)
                OR (COALESCE(method, '') = ? AND COALESCE(status_code, 0) = ? AND COALESCE(path, '') IN (?, ?, ?))
                OR (COALESCE(method, '') = ? AND COALESCE(status_code, 0) = ? AND COALESCE(path, '') = ?)
                OR (
                    COALESCE(event_code, '') = ?
                    AND COALESCE(status_code, 0) = ?
                    AND user_id IS NOT NULL
                    AND COALESCE(path, '') IN (?, ?, ?)
                )
            )",
            [
                'GET', 401, '/api/app/version',
                'GET', 403,
                '/api/agente-upec-home/filtros',
                '/api/estadisticas-actividades/catalogos/unidades',
                '/api/estadisticas-actividades/catalogos/delegaciones',
                'POST', 503, '/api/whatsapp/webhook',
                'authenticated_rate_limit_reached', 429,
                '/api/me',
                '/api/location',
                '/api/location/response-route',
            ]
        );
    }

    public function intentLabel(): string
    {
        if (!empty($this->metadata['intent'])) {
            return (string) $this->metadata['intent'];
        }

        $path = '/' . ltrim((string) $this->path, '/');
        $method = strtoupper((string) $this->method);

        if ($method === 'GET' && $path === '/api/me') {
            return 'Actualizar la sesión, el perfil y los permisos de la aplicación.';
        }

        if ($method === 'GET' && preg_match('#^/api/comunicaciones/conversacion/(\\d+)$#', $path, $matches)) {
            return 'Consultar o actualizar la conversación con el usuario #' . $matches[1] . '.';
        }

        if ($method === 'POST' && $path === '/api/location') {
            return 'Enviar la ubicación actual del dispositivo.';
        }

        if ($method === 'POST' && $path === '/api/location/response-route') {
            return 'Sincronizar puntos de ruta o ubicación acumulados por el dispositivo.';
        }

        return [
            'GET' => 'Consultar un recurso de la aplicación.',
            'POST' => 'Enviar o crear información en la aplicación.',
            'PUT' => 'Actualizar información existente.',
            'PATCH' => 'Actualizar parcialmente información existente.',
            'DELETE' => 'Solicitar la eliminación de un recurso.',
        ][$method] ?? 'Realizar una operación en la aplicación.';
    }

    public function assessmentLabel(): string
    {
        if (in_array($this->event_code, ['authenticated_rate_limit_reached', 'rate_limit_exceeded'], true)
            && $this->user_id) {
            return 'Probablemente tráfico legítimo excesivo de la app; revisar frecuencia y cliente antes de bloquear.';
        }

        if ($this->event_code === 'suspicious_path_probe') {
            return 'Patrón compatible con escaneo automatizado; revisar reincidencia y otras rutas de la misma IP.';
        }

        if ($this->event_code === 'login_failed') {
            return 'No concluyente por sí solo; varios intentos o identidades aumentan el riesgo.';
        }

        return 'Revisar junto con usuario, ruta, frecuencia y eventos cercanos; un evento aislado no prueba intención maliciosa.';
    }
}
