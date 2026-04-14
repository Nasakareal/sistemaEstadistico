<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hechos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode') ?? $request->query('hub.mode');
        $token = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
        $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge');

        $verifyToken = (string) config('services.whatsapp.verify_token');

        if ($mode === 'subscribe' && $token !== '' && hash_equals($verifyToken, (string) $token)) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    public function handle(Request $request)
    {
        $payload = $request->all();

        Log::info('WA Cloud webhook recibido', [
            'object' => $payload['object'] ?? null,
            'entries_count' => isset($payload['entry']) && is_array($payload['entry']) ? count($payload['entry']) : 0,
        ]);

        foreach (($payload['entry'] ?? []) as $entry) {
            foreach (($entry['changes'] ?? []) as $change) {
                $value = $change['value'] ?? [];
                $metadata = $value['metadata'] ?? [];

                foreach (($value['messages'] ?? []) as $message) {
                    Log::info('WA mensaje recibido', [
                        'from' => $message['from'] ?? null,
                        'id' => $message['id'] ?? null,
                        'timestamp' => $message['timestamp'] ?? null,
                        'type' => $message['type'] ?? null,
                        'text' => $message['text']['body'] ?? null,
                        'display_phone_number' => $metadata['display_phone_number'] ?? null,
                        'phone_number_id' => $metadata['phone_number_id'] ?? null,
                    ]);

                    $type = (string) ($message['type'] ?? '');
                    $from = (string) ($message['from'] ?? '');
                    $text = trim((string) ($message['text']['body'] ?? ''));

                    if ($from !== '' && $type === 'text' && $text !== '') {
                        $this->processIncomingText($from, $text);
                    }
                }

                foreach (($value['statuses'] ?? []) as $status) {
                    Log::info('WA estado mensaje', [
                        'id' => $status['id'] ?? null,
                        'status' => $status['status'] ?? null,
                        'timestamp' => $status['timestamp'] ?? null,
                        'recipient_id' => $status['recipient_id'] ?? null,
                        'conversation_id' => $status['conversation']['id'] ?? null,
                        'conversation_origin' => $status['conversation']['origin']['type'] ?? null,
                        'pricing_billable' => $status['pricing']['billable'] ?? null,
                        'pricing_category' => $status['pricing']['category'] ?? null,
                        'pricing_model' => $status['pricing']['pricing_model'] ?? null,
                        'error_code' => $status['errors'][0]['code'] ?? null,
                        'error_title' => $status['errors'][0]['title'] ?? null,
                        'error_message' => $status['errors'][0]['message'] ?? null,
                        'display_phone_number' => $metadata['display_phone_number'] ?? null,
                        'phone_number_id' => $metadata['phone_number_id'] ?? null,
                    ]);
                }
            }
        }

        return response()->json(['ok' => true], 200);
    }

    protected function processIncomingText(string $from, string $text): void
    {
        $original = trim($text);
        $normalized = mb_strtoupper($original, 'UTF-8');

        if (preg_match('/^PLACAS\s+(.+)$/u', $normalized, $matches)) {
            $placa = trim($matches[1]);
            $this->replyBusquedaPorPlacas($from, $placa);
            return;
        }

        if (preg_match('/^DETALLE\s+([A-Z0-9\/\-\._]+)$/u', $normalized, $matches)) {
            $folio = trim($matches[1]);
            $this->replyDetallePorFolio($from, $folio);
            return;
        }

        $this->sendText($from, "Escribe:\nPLACAS ABC123\n\nO:\nDETALLE 59564");
    }

    protected function replyBusquedaPorPlacas(string $from, string $placa): void
    {
        $resultados = $this->buscarHechosPorPlaca($placa);

        if (count($resultados) === 0) {
            $this->sendText($from, "No encontré hechos con las placas {$placa}.");
            return;
        }

        $lines = [];
        $lines[] = 'Encontré ' . count($resultados) . " hecho(s) con las placas {$placa}:";
        $lines[] = '';

        foreach ($resultados as $item) {
            $lines[] = "{$item['id']} | {$item['folio']} | {$item['fecha']} {$item['hora']} | {$item['tipo']} | {$item['estado']}";
        }

        $lines[] = '';
        $lines[] = 'Responde:';
        $lines[] = 'DETALLE ' . $resultados[0]['id'];

        $this->sendText($from, implode("\n", $lines));
    }

    protected function replyDetallePorFolio(string $from, string $folio): void
    {
        $detalle = $this->obtenerDetalleHechoPorFolio($folio);

        if (!$detalle) {
            $this->sendText($from, "No encontré el hecho {$folio}.");
            return;
        }

        $texto = implode("\n", array_filter([
            'GUARDIA CIVIL',
            '',
            $detalle['coordinacion'] ?? null,
            '',
            $detalle['unidad'] ?? null,
            '',
            $detalle['municipio'] ?? null,
            '',
            $detalle['sector'] ?? null,
            '',
            'TEMA: ' . ($detalle['tema'] ?? 'HECHO DE TRÁNSITO'),
            '',
            $detalle['descripcion'] ?? null,
            '',
            isset($detalle['vehiculo']) ? 'VEHÍCULO A)' : null,
            $detalle['vehiculo'] ?? null,
            '',
            isset($detalle['grua']) ? 'Grúa: ' . $detalle['grua'] : null,
            isset($detalle['corralon']) ? 'Corralón: ' . $detalle['corralon'] : null,
            isset($detalle['servicio']) ? 'Servicio: ' . $detalle['servicio'] : null,
            '',
            isset($detalle['estado']) ? 'Hecho ' . $detalle['estado'] . '.' : null,
            '',
            isset($detalle['ubicacion']) ? 'Ubicación: ' . $detalle['ubicacion'] : null,
            isset($detalle['google_maps']) ? 'Google Maps: ' . $detalle['google_maps'] : null,
            '',
            isset($detalle['informa']) ? 'INFORMA ' . $detalle['informa'] : null,
        ]));

        $this->sendText($from, $texto);

        foreach (($detalle['fotos'] ?? []) as $foto) {
            if (!empty($foto)) {
                $this->sendImage($from, $foto);
            }
        }
    }

    protected function sendText(string $to, string $text): array
    {
        $config = $this->getWhatsAppConfig();

        if ($config['phone_number_id'] === '' || $config['token'] === '') {
            Log::warning('WA sendText sin configuración', [
                'to' => $to,
            ]);

            return [
                'ok' => false,
                'status' => 0,
                'body' => ['error' => 'Configuración incompleta de WhatsApp.'],
            ];
        }

        $response = Http::withToken($config['token'])
            ->post("https://graph.facebook.com/{$config['graph_version']}/{$config['phone_number_id']}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $text,
                ],
            ]);

        $json = $response->json();

        Log::info('WA sendText response', [
            'to' => $to,
            'status' => $response->status(),
            'body' => $json,
        ]);

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'body' => $json,
        ];
    }

    protected function sendImage(string $to, string $imageUrl): array
    {
        $config = $this->getWhatsAppConfig();

        if ($config['phone_number_id'] === '' || $config['token'] === '') {
            Log::warning('WA sendImage sin configuración', [
                'to' => $to,
                'imageUrl' => $imageUrl,
            ]);

            return [
                'ok' => false,
                'status' => 0,
                'body' => ['error' => 'Configuración incompleta de WhatsApp.'],
            ];
        }

        $response = Http::withToken($config['token'])
            ->post("https://graph.facebook.com/{$config['graph_version']}/{$config['phone_number_id']}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'image',
                'image' => [
                    'link' => $imageUrl,
                ],
            ]);

        $json = $response->json();

        Log::info('WA sendImage response', [
            'to' => $to,
            'status' => $response->status(),
            'body' => $json,
        ]);

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'body' => $json,
        ];
    }

    public function sendTemplate(string $to, array $data): array
    {
        $config = $this->getWhatsAppConfig();

        if ($config['phone_number_id'] === '' || $config['token'] === '') {
            Log::warning('WA sendTemplate sin configuración', [
                'to' => $to,
                'template' => $data['template'] ?? 'notificacion_hecho_vial',
            ]);

            return [
                'ok' => false,
                'status' => 0,
                'body' => ['error' => 'Configuración incompleta de WhatsApp.'],
            ];
        }

        $templateName = (string) ($data['template'] ?? 'notificacion_hecho_vial');
        $languageCode = (string) ($data['language'] ?? 'es_MX');

        $parameters = [];
        foreach (($data['parameters'] ?? []) as $parameter) {
            $parameters[] = [
                'type' => 'text',
                'text' => (string) $parameter,
            ];
        }

        $response = Http::withToken($config['token'])
            ->post("https://graph.facebook.com/{$config['graph_version']}/{$config['phone_number_id']}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => [
                        'code' => $languageCode,
                    ],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => $parameters,
                        ],
                    ],
                ],
            ]);

        $json = $response->json();

        Log::info('WA sendTemplate response', [
            'to' => $to,
            'status' => $response->status(),
            'body' => $json,
        ]);

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'body' => $json,
        ];
    }

    public function sendHechoTemplate(string $to, Hechos $hecho): array
    {
        $hecho->loadMissing('vehiculos');

        $ubicacion = trim(implode(', ', array_filter([
            $hecho->calle,
            $hecho->colonia ? 'col. ' . $hecho->colonia : null,
        ])));

        $fechaHora = trim(implode(' ', array_filter([
            !empty($hecho->fecha) ? optional($hecho->fecha)->format('Y-m-d') ?: (string) $hecho->fecha : null,
            $this->formatearHora((string) $hecho->hora),
        ])));

        return $this->sendTemplate($to, [
            'template' => 'notificacion_hecho_vial',
            'language' => 'es_MX',
            'parameters' => [
                $ubicacion !== '' ? $ubicacion : 'SIN UBICACIÓN',
                $fechaHora !== '' ? $fechaHora : 'SIN FECHA',
                (string) ($hecho->tipo_hecho ?: 'SIN TIPO'),
                (string) ($hecho->situacion ?: 'SIN ESTADO'),
                (string) ($hecho->folio_c5i ?: $hecho->id),
            ],
        ]);
    }

    protected function buscarHechosPorPlaca(string $placa): array
    {
        $placaNormalizada = $this->normalizarPlaca($placa);

        $hechos = Hechos::query()
            ->with(['vehiculos'])
            ->whereHas('vehiculos', function ($query) use ($placaNormalizada) {
                $query->whereRaw(
                    "REPLACE(REPLACE(REPLACE(UPPER(placas), '-', ''), ' ', ''), '.', '') = ?",
                    [$placaNormalizada]
                );
            })
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->limit(10)
            ->get();

        return $hechos->map(function (Hechos $hecho) use ($placaNormalizada) {
            $vehiculo = $hecho->vehiculos->first(function ($vehiculo) use ($placaNormalizada) {
                return $this->normalizarPlaca((string) $vehiculo->placas) === $placaNormalizada;
            }) ?? $hecho->vehiculos->first();

            return [
                'id' => $hecho->id,
                'folio' => $hecho->folio_c5i ?: $hecho->id,
                'fecha' => optional($hecho->fecha)->format('Y-m-d') ?: (string) $hecho->fecha,
                'hora' => $this->formatearHora((string) $hecho->hora),
                'tipo' => (string) $hecho->tipo_hecho,
                'estado' => (string) ($hecho->situacion ?: 'SIN ESTADO'),
                'placas' => (string) ($vehiculo->placas ?? ''),
            ];
        })->values()->all();
    }

    protected function obtenerDetalleHechoPorFolio(string $folio): ?array
    {
        $hecho = Hechos::query()
            ->with(['vehiculos'])
            ->where(function ($query) use ($folio) {
                $query->where('id', $folio)
                    ->orWhere('folio_c5i', $folio);
            })
            ->first();

        if (!$hecho) {
            return null;
        }

        $vehiculo = $hecho->vehiculos->first();

        $ubicacionPartes = array_filter([
            $hecho->calle,
            $hecho->colonia ? 'col. ' . $hecho->colonia : null,
        ]);

        $descripcion = trim(implode(' ', array_filter([
            optional($hecho->fecha)->format('Y-m-d') ?: (string) $hecho->fecha,
            $this->formatearHora((string) $hecho->hora),
            'Hrs. Guardia Civil toma conocimiento en',
            implode(', ', $ubicacionPartes) . '.',
        ])));

        $vehiculoTexto = null;
        $grua = null;
        $corralon = null;
        $servicio = null;

        if ($vehiculo) {
            $vehiculoTexto = trim(implode(' ', array_filter([
                'De la marca ' . $this->valorONoEspecificado($vehiculo->marca) . ',',
                'tipo ' . $this->valorONoEspecificado($vehiculo->tipo) . ',',
                $vehiculo->linea ? 'línea ' . $vehiculo->linea . ',' : null,
                $vehiculo->color ? 'color ' . $vehiculo->color . ',' : null,
                $vehiculo->placas ? 'placas ' . $vehiculo->placas . ',' : null,
                $vehiculo->serie ? 'NIV ' . $vehiculo->serie . '.' : null,
            ])));

            $grua = $vehiculo->grua ?: null;
            $corralon = $vehiculo->corralon ?: null;

            $servicioPartes = array_filter([
                'vehículo_id ' . $vehiculo->id,
                $vehiculo->tipo ? 'tipo ' . $vehiculo->tipo : null,
            ]);

            $servicio = count($servicioPartes) > 0 ? implode(', ', $servicioPartes) . '.' : null;
        }

        $lat = $hecho->lat;
        $lng = $hecho->lng;
        $googleMaps = null;
        $ubicacion = null;

        if (!is_null($lat) && !is_null($lng) && $lat !== '' && $lng !== '') {
            $ubicacion = "{$lat}, {$lng}";
            $googleMaps = "https://www.google.com/maps?q={$lat},{$lng}";
        }

        $fotos = array_values(array_unique(array_filter(array_merge(
            $this->extraerUrlsDesdeCampo($hecho->foto_lugar),
            $this->extraerUrlsDesdeCampo($hecho->foto_situacion),
            $vehiculo ? $this->extraerUrlsDesdeCampo($vehiculo->fotos) : []
        ))));

        return [
            'coordinacion' => 'COORDINACION DEL AGRUPAMIENTO DE SEGURIDAD VIAL',
            'unidad' => 'UNIDAD DE ATENCIÓN A SINIESTROS',
            'municipio' => (string) ($hecho->municipio ?: 'MORELIA'),
            'sector' => $hecho->sector ? 'SECTOR ' . $hecho->sector : null,
            'tema' => 'HECHO DE TRÁNSITO CLASIFICADO COMO ' . mb_strtoupper((string) ($hecho->tipo_hecho ?: 'SIN CLASIFICACIÓN'), 'UTF-8'),
            'descripcion' => $descripcion,
            'vehiculo' => $vehiculoTexto,
            'grua' => $grua,
            'corralon' => $corralon,
            'servicio' => $servicio,
            'estado' => mb_strtoupper((string) ($hecho->situacion ?: 'SIN ESTADO'), 'UTF-8'),
            'ubicacion' => $ubicacion,
            'google_maps' => $googleMaps,
            'informa' => $hecho->unidad ? 'UNIDAD ' . $hecho->unidad : ($hecho->perito ?: null),
            'fotos' => $fotos,
        ];
    }

    protected function getWhatsAppConfig(): array
    {
        $graphVersion = (string) config('services.whatsapp.graph_version', 'v19.0');
        $phoneNumberId = (string) config('services.whatsapp.phone_number_id');
        $token = (string) (
            config('services.whatsapp.token')
            ?: config('services.whatsapp.access_token')
            ?: env('WHATSAPP_ACCESS_TOKEN')
        );

        return [
            'graph_version' => $graphVersion !== '' ? $graphVersion : 'v19.0',
            'phone_number_id' => $phoneNumberId,
            'token' => $token,
        ];
    }

    protected function normalizarPlaca(string $placa): string
    {
        $placa = mb_strtoupper(trim($placa), 'UTF-8');
        $placa = str_replace(['-', ' ', '.'], '', $placa);

        return $placa;
    }

    protected function formatearHora(string $hora): string
    {
        if ($hora === '') {
            return '';
        }

        return substr($hora, 0, 5);
    }

    protected function valorONoEspecificado(?string $valor): string
    {
        $valor = trim((string) $valor);

        return $valor !== '' ? $valor : 'NO ESPECIFICADO';
    }

    protected function extraerUrlsDesdeCampo($valor): array
    {
        if (empty($valor)) {
            return [];
        }

        if (is_array($valor)) {
            return collect($valor)
                ->flatMap(fn ($item) => $this->extraerUrlsDesdeCampo($item))
                ->filter()
                ->values()
                ->all();
        }

        if (is_string($valor)) {
            $trim = trim($valor);

            if ($trim === '') {
                return [];
            }

            $json = json_decode($trim, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->extraerUrlsDesdeCampo($json);
            }

            if (str_contains($trim, ',')) {
                return collect(explode(',', $trim))
                    ->map(fn ($item) => $this->pathToUrl($item))
                    ->filter()
                    ->values()
                    ->all();
            }

            return array_filter([$this->pathToUrl($trim)]);
        }

        return [];
    }

    protected function pathToUrl(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        try {
            return url(Storage::url($path));
        } catch (\Throwable $e) {
            Log::warning('WA pathToUrl error', [
                'path' => $path,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
