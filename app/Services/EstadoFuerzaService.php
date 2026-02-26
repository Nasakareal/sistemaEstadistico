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

        $incActiva = $personal->incidencias
            ? $personal->incidencias->first(function ($inc) use ($momento) {
                $inicioRaw = $inc->fecha_inicio ?? null;
                $finRaw = $inc->fecha_fin ?? null;

                if (!$inicioRaw) return false;

                $inicio = Carbon::parse($inicioRaw, 'America/Mexico_City')->startOfDay();
                $fin = $finRaw ? Carbon::parse($finRaw, 'America/Mexico_City')->endOfDay() : null;

                if ($fin) {
                    return $momento->between($inicio, $fin);
                }

                return $momento->greaterThanOrEqualTo($inicio);
            })
            : null;

        if ($incActiva) {
            $tipoNombre = '';

            if (isset($incActiva->tipo)) {
                if (is_object($incActiva->tipo) && isset($incActiva->tipo->nombre)) {
                    $tipoNombre = (string) $incActiva->tipo->nombre;
                } elseif (is_string($incActiva->tipo)) {
                    $tipoNombre = (string) $incActiva->tipo;
                }
            }

            $tipoNombre = strtoupper(trim($tipoNombre));

            if ($tipoNombre === 'COMISION') return 'COMISIONADOS';
            if ($tipoNombre === 'VACACIONES') return 'VACACIONES';
            if ($tipoNombre === 'INCAPACIDAD') return 'INCAPACIDAD';
            if ($tipoNombre === 'PERMISO') return 'PERMISO';
            if ($tipoNombre === 'CURSOS') return 'CURSOS';
            if ($tipoNombre === 'FALTA') return 'FALTANDO';

            return 'OTROS';
        }

        $turno = $personal->turno;

        if (!$turno || !$turno->tipo_rol) {
            return 'SIN_TURNO';
        }

        $tipoRol = strtoupper(trim((string) $turno->tipo_rol));
        $nombreTurno = strtoupper(trim((string) ($turno->nombre ?? '')));

        if ($tipoRol === 'SUBDIRECTOR' || str_contains($nombreTurno, 'SUBDIRECTOR')) {
            return 'EN_SERVICIO';
        }

        if ($tipoRol === 'LUN_VIE') {
            $dow = (int) $momento->dayOfWeekIso;
            return ($dow >= 1 && $dow <= 5) ? 'EN_SERVICIO' : 'FRANCO';
        }

        if ($turno->tipo_rol === '24X24') {
            if (!$turno->ciclo_inicio || !$turno->trabajo_horas || !$turno->descanso_horas) {
                return 'SIN_CONFIG_TURNO';
            }

            $inicio = Carbon::parse($turno->ciclo_inicio, 'America/Mexico_City');

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
