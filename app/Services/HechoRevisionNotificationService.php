<?php

namespace App\Services;

use App\Models\Hechos;
use App\Models\User;
use App\Notifications\HechoPendienteRevisionNotification;

class HechoRevisionNotificationService
{
    public function notificarJefesDeGrupoPorHechoPendiente(Hechos $hecho): void
    {
        if ($hecho->estado_revision !== 'pendiente') {
            return;
        }

        $usuarios = User::query()
            ->role('Jefe de Grupo')
            ->where('unidad_id', $hecho->unidad_org_id)
            ->where('estado', 'Activo')
            ->get();

        foreach ($usuarios as $usuario) {
            $usuario->notify(new HechoPendienteRevisionNotification($hecho));
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
