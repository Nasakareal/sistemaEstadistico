<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TurnoService
{
    public function turnoTrabajaEn($turno, Carbon $fechaHora): bool
    {
        if (!$turno) {
            return true;
        }

        $fechaHora = $fechaHora->copy()->timezone('America/Mexico_City');

        $tipoRol = strtoupper(trim((string) ($turno->tipo_rol ?? '')));
        $nombreTurno = strtoupper(trim((string) ($turno->nombre ?? '')));
        $slugTurno = strtoupper(trim((string) ($turno->slug ?? '')));

        if (
            $tipoRol === 'SUBDIRECTOR' ||
            str_contains($nombreTurno, 'SUBDIRECTOR') ||
            str_contains($slugTurno, 'SUBDIRECTOR') ||
            $tipoRol === 'SIEMPRE'
        ) {
            return true;
        }

        if ($tipoRol === 'LUN_VIE') {
            $dow = (int) $fechaHora->dayOfWeekIso;
            return $dow >= 1 && $dow <= 5;
        }

        if ($tipoRol === 'SAB_DOM') {
            $dow = (int) $fechaHora->dayOfWeekIso;
            return $dow === 6 || $dow === 7;
        }

        if (!in_array($tipoRol, ['24X24', 'RADIO_24X24', 'RADIO_12X36'], true)) {
            return true;
        }

        if (!$turno->ciclo_inicio || !$turno->trabajo_horas || $turno->descanso_horas === null) {
            return true;
        }

        $inicio = Carbon::parse($turno->ciclo_inicio, 'America/Mexico_City');

        $diffHoras = $inicio->diffInHours($fechaHora, false);
        if ($diffHoras < 0) {
            return false;
        }

        $trabajo = (int) $turno->trabajo_horas;
        $descanso = (int) $turno->descanso_horas;

        if ($trabajo <= 0 || $descanso < 0) {
            return true;
        }

        $ciclo = $trabajo + $descanso;
        if ($ciclo <= 0) {
            return true;
        }

        $pos = $diffHoras % $ciclo;

        return $pos < $trabajo;
    }

    public function turnoActivoEn(Carbon $fechaHora)
    {
        $fechaHora = $fechaHora->copy()->timezone('America/Mexico_City');

        $turnos = DB::table('turnos')
            ->where('activo', 1)
            ->where('tipo_rol', '24X24')
            ->whereNotNull('ciclo_inicio')
            ->whereNotNull('trabajo_horas')
            ->whereNotNull('descanso_horas')
            ->orderBy('id')
            ->get();

        foreach ($turnos as $turno) {
            if ((int) $turno->trabajo_horas <= 0 || (int) $turno->descanso_horas < 0) {
                continue;
            }

            if ($this->turnoTrabajaEn($turno, $fechaHora)) {
                return $turno;
            }
        }

        return null;
    }

    public function inicioDeBloqueTrabajoActual($turno, Carbon $fechaHora): Carbon
    {
        $fechaHora = $fechaHora->copy()->timezone('America/Mexico_City');

        $inicio = Carbon::parse($turno->ciclo_inicio, 'America/Mexico_City');
        $trabajo = (int) $turno->trabajo_horas;
        $descanso = (int) $turno->descanso_horas;

        $ciclo = $trabajo + $descanso;

        $diffHoras = $inicio->diffInHours($fechaHora, false);
        if ($diffHoras < 0) {
            return $inicio;
        }

        $pos = $diffHoras % $ciclo;

        return $fechaHora->copy()->subHours($pos);
    }
}
