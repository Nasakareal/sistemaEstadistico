<?php

namespace App\Services;

use App\Models\Hechos;
use App\Models\User;
use App\Notifications\HechoPendienteRevisionNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class HechoRevisionNotificationService
{
    public function notificarJefesDeGrupoPorHechoPendiente(Hechos $hecho): void
    {
        if ($hecho->estado_revision !== 'pendiente') {
            return;
        }

        if (!Schema::hasTable('notifications')) {
            Log::warning('No se notificaron jefes de grupo: falta la tabla notifications.', [
                'hecho_id' => $hecho->id,
            ]);

            return;
        }

        $usuarios = User::query()
            ->role('Jefe de Grupo')
            ->where('unidad_id', $hecho->unidad_org_id)
            ->where('estado', 'Activo')
            ->get();

        foreach ($usuarios as $usuario) {
            try {
                $usuario->notify(new HechoPendienteRevisionNotification($hecho));
            } catch (\Throwable $e) {
                Log::error('No se pudo registrar notificación de hecho pendiente.', [
                    'hecho_id' => $hecho->id,
                    'user_id' => $usuario->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function notificarPendientesDeRevisionPorUnidad(?int $unidadId = null): void
    {
        $query = Hechos::query()
            ->where('estado_revision', 'pendiente');

        if ($unidadId) {
            $query->where('unidad_org_id', $unidadId);
        }

        $hechos = $query->get();

        foreach ($hechos as $hecho) {
            $this->notificarJefesDeGrupoPorHechoPendiente($hecho);
        }
    }
}
