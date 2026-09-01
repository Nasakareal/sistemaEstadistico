<?php

namespace App\Services;

use App\Models\ActividadSubcategoria;
use App\Models\LicenciaPuntoInfraccion;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ActividadCorralonInfraccionService
{
    private const CATEGORIA = 'AL CORRALON';

    public function isAlCorralonSubcategoriaId($subcategoriaId): bool
    {
        $id = (int) ($subcategoriaId ?? 0);
        if ($id <= 0) {
            return false;
        }

        $subcategoria = ActividadSubcategoria::query()->with('categoria')->find($id);

        return $subcategoria
            && $this->normalizar(optional($subcategoria->categoria)->nombre) === self::CATEGORIA;
    }

    /**
     * Valida y conserva una instantánea legal para que una boleta histórica no
     * cambie cuando posteriormente se edite el catálogo de infracciones.
     */
    public function validarYSnapshot(int $subcategoriaId, array $selecciones): array
    {
        if (!$this->isAlCorralonSubcategoriaId($subcategoriaId)) {
            return [];
        }

        if ($selecciones === []) {
            throw ValidationException::withMessages([
                'actividad_infracciones' => 'Selecciona al menos una infracción para la actividad Al corralón.',
            ]);
        }

        $resultado = [];
        $vistos = [];
        foreach (array_values($selecciones) as $seleccion) {
            $id = (int) Arr::get($seleccion, 'licencia_punto_infraccion_id', 0);
            $infraccion = LicenciaPuntoInfraccion::activas()->find($id);
            if (!$infraccion
                || (!(bool) $infraccion->retencion_vehiculo
                    && !(bool) $infraccion->deposito_si_sin_persona_habilitada)) {
                throw ValidationException::withMessages([
                    'actividad_infracciones' => 'Una de las infracciones no permite remisión al corralón.',
                ]);
            }

            if (isset($vistos[$id])) {
                throw ValidationException::withMessages([
                    'actividad_infracciones' => 'No puedes seleccionar dos veces la misma infracción.',
                ]);
            }
            $vistos[$id] = true;

            $resultado[] = [
                'id' => (int) $infraccion->id,
                'codigo' => $this->nullable($infraccion->codigo),
                'articulo' => $this->nullable($infraccion->articulo),
                'fraccion' => $this->nullable($infraccion->fraccion),
                'inciso' => $this->nullable($infraccion->inciso),
                'nombre' => $this->nullable($infraccion->nombre) ?: 'Infracción',
                'etiqueta_operativa' => $this->nullable($infraccion->etiqueta_operativa),
                'texto_operativo' => $this->nullable($infraccion->texto_operativo),
                'descripcion' => $this->nullable($infraccion->descripcion),
                'fundamento_legal' => $this->nullable($infraccion->fundamento_legal),
                'referencia_legal_corta' => $this->nullable($infraccion->referencia_legal_corta),
                'resumen_sanciones' => $this->nullable($infraccion->resumen_sanciones),
                'retencion_vehiculo' => (bool) $infraccion->retencion_vehiculo,
                'deposito_si_sin_persona_habilitada' => (bool) $infraccion->deposito_si_sin_persona_habilitada,
            ];
        }

        return $resultado;
    }

    private function normalizar($value): string
    {
        return preg_replace('/\s+/', ' ', Str::upper(Str::ascii(trim((string) $value)))) ?: '';
    }

    private function nullable($value): ?string
    {
        $text = trim((string) ($value ?? ''));
        return $text === '' ? null : $text;
    }
}
