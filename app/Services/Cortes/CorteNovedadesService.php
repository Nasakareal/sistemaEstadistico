<?php

namespace App\Services\Cortes;

use Carbon\Carbon;

class CorteNovedadesService
{
    public function horaCorte(): string
    {
        return config('cortes.hora_corte', '18:00:00');
    }

    public function rango(?string $fecha = null): array
    {
        $timezone = config('app.timezone', 'America/Mexico_City');

        $horaCorte = $this->horaCorte();
        [$hora, $minuto, $segundo] = array_pad(explode(':', $horaCorte), 3, 0);

        $base = $fecha
            ? Carbon::parse($fecha, $timezone)
            : Carbon::now($timezone);

        $fin = $base->copy()->setTime((int)$hora, (int)$minuto, (int)$segundo);
        $inicio = $fin->copy()->subDay();

        return [
            'inicio' => $inicio,
            'fin' => $fin,
            'inicio_texto' => $inicio->format('Y-m-d H:i:s'),
            'fin_texto' => $fin->format('Y-m-d H:i:s'),
            'hora_corte' => $horaCorte,
            'label' => $inicio->format('d/m/Y H:i') . ' - ' . $fin->format('d/m/Y H:i'),
        ];
    }

    public function listar(int $dias = 30): array
    {
        $timezone = config('app.timezone', 'America/Mexico_City');
        $hoy = Carbon::now($timezone);

        $lista = [];

        for ($i = 0; $i < $dias; $i++) {
            $fecha = $hoy->copy()->subDays($i)->toDateString();
            $rango = $this->rango($fecha);

            $lista[] = [
                'fecha' => $fecha,
                'label' => $rango['label'],
                'inicio' => $rango['inicio'],
                'fin' => $rango['fin'],
                'inicio_texto' => $rango['inicio_texto'],
                'fin_texto' => $rango['fin_texto'],
                'hora_corte' => $rango['hora_corte'],
            ];
        }

        return $lista;
    }
}
