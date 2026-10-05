<?php

namespace App\Services;

use App\Models\ConstanciaPregunta;

class ConstanciaExamenCuestionarioService
{
    public const TOTAL_PREGUNTAS = 20;

    public function generar(string $tipoLicencia, string $semilla)
    {
        $preguntas = ConstanciaPregunta::with(['respuestas' => function ($query) {
                $query->orderBy('id');
            }])
            ->where('activo', true)
            ->where(function ($query) use ($tipoLicencia) {
                $query->where('tipo_licencia', $tipoLicencia)
                    ->orWhere('tipo_licencia', 'GENERAL');
            })
            ->orderBy('id')
            ->get()
            ->sortBy(function (ConstanciaPregunta $pregunta) use ($semilla) {
                return hash('sha256', $semilla . '|' . $pregunta->id);
            })
            ->take(self::TOTAL_PREGUNTAS)
            ->values();

        if ($preguntas->count() >= self::TOTAL_PREGUNTAS
            || !in_array($tipoLicencia, ['CHOFER', 'SERVICIO_PUBLICO', 'PERMISO'], true)) {
            return $preguntas;
        }

        $faltantes = self::TOTAL_PREGUNTAS - $preguntas->count();
        $respaldo = ConstanciaPregunta::with(['respuestas' => function ($query) {
                $query->orderBy('id');
            }])
            ->where('activo', true)
            ->where('tipo_licencia', 'AUTOMOVILISTA')
            ->whereNotIn('id', $preguntas->pluck('id'))
            ->orderBy('id')
            ->get()
            ->sortBy(function (ConstanciaPregunta $pregunta) use ($semilla) {
                return hash('sha256', $semilla . '|respaldo|' . $pregunta->id);
            })
            ->take($faltantes)
            ->values();

        return $preguntas->concat($respaldo)->values();
    }
}
