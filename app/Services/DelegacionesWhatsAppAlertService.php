<?php

namespace App\Services;

use App\Models\Actividad;
use App\Models\Delegacion;
use App\Models\Hechos;
use App\Models\Lesionado;
use App\Models\PuestaDisposicion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DelegacionesWhatsAppAlertService
{
    private const UNIDAD_DELEGACIONES_ID = 2;

    private $whatsApp;

    public function __construct(WhatsAppCloudService $whatsApp)
    {
        $this->whatsApp = $whatsApp;
    }

    public function notificarFallecido(Lesionado $lesionado): void
    {
        $this->guard('fallecido', [
            'hecho_id' => $lesionado->hecho_id,
            'lesionado_id' => $lesionado->id,
        ], function () use ($lesionado) {
            if (!$this->esFallecido($lesionado->tipo_lesion ?? null)) {
                return;
            }

            $lesionado->loadMissing([
                'hecho.creator',
                'hecho.unidadOrganizacional',
                'hecho.delegacion',
            ]);

            if (!$lesionado->hecho) {
                return;
            }

            $this->sendToRecipients(
                'fallecido',
                $this->mensajeFallecido($lesionado, $lesionado->hecho),
                [
                    'hecho_id' => $lesionado->hecho_id,
                    'lesionado_id' => $lesionado->id,
                ]
            );
        });
    }

    public function notificarHechoTurnado(Hechos $hecho): void
    {
        $this->guard('hecho_turnado', [
            'hecho_id' => $hecho->id,
        ], function () use ($hecho) {
            if (!$this->hechoGeneraAlertaPuesta($hecho)) {
                return;
            }

            $hecho->loadMissing([
                'creator',
                'unidadOrganizacional',
                'delegacion',
                'lesionados',
            ]);

            $this->sendToRecipients(
                'hecho_turnado',
                $this->mensajeHechoTurnado($hecho),
                ['hecho_id' => $hecho->id]
            );
        });
    }

    public function notificarActividadConDetenidos(Actividad $actividad): void
    {
        $this->guard('actividad_detenidos', [
            'actividad_id' => $actividad->id,
        ], function () use ($actividad) {
            if ((int) ($actividad->personas_detenidas ?? 0) <= 0) {
                return;
            }

            $actividad->loadMissing([
                'categoria',
                'subcategoria',
                'unidad',
                'delegacion',
                'destacamento',
                'creador',
            ]);

            $this->sendActividadDetenidosTemplate($actividad);
        });
    }

    public function notificarPuestaDisposicion(PuestaDisposicion $puesta): void
    {
        $this->guard('puesta_disposicion', [
            'puesta_disposicion_id' => $puesta->id,
        ], function () use ($puesta) {
            $puesta->loadMissing([
                'hecho',
                'personas',
                'vehiculos',
                'objetos',
                'unidad',
                'delegacion',
                'destacamento',
                'creador',
            ]);

            if (!$this->puestaGeneraAlertaDelegaciones($puesta)) {
                return;
            }

            $this->sendToRecipients(
                'puesta_disposicion',
                $this->mensajePuestaDisposicion($puesta),
                ['puesta_disposicion_id' => $puesta->id]
            );
        });
    }

    public function notificarHechoIncompleto(Hechos $hecho, int $minutosPendiente): void
    {
        $this->guard('hecho_incompleto', [
            'hecho_id' => $hecho->id,
        ], function () use ($hecho, $minutosPendiente) {
            $hecho->loadMissing([
                'creator',
                'unidadOrganizacional',
                'delegacion',
            ]);

            if ($hecho->capturaCompletaCalculada()) {
                return;
            }

            $this->sendHechoIncompletoTemplate($hecho, $minutosPendiente);
        });
    }

    public function notificarHechoPendienteSinResguardo(Hechos $hecho, int $minutosPendiente): void
    {
        $this->guard('hecho_pendiente_sin_resguardo', [
            'hecho_id' => $hecho->id,
        ], function () use ($hecho, $minutosPendiente) {
            $hecho->loadMissing([
                'creator',
                'unidadOrganizacional',
                'delegacion',
                'vehiculos',
            ]);

            if ($this->upper($hecho->situacion ?? null) !== 'PENDIENTE') {
                return;
            }

            if ($hecho->capturaCompletaCalculada()) {
                return;
            }

            if ($this->hechoTieneVehiculosResguardados($hecho)) {
                return;
            }

            $this->sendHechoIncompletoTemplate(
                $hecho,
                $minutosPendiente,
                'hecho_pendiente_sin_resguardo',
                $this->detallePendienteSinResguardo($hecho),
                false
            );
        });
    }

    public function destinatariosHechoIncompleto(Hechos $hecho, bool $incluirDelegadosDesdeUsuarios = true): array
    {
        $hecho->loadMissing('delegacion.padre');

        $recipients = $this->recipients();

        if ((bool) config('services.whatsapp.delegaciones.incompletos_notify_delegados', true)) {
            $recipients = array_merge(
                $recipients,
                $this->configuredDelegacionRecipients($hecho)
            );

            if ($incluirDelegadosDesdeUsuarios) {
                $recipients = array_merge($recipients, $this->delegadoUserRecipients($hecho));
            }
        }

        return $this->uniqueNumbers($recipients);
    }

    public function debeNotificarNuevaPuestaHecho(?Hechos $antes, Hechos $actual): bool
    {
        if (!$this->hechoGeneraAlertaPuesta($actual)) {
            return false;
        }

        if (!$antes || !$this->hechoGeneraAlertaPuesta($antes)) {
            return true;
        }

        return (int) ($actual->personas_mp ?? 0) > (int) ($antes->personas_mp ?? 0)
            || (int) ($actual->vehiculos_mp ?? 0) > (int) ($antes->vehiculos_mp ?? 0);
    }

    public function debeNotificarActividadConDetenidos(int $detenidosAntes, Actividad $actividad): bool
    {
        return (int) ($actividad->personas_detenidas ?? 0) > max(0, $detenidosAntes);
    }

    public function esFallecido($tipoLesion): bool
    {
        return $this->upper($tipoLesion) === 'FALLECIDO';
    }

    public function hechoGeneraAlertaPuesta(Hechos $hecho): bool
    {
        return $this->upper($hecho->situacion ?? null) === 'TURNADO'
            && (int) ($hecho->unidad_org_id ?? 0) === self::UNIDAD_DELEGACIONES_ID;
    }

    private function puestaGeneraAlertaDelegaciones(PuestaDisposicion $puesta): bool
    {
        if ((int) ($puesta->unidad_id ?? 0) === self::UNIDAD_DELEGACIONES_ID) {
            return true;
        }

        $puesta->loadMissing('hecho');

        return $puesta->hecho
            && (int) ($puesta->hecho->unidad_org_id ?? 0) === self::UNIDAD_DELEGACIONES_ID;
    }

    private function guard(string $event, array $context, callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            Log::error('Error preparando WhatsApp alerta delegaciones', [
                'event' => $event,
                'context' => $context,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendToRecipients(string $event, string $message, array $context = []): void
    {
        $recipients = $this->recipients();

        if (empty($recipients)) {
            Log::warning('WhatsApp alertas delegaciones sin destinatarios', [
                'event' => $event,
                'context' => $context,
            ]);

            return;
        }

        foreach ($recipients as $to) {
            try {
                $response = $this->whatsApp->sendText($to, $message);

                Log::info('WhatsApp alerta delegaciones enviada', [
                    'event' => $event,
                    'to' => $to,
                    'ok' => $response['ok'] ?? null,
                    'status' => $response['status'] ?? null,
                    'context' => $context,
                ]);
            } catch (\Throwable $e) {
                Log::error('Error enviando WhatsApp alerta delegaciones', [
                    'event' => $event,
                    'to' => $to,
                    'context' => $context,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function sendActividadDetenidosTemplate(Actividad $actividad): void
    {
        $recipients = $this->recipients();

        if (empty($recipients)) {
            Log::warning('WhatsApp alertas delegaciones sin destinatarios', [
                'event' => 'actividad_detenidos',
                'context' => [
                    'actividad_id' => $actividad->id,
                ],
            ]);

            return;
        }

        $params = [
            $this->valorTemplate($actividad->nombre ?? optional($actividad->categoria)->nombre ?? 'Actividad registrada'),
            $this->valorTemplate($this->fechaHora($actividad->fecha ?? null, $actividad->hora ?? null)),
            $this->valorTemplate(optional($actividad->unidad)->nombre),
            $this->valorTemplate(optional($actividad->delegacion)->nombre),
            $this->valorTemplate($this->ubicacionActividad($actividad)),
            (string) (int) ($actividad->personas_detenidas ?? 0),
            $this->valorTemplate($this->preview($actividad->motivo ?? null, 180)),
            $this->valorTemplate(optional($actividad->creador)->name),
        ];

        foreach ($recipients as $to) {
            try {
                $response = $this->whatsApp->sendTemplate(
                    $to,
                    'alerta_actividad_detenidos',
                    $params,
                    'es_MX'
                );

                Log::info('WhatsApp alerta delegaciones template enviada', [
                    'event' => 'actividad_detenidos',
                    'template' => 'alerta_actividad_detenidos',
                    'to' => $to,
                    'ok' => $response['ok'] ?? null,
                    'status' => $response['status'] ?? null,
                    'context' => [
                        'actividad_id' => $actividad->id,
                    ],
                ]);
            } catch (\Throwable $e) {
                Log::error('Error enviando WhatsApp alerta delegaciones template', [
                    'event' => 'actividad_detenidos',
                    'template' => 'alerta_actividad_detenidos',
                    'to' => $to,
                    'context' => [
                        'actividad_id' => $actividad->id,
                    ],
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function sendHechoIncompletoTemplate(
        Hechos $hecho,
        int $minutosPendiente,
        string $event = 'hecho_incompleto',
        ?string $detallePendiente = null,
        bool $incluirDelegadosDesdeUsuarios = true
    ): void
    {
        $recipients = $this->destinatariosHechoIncompleto($hecho, $incluirDelegadosDesdeUsuarios);
        $template = (string) config(
            'services.whatsapp.delegaciones.incompletos_template',
            'alerta_hecho_incompleto_delegaciones'
        );

        if (empty($recipients)) {
            Log::warning('WhatsApp alertas delegaciones sin destinatarios', [
                'event' => $event,
                'template' => $template,
                'context' => [
                    'hecho_id' => $hecho->id,
                    'delegacion_id' => $hecho->delegacion_id,
                ],
            ]);

            return;
        }

        if ($template === '') {
            Log::warning('WhatsApp alerta delegaciones sin template de incompletos', [
                'event' => $event,
                'context' => [
                    'hecho_id' => $hecho->id,
                    'delegacion_id' => $hecho->delegacion_id,
                ],
            ]);

            return;
        }

        $faltantes = implode(', ', $hecho->faltantesCapturaTexto());
        $pendiente = $detallePendiente ?: ($faltantes !== '' ? $faltantes : 'Revisar captura pendiente');

        $params = [
            '#' . $hecho->id,
            $this->valorTemplate($this->fechaHora($hecho->fecha ?? null, $hecho->hora ?? null)),
            $this->valorTemplate(optional($hecho->delegacion)->nombre ?: optional($hecho->unidadOrganizacional)->nombre),
            $this->valorTemplate($hecho->tipo_hecho ?? null),
            $this->valorTemplate($this->ubicacionHecho($hecho)),
            $this->valorTemplate($this->formatoDuracion($minutosPendiente)),
            $this->valorTemplate($pendiente),
            $this->valorTemplate(optional($hecho->creator)->name),
        ];

        foreach ($recipients as $to) {
            try {
                $response = $this->whatsApp->sendTemplate(
                    $to,
                    $template,
                    $params,
                    'es_MX'
                );

                Log::info('WhatsApp alerta delegaciones template enviada', [
                    'event' => $event,
                    'template' => $template,
                    'to' => $to,
                    'ok' => $response['ok'] ?? null,
                    'status' => $response['status'] ?? null,
                    'context' => [
                        'hecho_id' => $hecho->id,
                        'delegacion_id' => $hecho->delegacion_id,
                        'minutos_pendiente' => $minutosPendiente,
                    ],
                ]);
            } catch (\Throwable $e) {
                Log::error('Error enviando WhatsApp alerta delegaciones template', [
                    'event' => $event,
                    'template' => $template,
                    'to' => $to,
                    'context' => [
                        'hecho_id' => $hecho->id,
                        'delegacion_id' => $hecho->delegacion_id,
                        'minutos_pendiente' => $minutosPendiente,
                    ],
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function hechoTieneVehiculosResguardados(Hechos $hecho): bool
    {
        $hecho->loadMissing('vehiculos');

        return $hecho->vehiculos->contains(function ($vehiculo) {
            return method_exists($vehiculo, 'tieneCorralonValido')
                ? $vehiculo->tieneCorralonValido()
                : trim((string) ($vehiculo->corralon ?? '')) !== '';
        });
    }

    private function detallePendienteSinResguardo(Hechos $hecho): string
    {
        $hecho->loadMissing('vehiculos');
        $totalVehiculos = $hecho->vehiculos->count();

        if ($totalVehiculos === 0) {
            return 'Hecho pendiente sin vehiculos capturados ni resguardados en corralon';
        }

        return $totalVehiculos === 1
            ? 'Hecho pendiente con 1 vehiculo relacionado sin resguardo en corralon'
            : 'Hecho pendiente con ' . $totalVehiculos . ' vehiculos relacionados sin resguardo en corralon';
    }

    private function recipients(): array
    {
        $configured = (string) config('services.whatsapp.delegaciones.alertas_to', '');
        return $this->numbersFromText($configured);
    }

    private function configuredDelegacionRecipients(Hechos $hecho): array
    {
        $delegacion = $hecho->delegacion;

        if (!$delegacion) {
            return [];
        }

        $map = $this->delegacionRecipientsMap(
            (string) config('services.whatsapp.delegaciones.incompletos_delegados_to', '')
        );

        if (empty($map)) {
            return [];
        }

        $numbers = [];

        foreach ($this->delegacionLookupKeys($delegacion) as $key) {
            if (isset($map[$key])) {
                $numbers = array_merge($numbers, $map[$key]);
            }
        }

        return $this->uniqueNumbers($numbers);
    }

    private function delegadoUserRecipients(Hechos $hecho): array
    {
        if (!(bool) config('services.whatsapp.delegaciones.incompletos_delegados_from_users', true)) {
            return [];
        }

        $delegacion = $hecho->delegacion;

        if (!$delegacion) {
            return [];
        }

        $delegacionIds = $this->delegacionIdsParaDelegado($delegacion);
        $roles = $this->delegadoRoleNames();

        if (empty($delegacionIds) || empty($roles)) {
            return [];
        }

        try {
            $numbers = User::query()
                ->whereNotNull('telefono')
                ->where('telefono', '<>', '')
                ->where(function ($query) use ($delegacionIds) {
                    $query->whereIn('delegacion_id', $delegacionIds)
                        ->orWhereIn('id', function ($subQuery) use ($delegacionIds) {
                            $subQuery->select('user_id')
                                ->from('delegacion_user')
                                ->whereIn('delegacion_id', $delegacionIds);
                        });
                })
                ->whereHas('roles', function ($query) use ($roles) {
                    $query->whereIn('name', $roles);
                })
                ->where(function ($query) {
                    $query->whereNull('estado')
                        ->orWhereRaw('UPPER(TRIM(estado)) <> ?', ['INACTIVO']);
                })
                ->pluck('telefono')
                ->all();
        } catch (\Throwable $e) {
            Log::warning('No se pudieron resolver delegados para WhatsApp incompleto', [
                'hecho_id' => $hecho->id,
                'delegacion_id' => $hecho->delegacion_id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }

        return $this->uniqueNumbers($numbers);
    }

    private function delegacionRecipientsMap(string $configured): array
    {
        $entries = preg_split('/[;\r\n]+/', $configured, -1, PREG_SPLIT_NO_EMPTY);
        $map = [];

        foreach ($entries ?: [] as $entry) {
            $separator = strpos($entry, '=');

            if ($separator === false) {
                $separator = strpos($entry, ':');
            }

            if ($separator === false) {
                continue;
            }

            $key = $this->delegacionKey(substr($entry, 0, $separator));
            $numbers = $this->numbersFromText(substr($entry, $separator + 1));

            if ($key === '' || empty($numbers)) {
                continue;
            }

            $map[$key] = $this->uniqueNumbers(array_merge($map[$key] ?? [], $numbers));
        }

        return $map;
    }

    private function delegacionLookupKeys(Delegacion $delegacion): array
    {
        $delegacion->loadMissing('padre');

        $values = [
            $delegacion->id,
            $delegacion->clave,
            $delegacion->nombre,
            $delegacion->municipio,
        ];

        if ($delegacion->padre) {
            $values[] = $delegacion->padre->id;
            $values[] = $delegacion->padre->clave;
            $values[] = $delegacion->padre->nombre;
            $values[] = $delegacion->padre->municipio;
        }

        $keys = [];

        foreach ($values as $value) {
            $key = $this->delegacionKey($value);

            if ($key !== '') {
                $keys[] = $key;
            }
        }

        return array_values(array_unique($keys));
    }

    private function delegacionIdsParaDelegado(Delegacion $delegacion): array
    {
        $ids = [(int) $delegacion->id];

        if (!empty($delegacion->delegacion_padre_id)) {
            $ids[] = (int) $delegacion->delegacion_padre_id;
        }

        return array_values(array_unique(array_filter($ids)));
    }

    private function delegadoRoleNames(): array
    {
        $configured = (string) config('services.whatsapp.delegaciones.incompletos_delegado_roles', 'Delegado');
        $parts = preg_split('/[,;|]+/', $configured, -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_unique(array_filter(array_map('trim', $parts ?: []))));
    }

    private function numbersFromText(string $configured): array
    {
        $parts = preg_split('/[\s,;|]+/', $configured, -1, PREG_SPLIT_NO_EMPTY);

        return $this->uniqueNumbers($parts ?: []);
    }

    private function uniqueNumbers(array $values): array
    {
        $numbers = [];

        foreach ($values as $value) {
            $number = preg_replace('/\D+/', '', (string) $value);

            if ($number !== '') {
                $numbers[] = $number;
            }
        }

        return array_values(array_unique($numbers));
    }

    private function delegacionKey($value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        $map = [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'á' => 'A', 'é' => 'E', 'í' => 'I', 'ó' => 'O', 'ú' => 'U',
            'Ñ' => 'N', 'ñ' => 'N',
        ];

        $value = strtr($value, $map);

        return strtolower(preg_replace('/[^A-Za-z0-9]+/', '', $value));
    }

    private function mensajeFallecido(Lesionado $lesionado, Hechos $hecho): string
    {
        $lines = [
            'GUARDIA CIVIL',
            'ALERTA DELEGACIONES',
            '',
            'FALLECIDO REGISTRADO',
        ];

        $this->appendHechoResumen($lines, $hecho);
        $this->appendLine($lines, 'Fallecido', $lesionado->nombre ?? null);
        $this->appendLine($lines, 'Edad', $lesionado->edad ? (string) $lesionado->edad : null);
        $this->appendLine($lines, 'Sexo', $lesionado->sexo ?? null);
        $this->appendLine($lines, 'Paramedico', $lesionado->paramedico ?? null);
        $this->appendLine($lines, 'Ambulancia', $lesionado->ambulancia ?? null);
        $this->appendLine($lines, 'Observaciones', $this->preview($lesionado->observaciones ?? null));

        return implode("\n", $lines);
    }

    private function mensajeHechoTurnado(Hechos $hecho): string
    {
        $lines = [
            'GUARDIA CIVIL',
            'ALERTA DELEGACIONES',
            '',
            'PUESTA A DISPOSICION POR HECHO',
        ];

        $this->appendHechoResumen($lines, $hecho);
        $this->appendLine($lines, 'Oficio MP', $hecho->oficio_mp ?? null);
        $this->appendLine($lines, 'Personas MP', (string) (int) ($hecho->personas_mp ?? 0));
        $this->appendLine($lines, 'Vehiculos MP', (string) (int) ($hecho->vehiculos_mp ?? 0));

        return implode("\n", $lines);
    }

    private function mensajeActividadConDetenidos(Actividad $actividad): string
    {
        $lines = [
            'GUARDIA CIVIL',
            'ALERTA DELEGACIONES',
            '',
            'DETENIDOS EN ACTIVIDAD',
        ];

        $this->appendLine($lines, 'Actividad ID', (string) $actividad->id);
        $this->appendLine($lines, 'Fecha/hora', $this->fechaHora($actividad->fecha ?? null, $actividad->hora ?? null));
        $this->appendLine($lines, 'Unidad', optional($actividad->unidad)->nombre);
        $this->appendLine($lines, 'Delegacion', optional($actividad->delegacion)->nombre);
        $this->appendLine($lines, 'Destacamento', optional($actividad->destacamento)->nombre);
        $this->appendLine($lines, 'Categoria', optional($actividad->categoria)->nombre);
        $this->appendLine($lines, 'Subcategoria', optional($actividad->subcategoria)->nombre);
        $this->appendLine($lines, 'Lugar', $this->ubicacionActividad($actividad));
        $this->appendLine($lines, 'Personas detenidas', (string) (int) ($actividad->personas_detenidas ?? 0));
        $this->appendLine($lines, 'Motivo', $this->preview($actividad->motivo ?? null));
        $this->appendLine($lines, 'Narrativa', $this->preview($actividad->narrativa ?? null));
        $this->appendLine($lines, 'Capturo', optional($actividad->creador)->name);
        $this->appendMaps($lines, $actividad->lat ?? null, $actividad->lng ?? null);

        return implode("\n", $lines);
    }

    private function mensajePuestaDisposicion(PuestaDisposicion $puesta): string
    {
        $lines = [
            'GUARDIA CIVIL',
            'ALERTA DELEGACIONES',
            '',
            'PUESTA A DISPOSICION REGISTRADA',
        ];

        $this->appendLine($lines, 'Folio', trim((string) ($puesta->numero_puesta ?? '')) . '/' . trim((string) ($puesta->anio ?? '')));
        $this->appendLine($lines, 'Fecha/hora', $this->fechaHora($puesta->fecha_puesta ?? null, $puesta->hora_puesta ?? null));
        $this->appendLine($lines, 'Unidad', optional($puesta->unidad)->nombre ?: ($puesta->area ?? null));
        $this->appendLine($lines, 'Delegacion', optional($puesta->delegacion)->nombre);
        $this->appendLine($lines, 'Destacamento', optional($puesta->destacamento)->nombre);
        $this->appendLine($lines, 'Tipo', $puesta->tipo_puesta ?? null);
        $this->appendLine($lines, 'Motivo', $puesta->motivo ?? null);
        $this->appendLine($lines, 'Personas', (string) $puesta->personas->count());
        $this->appendLine($lines, 'Vehiculos', (string) $puesta->vehiculos->count());
        $this->appendLine($lines, 'Objetos', (string) $puesta->objetos->count());
        $this->appendLine($lines, 'Policia', $puesta->nombre_policia ?? null);
        $this->appendLine($lines, 'Autoridad receptora', $puesta->autoridad_receptora ?? null);
        $this->appendLine($lines, 'Lugar', $puesta->lugar_puesta ?? null);
        $this->appendLine($lines, 'Narrativa', $this->preview($puesta->narrativa ?? null));
        $this->appendLine($lines, 'Capturo', optional($puesta->creador)->name);

        return implode("\n", $lines);
    }

    private function appendHechoResumen(array &$lines, Hechos $hecho): void
    {
        $this->appendLine($lines, 'Hecho ID', (string) $hecho->id);
        $this->appendLine($lines, 'Folio C5i', $hecho->folio_c5i ?? null);
        $this->appendLine($lines, 'Fecha/hora', $this->fechaHora($hecho->fecha ?? null, $hecho->hora ?? null));
        $this->appendLine($lines, 'Unidad', optional($hecho->unidadOrganizacional)->nombre);
        $this->appendLine($lines, 'Delegacion', optional($hecho->delegacion)->nombre);
        $this->appendLine($lines, 'Tipo de hecho', $hecho->tipo_hecho ?? null);
        $this->appendLine($lines, 'Situacion', $hecho->situacion ?? null);
        $this->appendLine($lines, 'Lugar', $this->ubicacionHecho($hecho));
        $this->appendLine($lines, 'Capturo', optional($hecho->creator)->name);
        $this->appendMaps($lines, $hecho->lat ?? null, $hecho->lng ?? null);
    }

    private function appendLine(array &$lines, string $label, $value): void
    {
        $value = trim((string) $value);

        if ($value === '') {
            return;
        }

        $lines[] = "{$label}: {$value}";
    }

    private function appendMaps(array &$lines, $lat, $lng): void
    {
        if (!is_numeric($lat) || !is_numeric($lng)) {
            return;
        }

        $lines[] = 'Google Maps: https://www.google.com/maps?q=' . $lat . ',' . $lng;
    }

    private function fechaHora($fecha, $hora): ?string
    {
        $fechaTexto = '';

        if (!empty($fecha)) {
            try {
                $fechaTexto = Carbon::parse($fecha)->format('d/m/Y');
            } catch (\Throwable $e) {
                $fechaTexto = substr((string) $fecha, 0, 10);
            }
        }

        $horaTexto = '';

        if (!empty($hora)) {
            if ($hora instanceof \DateTimeInterface) {
                $horaTexto = $hora->format('H:i');
            } elseif (preg_match('/\b(\d{2}:\d{2})/', (string) $hora, $match)) {
                $horaTexto = $match[1];
            }
        }

        $result = trim($fechaTexto . ' ' . $horaTexto);

        return $result !== '' ? $result : null;
    }

    private function ubicacionHecho(Hechos $hecho): ?string
    {
        $parts = array_filter([
            $hecho->calle ?? null,
            !empty($hecho->colonia) ? 'COL. ' . $hecho->colonia : null,
            $hecho->municipio ?? null,
        ]);

        $ubicacion = trim(implode(', ', $parts));

        if ($ubicacion === '') {
            $ubicacion = trim((string) ($hecho->ubicacion_formateada ?? ''));
        }

        return $ubicacion !== '' ? $ubicacion : null;
    }

    private function ubicacionActividad(Actividad $actividad): ?string
    {
        $parts = array_filter([
            $actividad->lugar ?? null,
            $actividad->municipio ?? null,
            $actividad->carretera ?? null,
            $actividad->tramo ?? null,
            $actividad->kilometro ?? null,
        ]);

        $ubicacion = trim(implode(', ', $parts));

        return $ubicacion !== '' ? $ubicacion : null;
    }

    private function preview($value, int $limit = 350): ?string
    {
        $text = preg_replace('/\s+/', ' ', trim((string) $value));

        if ($text === '') {
            return null;
        }

        if (mb_strlen($text, 'UTF-8') <= $limit) {
            return $text;
        }

        return mb_substr($text, 0, $limit - 3, 'UTF-8') . '...';
    }

    private function formatoDuracion(int $minutos): string
    {
        $minutos = max(0, $minutos);
        $horas = intdiv($minutos, 60);
        $resto = $minutos % 60;

        if ($horas <= 0) {
            return $resto . ' min';
        }

        if ($resto <= 0) {
            return $horas . ' h';
        }

        return $horas . ' h ' . $resto . ' min';
    }

    private function valorTemplate($value): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : 'No especificado';
    }

    private function upper($value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        $map = [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'á' => 'A', 'é' => 'E', 'í' => 'I', 'ó' => 'O', 'ú' => 'U',
            'Ñ' => 'N', 'ñ' => 'N',
        ];

        return mb_strtoupper(strtr($value, $map), 'UTF-8');
    }
}
