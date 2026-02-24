<?php

namespace App\Services;

use App\Models\Personal;
use Carbon\Carbon;

class EstadoFuerzaService
{
    public function estado(Personal $personal, ?Carbon $momento = null): string
    {
        $momento = $momento ? $momento->copy() : now('America/Mexico_City');

        if (($personal->estatus ?? '') !== 'ACTIVO') {
            return 'INACTIVO';
        }

        if ($personal->fecha_baja && $momento->toDateString() >= $personal->fecha_baja->toDateString()) {
            return 'INACTIVO';
        }

        $tieneIncidencia = $personal->incidencias
            ? $personal->incidencias->first(function ($inc) use ($momento) {
                $inicio = $inc->fecha_inicio ? Carbon::parse($inc->fecha_inicio)->startOfDay() : null;
                $fin = $inc->fecha_fin ? Carbon::parse($inc->fecha_fin)->endOfDay() : null;

                if (!$inicio) return false;

                if ($fin) {
                    return $momento->between($inicio, $fin);
                }

                return $momento->greaterThanOrEqualTo($inicio);
            })
            : null;

        if ($tieneIncidencia) {
            return 'FUERA_POR_INCIDENCIA';
        }

        $turno = $personal->turno;

        if (!$turno || !$turno->tipo_rol) {
            return 'SIN_TURNO';
        }

        if ($turno->tipo_rol === '24X24') {
            if (!$turno->ciclo_inicio || !$turno->trabajo_horas || !$turno->descanso_horas) {
                return 'SIN_CONFIG_TURNO';
            }

            $inicio = Carbon::parse($turno->ciclo_inicio);

            $diffHoras = $inicio->diffInHours($momento, false);
            if ($diffHoras < 0) {
                return 'FRANCO';
            }

            $trabajo = (int) $turno->trabajo_horas;
            $descanso = (int) $turno->descanso_horas;
            $ciclo = $trabajo + $descanso;

            $pos = $diffHoras % $ciclo;

            return ($pos < $trabajo) ? 'EN_SERVICIO' : 'FRANCO';
        }

        if ($turno->tipo_rol === 'SIEMPRE') {
            return 'EN_SERVICIO';
        }

        return 'SIN_REGLA';
    }
}
