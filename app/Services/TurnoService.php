<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TurnoService
{
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
            $inicio = Carbon::parse($turno->ciclo_inicio, 'America/Mexico_City');

            $trabajo = (int) $turno->trabajo_horas;
            $descanso = (int) $turno->descanso_horas;

            if ($trabajo <= 0 || $descanso < 0) {
                continue;
            }

            $ciclo = $trabajo + $descanso;

            $diffHoras = $inicio->diffInHours($fechaHora, false);
            if ($diffHoras < 0) {
                continue;
            }

            $pos = $diffHoras % $ciclo;

            if ($pos >= 0 && $pos < $trabajo) {
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
