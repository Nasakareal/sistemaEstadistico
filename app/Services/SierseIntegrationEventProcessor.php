<?php

namespace App\Services;

use App\Models\LiberacionCorralon;
use App\Models\SierseIntegrationEvent;
use App\Models\Vehiculo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SierseIntegrationEventProcessor
{
    private const DOCUMENTS = [
        'memory_description' => ['field' => 'memoria_descriptiva', 'max' => 15728640],
        'payment_receipt' => ['field' => 'comprobante_pago', 'max' => 10485760],
        'release_document' => ['field' => 'documento_liberacion', 'max' => 10485760],
        'delivery_photo' => ['field' => 'foto_entrega', 'max' => 5242880],
    ];

    public function process(SierseIntegrationEvent $event, Vehiculo $vehicle, array $data): array
    {
        if ($event->event_type === 'cost_update') {
            return $this->costUpdate($vehicle, $data);
        }

        if ($event->event_type === 'service_update') {
            return $this->serviceUpdate($vehicle, $data);
        }

        if ($event->event_type === 'release_update') {
            return $this->releaseUpdate($event, $vehicle, $data);
        }

        if ($event->event_type === 'evidence_update') {
            return $this->evidenceUpdate($event, $vehicle, $data);
        }

        return [
            'status' => 'RECEIVED',
            'message' => 'Evento adicional recibido sin modificar el expediente.',
            'payload' => $data,
        ];
    }

    private function costUpdate(Vehiculo $vehicle, array $data): array
    {
        $validated = Validator::make($data, [
            'total_amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'currency' => ['nullable', 'in:MXN'],
            'payment_method' => ['nullable', 'string', 'max:40'],
            'payment_status' => ['nullable', 'in:PENDING,PARTIAL,PAID,CANCELLED'],
            'invoice_reference' => ['nullable', 'string', 'max:120'],
            'breakdown' => ['nullable', 'array'],
            'breakdown.*.concept' => ['required_with:breakdown', 'string', 'max:120'],
            'breakdown.*.amount' => ['required_with:breakdown', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ])->validate();

        $service = $vehicle->servicio()->latest()->firstOrFail();
        $service->update([
            'importe_total' => $validated['total_amount'],
            'forma_pago' => $validated['payment_method'] ?? $service->forma_pago,
            'observaciones_grua' => $this->appendNote(
                $service->observaciones_grua,
                $validated['notes'] ?? null,
                'Costo reportado por API SIERSE'
            ),
        ]);

        return [
            'status' => 'APPLIED',
            'message' => 'Costo aplicado al servicio; desglose conservado en el evento.',
            'payload' => $validated,
        ];
    }

    private function serviceUpdate(Vehiculo $vehicle, array $data): array
    {
        $validated = Validator::make($data, [
            'sierse_folio' => ['nullable', 'string', 'max:120'],
            'requested_at' => ['nullable', 'date'],
            'arrived_at' => ['nullable', 'date'],
            'started_at' => ['nullable', 'date'],
            'finished_at' => ['nullable', 'date'],
            'origin' => ['nullable', 'string', 'max:255'],
            'destination' => ['nullable', 'string', 'max:255'],
            'operator_name' => ['nullable', 'string', 'max:255'],
            'tow_unit' => ['nullable', 'string', 'max:120'],
            'kilometers' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'performed_maneuvers' => ['nullable', 'boolean'],
            'maneuvers_description' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ])->validate();

        $map = [
            'sierse_folio' => 'folio_sierse',
            'requested_at' => 'fecha_solicitud',
            'arrived_at' => 'fecha_arribo',
            'started_at' => 'fecha_inicio',
            'finished_at' => 'fecha_fin',
            'origin' => 'lugar_origen',
            'destination' => 'lugar_destino',
            'operator_name' => 'operador_nombre',
            'tow_unit' => 'unidad_grua',
            'kilometers' => 'kilometros_recorridos',
            'performed_maneuvers' => 'realizo_maniobras',
            'maneuvers_description' => 'maniobras_descripcion',
        ];
        $updates = [];
        foreach ($map as $input => $column) {
            if (array_key_exists($input, $validated)) {
                $updates[$column] = $validated[$input];
            }
        }

        $service = $vehicle->servicio()->latest()->firstOrFail();
        if (array_key_exists('notes', $validated)) {
            $updates['observaciones_grua'] = $this->appendNote(
                $service->observaciones_grua,
                $validated['notes'],
                'Actualización recibida por API SIERSE'
            );
        }
        $service->update($updates);

        return [
            'status' => 'APPLIED',
            'message' => 'Datos operativos aplicados al servicio.',
            'payload' => $validated,
        ];
    }

    private function releaseUpdate(SierseIntegrationEvent $event, Vehiculo $vehicle, array $data): array
    {
        $validated = Validator::make($data, [
            'released' => ['required', 'boolean'],
            'released_at' => ['required_if:released,true', 'nullable', 'date'],
            'sierse_folio' => ['required_if:released,true', 'nullable', 'string', 'max:120'],
            'delivery_condition' => ['nullable', 'in:CONFORME_INVENTARIO,CON_OBSERVACIONES'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'documents' => ['nullable', 'array'],
        ])->validate();

        $release = LiberacionCorralon::firstOrNew(['vehiculo_id' => $vehicle->id]);
        $official = $release->exists && $release->estado === 'ENTREGADO';
        $release->fill([
            'grua_id' => $vehicle->grua_id,
            'sierse_folio' => $validated['sierse_folio'] ?? $release->sierse_folio,
            'sierse_reported_released' => (bool) $validated['released'],
            'sierse_reported_at' => $validated['released_at'] ?? now(),
            'sierse_event_id' => $event->id,
            'condicion_entrega' => $validated['delivery_condition'] ?? $release->condicion_entrega,
            'observaciones' => $this->appendNote(
                $release->observaciones,
                $validated['notes'] ?? null,
                'Liberación reportada por API SIERSE'
            ),
            'estado' => $official ? 'ENTREGADO' : 'PENDIENTE',
        ]);

        $documentResult = $this->storeDocuments($event, $vehicle, $validated['documents'] ?? [], $release);
        $release->sierse_requirements = $documentResult['requirements'];
        $release->save();

        return [
            'status' => $official ? 'APPLIED' : 'REVIEW_REQUIRED',
            'message' => $official
                ? 'Liberación externa asociada a una entrega oficial existente.'
                : 'Liberación reportada; requiere validación interna y evidencias completas.',
            'payload' => array_merge($validated, ['documents' => $documentResult['documents']]),
        ];
    }

    private function evidenceUpdate(SierseIntegrationEvent $event, Vehiculo $vehicle, array $data): array
    {
        $validated = Validator::make($data, [
            'documents' => ['required', 'array', 'min:1'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ])->validate();
        $release = LiberacionCorralon::firstOrNew(['vehiculo_id' => $vehicle->id]);
        $release->grua_id = $vehicle->grua_id;
        $release->estado = $release->estado ?: 'PENDIENTE';
        $release->sierse_event_id = $event->id;

        $documentResult = $this->storeDocuments($event, $vehicle, $validated['documents'], $release);
        $release->sierse_requirements = $documentResult['requirements'];
        $release->observaciones = $this->appendNote(
            $release->observaciones,
            $validated['notes'] ?? null,
            'Evidencias recibidas por API SIERSE'
        );
        $release->save();

        return [
            'status' => 'REVIEW_REQUIRED',
            'message' => 'Evidencias almacenadas; requieren revisión interna.',
            'payload' => array_merge($validated, ['documents' => $documentResult['documents']]),
        ];
    }

    private function storeDocuments(
        SierseIntegrationEvent $event,
        Vehiculo $vehicle,
        array $documents,
        LiberacionCorralon $release
    ): array {
        $requirements = array_merge([
            'memory_description' => false,
            'payment_receipt' => false,
            'release_document' => false,
            'delivery_photo' => false,
        ], (array) $release->sierse_requirements);
        $sanitized = [];

        foreach ($documents as $key => $document) {
            if (!isset(self::DOCUMENTS[$key]) || !is_array($document)) {
                throw ValidationException::withMessages(["data.documents.{$key}" => 'Tipo de documento no permitido.']);
            }

            $meta = Validator::make($document, [
                'filename' => ['required', 'string', 'max:180'],
                'mime' => ['required', 'in:application/pdf,image/jpeg,image/png,image/webp'],
                'content_base64' => ['required', 'string'],
            ])->validate();
            $binary = base64_decode($meta['content_base64'], true);
            if ($binary === false || $binary === '') {
                throw ValidationException::withMessages(["data.documents.{$key}.content_base64" => 'Base64 inválido.']);
            }
            if (strlen($binary) > self::DOCUMENTS[$key]['max']) {
                throw ValidationException::withMessages(["data.documents.{$key}" => 'Documento demasiado grande.']);
            }

            $actualMime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($binary);
            if (!in_array($actualMime, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true)) {
                throw ValidationException::withMessages(["data.documents.{$key}" => 'Contenido de archivo no permitido.']);
            }
            $extension = [
                'application/pdf' => 'pdf',
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            ][$actualMime];
            $path = 'sierse_integration/' . $vehicle->id . '/' . $event->id . '/' . $key . '.' . $extension;
            Storage::disk('local')->put($path, $binary);

            $field = self::DOCUMENTS[$key]['field'];
            $release->{$field} = $path;
            $requirements[$key] = true;
            $sanitized[$key] = [
                'filename' => Str::limit(basename($meta['filename']), 180, ''),
                'mime' => $actualMime,
                'sha256' => hash('sha256', $binary),
                'size' => strlen($binary),
                'received' => true,
            ];

            if ($key === 'memory_description') {
                $service = $vehicle->servicio()->latest()->first();
                if ($service) {
                    $service->update([
                        'memoria_descriptiva_path' => $path,
                        'memoria_descriptiva_nombre' => $sanitized[$key]['filename'],
                        'memoria_descriptiva_mime' => $actualMime,
                        'memoria_descriptiva_sha256' => $sanitized[$key]['sha256'],
                        'memoria_subida_at' => now(),
                    ]);
                }
            }
        }

        return ['requirements' => $requirements, 'documents' => $sanitized];
    }

    private function appendNote(?string $current, ?string $incoming, string $label): ?string
    {
        $incoming = trim((string) $incoming);
        if ($incoming === '') {
            return $current;
        }

        return trim(implode("\n", array_filter([
            trim((string) $current),
            '[' . $label . ' ' . now()->toIso8601String() . '] ' . $incoming,
        ])));
    }
}
