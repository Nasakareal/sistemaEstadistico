<?php

namespace App\Services;

use App\Models\SierseIntegrationEvent;
use App\Models\Vehiculo;

class SierseIntegrationStatusService
{
    public function forVehicle(Vehiculo $vehicle): array
    {
        $vehicle->loadMissing(['servicio', 'liberacionCorralon']);
        $events = SierseIntegrationEvent::query()
            ->where('vehicle_source', 'vehicle_registry')
            ->where('vehicle_id', $vehicle->id)
            ->orderByDesc('received_at')
            ->get();
        $release = $vehicle->liberacionCorralon;
        $service = $vehicle->servicio;

        $latest = fn (string $type) => $events->firstWhere('event_type', $type);
        $costEvent = $latest('cost_update');
        $releaseEvent = $latest('release_update');
        $requirements = array_merge([
            'memory_description' => false,
            'payment_receipt' => false,
            'release_document' => false,
            'delivery_photo' => false,
        ], (array) optional($release)->sierse_requirements);

        $requirements['memory_description'] = $requirements['memory_description']
            || (bool) optional($service)->memoria_descriptiva_path
            || (bool) optional($release)->memoria_descriptiva;
        $requirements['payment_receipt'] = $requirements['payment_receipt']
            || (bool) optional($release)->comprobante_pago;
        $requirements['release_document'] = $requirements['release_document']
            || (bool) optional($release)->documento_liberacion;
        $requirements['delivery_photo'] = $requirements['delivery_photo']
            || (bool) optional($release)->foto_entrega;

        return [
            'connected' => $events->isNotEmpty(),
            'last_received_at' => optional($events->first())->received_at,
            'events_count' => $events->count(),
            'pending_review_count' => $events->where('status', 'REVIEW_REQUIRED')->count(),
            'costs_received' => (bool) $costEvent,
            'costs_status' => optional($costEvent)->status,
            'total_amount' => optional($service)->importe_total,
            'release_reported' => (bool) optional($release)->sierse_reported_released,
            'release_reported_at' => optional($release)->sierse_reported_at,
            'release_event_status' => optional($releaseEvent)->status,
            'officially_released' => optional($release)->estado === 'ENTREGADO',
            'requirements' => $requirements,
            'complete' => (bool) $costEvent
                && (bool) optional($release)->sierse_reported_released
                && !in_array(false, $requirements, true),
        ];
    }
}
