<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConduceLegalidadVehiculo;
use App\Models\Grua;
use App\Models\SierseIntegrationEvent;
use App\Models\Vehiculo;
use App\Services\Fotos\HechoFotoStorage;
use App\Services\SierseIntegrationEventProcessor;
use App\Services\SierseIntegrationStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SierseIntegrationController extends Controller
{
    public function status(Request $request)
    {
        return response()->json([
            'ok' => true,
            'integration' => 'SIERSE',
            'version' => 'v1',
            'client' => [
                'id' => $request->attributes->get('sierse_client_id'),
                'name' => $request->attributes->get('sierse_client_name'),
                'key_id' => $request->attributes->get('sierse_key_id'),
            ],
            'data_exchange_enabled' => (bool) config('sierse.data_exchange_enabled', false),
            'capabilities' => [
                'outbound' => [
                    'scoped_vehicle_search',
                    'vehicle_custody_file',
                    'authenticated_vehicle_photos',
                    'tow_service_file',
                ],
                'inbound' => [
                    'service_costs',
                    'service_status_and_dates',
                    'release_status',
                    'versioned_additional_data',
                ],
                'excluded' => [
                    'drivers',
                    'driver_phone_numbers',
                    'driver_addresses',
                ],
            ],
            'server_time' => now()->toIso8601String(),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function search(Request $request)
    {
        abort_unless(config('sierse.data_exchange_enabled', false), 404);

        $validated = $request->validate([
            'type' => ['required', 'in:inventory,plate,vin,conduce_folio'],
            'value' => ['required', 'string', 'max:120'],
        ]);
        $clientId = (string) $request->attributes->get('sierse_client_id');
        $grua = $this->clientGrua($clientId);
        abort_unless($grua, 404);

        $type = (string) $validated['type'];
        $value = trim((string) $validated['value']);
        $items = $type === 'conduce_folio'
            ? $this->searchConduceFolio($grua, $value)
            : $this->searchVehicleIdentifiers($grua, $type, $value);

        // No se distingue entre inexistente y perteneciente a otra grua.
        abort_if($items->isEmpty(), 404);

        return response()->json([
            'ok' => true,
            'query' => ['type' => $type, 'value' => $value],
            'data' => $items->values(),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function index(Request $request)
    {
        $this->ensureExchangeEnabled();
        $validated = $request->validate([
            'source' => ['required', 'in:vehicle_registry,conduce_legalidad'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'updated_since' => ['nullable', 'date'],
        ]);
        $grua = $this->clientGrua((string) $request->attributes->get('sierse_client_id'));
        abort_unless($grua, 404);

        $page = (int) ($validated['page'] ?? 1);
        $perPage = (int) ($validated['per_page'] ?? 50);
        $updatedSince = $validated['updated_since'] ?? null;

        if ($validated['source'] === 'vehicle_registry') {
            $query = Vehiculo::query()
                ->where('grua_id', $grua->id)
                ->with(['servicio', 'liberacionCorralon'])
                ->when($updatedSince, fn ($q) => $q->where('updated_at', '>=', $updatedSince))
                ->orderBy('id');
            $result = $query->paginate($perPage, ['*'], 'page', $page);
            $data = $result->getCollection()->map(fn (Vehiculo $vehicle) => $this->regularVehiclePayload($vehicle));
        } else {
            $query = ConduceLegalidadVehiculo::query()
                ->where(function ($custody) use ($grua) {
                    $custody->where('grua_id', $grua->id)
                        ->orWhere(function ($yard) use ($grua) {
                            $yard->whereNull('grua_id')->where('corralon_id', $grua->id);
                        });
                })
                ->with(['captura.operativo'])
                ->when($updatedSince, fn ($q) => $q->where('updated_at', '>=', $updatedSince))
                ->orderBy('id');
            $result = $query->paginate($perPage, ['*'], 'page', $page);
            $data = $result->getCollection()->map(fn (ConduceLegalidadVehiculo $vehicle) => $this->conduceVehiclePayload($vehicle));
        }

        return response()->json([
            'ok' => true,
            'data' => $data->values(),
            'meta' => [
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
            ],
        ])->header('Cache-Control', 'no-store, private');
    }

    public function show(Request $request, string $source, int $vehicleId)
    {
        $this->ensureExchangeEnabled();
        $vehicle = $this->scopedVehicle($request, $source, $vehicleId);
        $payload = $source === 'vehicle_registry'
            ? $this->regularVehiclePayload($vehicle)
            : $this->conduceVehiclePayload($vehicle);

        return response()->json(['ok' => true, 'data' => $payload])
            ->header('Cache-Control', 'no-store, private');
    }

    public function photo(
        Request $request,
        string $source,
        int $vehicleId,
        string $type,
        HechoFotoStorage $storage
    ) {
        $this->ensureExchangeEnabled();
        abort_unless($source === 'vehicle_registry', 404);
        /** @var Vehiculo $vehicle */
        $vehicle = $this->scopedVehicle($request, $source, $vehicleId);
        $service = $vehicle->servicio;
        $paths = [
            'vehicle' => $vehicle->fotos,
            'inventory' => $vehicle->foto_inventario_grua,
            'service' => $service ? $service->foto_vehiculo : null,
        ];
        abort_unless(array_key_exists($type, $paths) && $paths[$type], 404);

        return $storage->response(
            $paths[$type],
            'sierse_' . $type . '_vehiculo_' . $vehicle->id . '.'
                . (pathinfo($paths[$type], PATHINFO_EXTENSION) ?: 'jpg')
        );
    }

    public function storeEvent(
        Request $request,
        string $source,
        int $vehicleId,
        SierseIntegrationEventProcessor $processor
    )
    {
        $this->ensureExchangeEnabled();
        abort_if(strlen((string) $request->getContent()) > 62914560, 413, 'Payload demasiado grande.');
        abort_unless($source === 'vehicle_registry', 422, 'Los eventos solo se aplican al expediente vehicular principal.');
        /** @var Vehiculo $vehicle */
        $vehicle = $this->scopedVehicle($request, $source, $vehicleId);
        $validated = $request->validate([
            'external_event_id' => ['required', 'string', 'max:120', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'event_type' => ['required', 'in:service_update,cost_update,release_update,evidence_update,other'],
            'schema_version' => ['nullable', 'string', 'max:20'],
            'data' => ['required', 'array'],
        ]);
        $clientId = (string) $request->attributes->get('sierse_client_id');
        $canonicalPayload = json_encode($validated['data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $payloadHash = hash('sha256', (string) $canonicalPayload);
        $existing = SierseIntegrationEvent::query()
            ->where('client_id', $clientId)
            ->where('external_event_id', $validated['external_event_id'])
            ->first();

        if ($existing) {
            abort_unless(hash_equals($existing->payload_sha256, $payloadHash), 409, 'El identificador ya existe con otro contenido.');
            return response()->json(['ok' => true, 'duplicate' => true, 'event' => $this->eventPayload($existing)])
                ->header('Cache-Control', 'no-store, private');
        }

        $event = DB::transaction(function () use (
            $clientId,
            $payloadHash,
            $processor,
            $source,
            $validated,
            $vehicle,
            $vehicleId
        ) {
            $event = SierseIntegrationEvent::create([
                'client_id' => $clientId,
                'external_event_id' => $validated['external_event_id'],
                'vehicle_source' => $source,
                'vehicle_id' => $vehicleId,
                'event_type' => $validated['event_type'],
                'schema_version' => $validated['schema_version'] ?? '1.0',
                'payload_sha256' => $payloadHash,
                'payload' => $validated['data'],
                'status' => 'RECEIVED',
                'received_at' => now(),
            ]);

            $result = $processor->process($event, $vehicle, $validated['data']);
            $event->update([
                'payload' => $result['payload'],
                'status' => $result['status'],
                'processing_message' => $result['message'],
                'processed_at' => $result['status'] === 'RECEIVED' ? null : now(),
            ]);

            return $event->refresh();
        });

        return response()->json(['ok' => true, 'duplicate' => false, 'event' => $this->eventPayload($event)], 202)
            ->header('Cache-Control', 'no-store, private');
    }

    public function showEvent(Request $request, string $externalEventId)
    {
        $this->ensureExchangeEnabled();
        $event = SierseIntegrationEvent::query()
            ->where('client_id', (string) $request->attributes->get('sierse_client_id'))
            ->where('external_event_id', $externalEventId)
            ->firstOrFail();

        return response()->json(['ok' => true, 'event' => $this->eventPayload($event)])
            ->header('Cache-Control', 'no-store, private');
    }

    private function clientGrua(string $clientId): ?Grua
    {
        $client = config('sierse.clients.' . $clientId, []);
        $gruaName = trim((string) ($client['grua_name'] ?? ''));

        if ($gruaName === '') {
            return null;
        }

        return Grua::query()->where('nombre', $gruaName)->first();
    }

    private function ensureExchangeEnabled(): void
    {
        abort_unless(config('sierse.data_exchange_enabled', false), 404);
    }

    private function scopedVehicle(Request $request, string $source, int $vehicleId)
    {
        abort_unless(in_array($source, ['vehicle_registry', 'conduce_legalidad'], true), 404);
        $grua = $this->clientGrua((string) $request->attributes->get('sierse_client_id'));
        abort_unless($grua, 404);

        if ($source === 'vehicle_registry') {
            return Vehiculo::query()
                ->whereKey($vehicleId)
                ->where('grua_id', $grua->id)
                ->with(['servicio', 'liberacionCorralon'])
                ->firstOrFail();
        }

        return ConduceLegalidadVehiculo::query()
            ->whereKey($vehicleId)
            ->where(function ($custody) use ($grua) {
                $custody->where('grua_id', $grua->id)
                    ->orWhere(function ($yard) use ($grua) {
                        $yard->whereNull('grua_id')->where('corralon_id', $grua->id);
                    });
            })
            ->with(['captura.operativo'])
            ->firstOrFail();
    }

    private function eventPayload(SierseIntegrationEvent $event): array
    {
        return [
            'external_event_id' => $event->external_event_id,
            'vehicle_source' => $event->vehicle_source,
            'vehicle_id' => (int) $event->vehicle_id,
            'event_type' => $event->event_type,
            'schema_version' => $event->schema_version,
            'status' => $event->status,
            'processing_message' => $event->processing_message,
            'received_at' => optional($event->received_at)->toIso8601String(),
            'processed_at' => optional($event->processed_at)->toIso8601String(),
        ];
    }

    private function searchVehicleIdentifiers(Grua $grua, string $type, string $value)
    {
        $column = [
            'inventory' => 'numero_inventario_grua',
            'plate' => 'placas',
            'vin' => 'serie',
        ][$type];
        $normalized = $this->normalizeIdentifier($value);

        $regular = Vehiculo::query()
            ->where('grua_id', $grua->id)
            ->where(function ($query) use ($column, $value, $normalized) {
                $query->where($column, $value);
                if ($normalized !== '') {
                    $query->orWhereRaw($this->normalizedSql($column) . ' = ?', [$normalized]);
                }
            })
            ->with(['servicio', 'liberacionCorralon'])
            ->limit(20)
            ->get()
            ->map(fn (Vehiculo $vehicle) => $this->regularVehiclePayload($vehicle));

        $clColumn = [
            'inventory' => 'numero_inventario',
            'plate' => 'placas',
            'vin' => 'serie',
        ][$type];
        $remaining = max(0, 20 - $regular->count());
        if ($remaining === 0) {
            return $regular;
        }

        $conduce = ConduceLegalidadVehiculo::query()
            ->where(function ($query) use ($grua) {
                $query->where('grua_id', $grua->id)
                    ->orWhere(function ($corralon) use ($grua) {
                        $corralon->whereNull('grua_id')->where('corralon_id', $grua->id);
                    });
            })
            ->where(function ($query) use ($clColumn, $value, $normalized) {
                $query->where($clColumn, $value);
                if ($normalized !== '') {
                    $query->orWhereRaw($this->normalizedSql($clColumn) . ' = ?', [$normalized]);
                }
            })
            ->with(['captura.operativo'])
            ->limit($remaining)
            ->get()
            ->map(fn (ConduceLegalidadVehiculo $vehicle) => $this->conduceVehiclePayload($vehicle));

        return $regular->concat($conduce);
    }

    private function searchConduceFolio(Grua $grua, string $value)
    {
        $folio = Str::upper(trim($value));
        if (!preg_match('/^CL-(\d+)-(\d+)(?:-(\d+))?$/', $folio, $matches)) {
            return collect();
        }

        $query = ConduceLegalidadVehiculo::query()
            ->select('conduce_legalidad_vehiculos.*')
            ->join('conduce_legalidad_capturas as clc', 'clc.id', '=', 'conduce_legalidad_vehiculos.captura_id')
            ->join('conduce_legalidad_operativos as clo', 'clo.id', '=', 'clc.operativo_id')
            ->where('clo.tipo_operativo', 'conduce_legalidad')
            ->where('clc.operativo_id', (int) $matches[1])
            ->where(function ($capture) use ($matches) {
                $capture->where('clc.folio_numero', (int) $matches[2])
                    ->orWhere(function ($legacy) use ($matches) {
                        $legacy->whereNull('clc.folio_numero')->where('clc.id', (int) $matches[2]);
                    });
            })
            ->where(function ($custody) use ($grua) {
                $custody->where('conduce_legalidad_vehiculos.grua_id', $grua->id)
                    ->orWhere(function ($corralon) use ($grua) {
                        $corralon->whereNull('conduce_legalidad_vehiculos.grua_id')
                            ->where('conduce_legalidad_vehiculos.corralon_id', $grua->id);
                    });
            })
            ->with(['captura.operativo'])
            ->orderBy('conduce_legalidad_vehiculos.id');

        if (isset($matches[3])) {
            $query->skip(max(0, (int) $matches[3] - 1))->limit(1);
        } else {
            $query->limit(20);
        }

        return $query->get()->map(fn (ConduceLegalidadVehiculo $vehicle) => $this->conduceVehiclePayload($vehicle));
    }

    private function regularVehiclePayload(Vehiculo $vehicle): array
    {
        $service = $vehicle->servicio;
        $release = $vehicle->liberacionCorralon;

        return [
            'source' => 'vehicle_registry',
            'id' => (int) $vehicle->id,
            'client_uuid' => $vehicle->client_uuid,
            'inventory_number' => $vehicle->numero_inventario_grua,
            'conduce_folio' => null,
            'make' => $vehicle->marca,
            'line' => $vehicle->linea,
            'model' => $vehicle->modelo,
            'type' => $vehicle->tipo,
            'color' => $vehicle->color,
            'plate' => $vehicle->placas,
            'plate_state' => $vehicle->estado_placas,
            'vin' => $vehicle->serie,
            'yard' => $vehicle->corralon,
            'damage_amount' => $vehicle->monto_danos,
            'damaged_parts' => $vehicle->partes_danadas,
            'insurer' => $vehicle->aseguradora,
            'theft_report' => (bool) $vehicle->reporte_robo,
            'service' => $service ? [
                'id' => (int) $service->id,
                'sierse_folio' => $service->folio_sierse,
                'status_dates' => [
                    'requested_at' => optional($service->fecha_solicitud)->toIso8601String(),
                    'arrived_at' => optional($service->fecha_arribo)->toIso8601String(),
                    'started_at' => optional($service->fecha_inicio)->toIso8601String(),
                    'finished_at' => optional($service->fecha_fin)->toIso8601String(),
                ],
            ] : null,
            'release' => $release ? [
                'status' => $release->estado,
                'sierse_folio' => $release->sierse_folio,
                'released_at' => optional($release->fecha_entrega)->toIso8601String(),
            ] : null,
            'sierse_integration' => app(SierseIntegrationStatusService::class)->forVehicle($vehicle),
            'available_photos' => array_values(array_filter([
                $vehicle->fotos ? 'vehicle' : null,
                $vehicle->foto_inventario_grua ? 'inventory' : null,
                $service && $service->foto_vehiculo ? 'service' : null,
            ])),
        ];
    }

    private function conduceVehiclePayload(ConduceLegalidadVehiculo $vehicle): array
    {
        $capture = $vehicle->captura;
        $operational = optional($capture)->operativo;
        $folioNumber = optional($capture)->folio_numero ?: optional($capture)->id;

        return [
            'source' => 'conduce_legalidad',
            'id' => (int) $vehicle->id,
            'inventory_number' => $vehicle->numero_inventario,
            'conduce_folio' => $operational && $folioNumber
                ? 'CL-' . $operational->id . '-' . $folioNumber
                : null,
            'make' => $vehicle->marca,
            'line' => $vehicle->linea,
            'model' => $vehicle->modelo,
            'type' => $vehicle->tipo ?: $vehicle->tipo_general,
            'color' => $vehicle->color,
            'plate' => $vehicle->placas,
            'plate_state' => $vehicle->estado_placas,
            'vin' => $vehicle->serie,
            'yard' => $vehicle->corralon,
            'damage_amount' => $vehicle->monto_danos,
            'damaged_parts' => $vehicle->partes_danadas,
            'insurer' => $vehicle->aseguradora,
            'retained' => (bool) $vehicle->retencion_vehiculo,
            'custody_reason' => $vehicle->motivo_retencion,
            'registered_at' => optional($vehicle->created_at)->toIso8601String(),
        ];
    }

    private function normalizeIdentifier(string $value): string
    {
        return preg_replace('/[^A-Z0-9]/', '', Str::upper($value)) ?: '';
    }

    private function normalizedSql(string $column): string
    {
        $qualified = DB::getQueryGrammar()->wrap($column);

        return "UPPER(REPLACE(REPLACE(REPLACE({$qualified}, '-', ''), ' ', ''), '.', ''))";
    }
}
