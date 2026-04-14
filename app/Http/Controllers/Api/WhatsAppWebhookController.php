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

        $this->sendText($from, "Escribe:\n\nPLACAS ABC123\n\nO:\n\nDETALLE 59564");
    }

    protected function replyBusquedaPorPlacas(string $from, string $placa): void
    {
        $resultados = $this->buscarHechosPorPlaca($placa);

        if (count($resultados) === 0) {
            $this->sendText($from, "No encontré hechos con las placas {$placa}.");
            return;
        }

        $lineas = [];
        $lineas[] = 'Encontré ' . count($resultados) . " hecho(s) con las placas {$placa}:";
        $lineas[] = '';

        foreach ($resultados as $item) {
            $lineas[] = "{$item['id']} | {$item['folio']} | {$item['fecha']} {$item['hora']} | {$item['tipo']} | {$item['estado']}";
        }

        $lineas[] = '';
        $lineas[] = 'Responde:';
        $lineas[] = 'DETALLE ' . $resultados[0]['id'];

        $this->sendText($from, implode("\n", $lineas));
    }

    protected function replyDetallePorFolio(string $from, string $folio): void
    {
        $detalle = $this->obtenerDetalleHechoPorFolio($folio);

        if (!$detalle) {
            $this->sendText($from, "No encontré el hecho {$folio}.");
            return;
        }

        $bloques = [];

        $bloques[] = 'GUARDIA CIVIL';
        $bloques[] = $detalle['coordinacion'] ?? '';
        $bloques[] = $detalle['unidad'] ?? '';
        $bloques[] = $detalle['municipio'] ?? '';

        if (!empty($detalle['sector'])) {
            $bloques[] = $detalle['sector'];
        }

        $bloques[] = 'TEMA: ' . ($detalle['tema'] ?? 'HECHO DE TRÁNSITO');
        $bloques[] = $detalle['descripcion'] ?? '';

        if (!empty($detalle['vehiculos_texto'])) {
            $bloques[] = 'Lugar donde se encuentran:';
            $bloques[] = $detalle['vehiculos_texto'];
        }

        if (!empty($detalle['estado'])) {
            $bloques[] = 'Hecho ' . $detalle['estado'] . '.';
        }

        $ubicacionExtra = [];
        if (!empty($detalle['ubicacion'])) {
            $ubicacionExtra[] = 'Ubicación: ' . $detalle['ubicacion'];
        }
        if (!empty($detalle['google_maps'])) {
            $ubicacionExtra[] = 'Google Maps: ' . $detalle['google_maps'];
        }
        if (!empty($ubicacionExtra)) {
            $bloques[] = implode("\n", $ubicacionExtra);
        }

        if (!empty($detalle['informa'])) {
            $bloques[] = 'INFORMA ' . $detalle['informa'];
        }

        $bloques = array_values(array_filter($bloques, fn ($item) => $item !== null && trim((string) $item) !== ''));
        $texto = implode("\n\n", $bloques);

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

        $lat = $hecho->lat;
        $lng = $hecho->lng;
        $googleMaps = null;
        $ubicacion = null;

        if (!is_null($lat) && !is_null($lng) && $lat !== '' && $lng !== '') {
            $ubicacion = "{$lat}, {$lng}";
            $googleMaps = "https://www.google.com/maps?q={$lat},{$lng}";
        }

        $vehiculosTexto = [];
        $fotosVehiculos = [];

        foreach (($hecho->vehiculos ?? []) as $index => $vehiculo) {
            $etiqueta = chr(65 + $index) . ')';

            $lineasVehiculo = [];
            $lineasVehiculo[] = 'VEHÍCULO ' . $etiqueta;
            $lineasVehiculo[] = $this->buildVehiculoDescripcion($vehiculo);

            $ocupantes = $this->buildVehiculoOcupantes($vehiculo);
            if ($ocupantes !== '') {
                $lineasVehiculo[] = $ocupantes;
            }

            $vehiculosTexto[] = implode("\n", array_filter($lineasVehiculo, fn ($item) => trim((string) $item) !== ''));

            $fotosVehiculos = array_merge($fotosVehiculos, $this->extraerUrlsDesdeCampo($vehiculo->fotos ?? null));
        }

        $fotos = array_values(array_unique(array_filter(array_merge(
            $this->extraerUrlsDesdeCampo($hecho->foto_lugar),
            $this->extraerUrlsDesdeCampo($hecho->foto_situacion),
            $fotosVehiculos
        ))));

        return [
            'coordinacion' => 'COORDINACION DEL AGRUPAMIENTO DE SEGURIDAD VIAL',
            'unidad' => 'UNIDAD DE ATENCIÓN A SINIESTROS',
            'municipio' => (string) ($hecho->municipio ?: 'MORELIA'),
            'sector' => $hecho->sector ? 'SECTOR ' . $hecho->sector : null,
            'tema' => 'HECHO DE TRÁNSITO CLASIFICADO COMO ' . mb_strtoupper((string) ($hecho->tipo_hecho ?: 'SIN CLASIFICACIÓN'), 'UTF-8'),
            'descripcion' => $descripcion,
            'vehiculos_texto' => implode("\n\n", $vehiculosTexto),
            'estado' => mb_strtoupper((string) ($hecho->situacion ?: 'SIN ESTADO'), 'UTF-8'),
            'ubicacion' => $ubicacion,
            'google_maps' => $googleMaps,
            'informa' => $hecho->unidad ? 'UNIDAD ' . $hecho->unidad : ($hecho->perito ?: null),
            'fotos' => $fotos,
        ];
    }

    protected function buildVehiculoDescripcion($vehiculo): string
    {
        $partes = [];

        $partes[] = 'De la marca ' . $this->valorONoEspecificado($vehiculo->marca ?? null);
        $partes[] = 'tipo ' . $this->valorONoEspecificado($vehiculo->tipo ?? null);

        if (!empty($vehiculo->linea)) {
            $partes[] = 'línea ' . trim((string) $vehiculo->linea);
        }

        if (!empty($vehiculo->color)) {
            $partes[] = 'color ' . trim((string) $vehiculo->color);
        }

        if (!empty($vehiculo->placas)) {
            $partes[] = 'placas ' . trim((string) $vehiculo->placas);
        }

        if (!empty($vehiculo->serie)) {
            $partes[] = 'NIV ' . trim((string) $vehiculo->serie);
        }

        return implode(', ', $partes) . '.';
    }

    protected function buildVehiculoOcupantes($vehiculo): string
    {
        $nombre = $this->firstFilled([
            $vehiculo->nombre_conductor ?? null,
            $vehiculo->conductor_nombre ?? null,
            $vehiculo->nombre_persona ?? null,
            $vehiculo->responsable ?? null,
            $vehiculo->propietario ?? null,
        ]);

        $edad = $this->firstFilled([
            $vehiculo->edad_conductor ?? null,
            $vehiculo->conductor_edad ?? null,
            $vehiculo->edad_persona ?? null,
            $vehiculo->edad ?? null,
        ]);

        if ($nombre === '') {
            return '';
        }

        $texto = 'Manifiesta viajar a bordo el C. ' . $nombre;

        if ($edad !== '') {
            $texto .= ' de ' . $edad . ' años';
        }

        return $texto . '.';
    }

    protected function firstFilled(array $values): string
    {
        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
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
