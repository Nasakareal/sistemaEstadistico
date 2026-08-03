<?php

namespace App\Services;

use App\Models\ConstanciaPregunta;

class ConstanciaExamenCuestionarioService
{
    public const TOTAL_PREGUNTAS = 20;

    public function generar(string $tipoLicencia, string $semilla)
    {
        return ConstanciaPregunta::with(['respuestas' => function ($query) {
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
    }
}
