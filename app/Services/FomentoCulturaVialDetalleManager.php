<?php

namespace App\Services;

use App\Models\Actividad;
use App\Models\ActividadCategoria;
use App\Models\FomentoCulturaVialDetalle;
use App\Models\FomentoCulturaVialPrograma;
use App\Models\Unidad;
use Illuminate\Support\Str;

class FomentoCulturaVialDetalleManager
{
    public const NUMERIC_FIELDS = [
        'ninas',
        'ninos',
        'adolescentes_mujeres',
        'adolescentes_hombres',
        'docentes_hombres',
        'docentes_mujeres',
        'hombres',
        'mujeres',
    ];

    public static function validationRules(string $prefix = 'fomento.'): array
    {
        return [
            rtrim($prefix, '.') => 'nullable|array',
            $prefix . 'programa_id' => 'nullable|integer|exists:fomento_cultura_vial_programas,id',
            $prefix . 'nivel_educativo' => 'nullable|string|max:120',
            $prefix . 'sector' => 'nullable|string|max:120',
            $prefix . 'escuela' => 'nullable|string|max:255',
            $prefix . 'nombre_institucion' => 'nullable|string|max:255',
            $prefix . 'domicilio' => 'nullable|string|max:255',
            $prefix . 'ninas' => 'nullable|integer|min:0|max:999999',
            $prefix . 'ninos' => 'nullable|integer|min:0|max:999999',
            $prefix . 'adolescentes_mujeres' => 'nullable|integer|min:0|max:999999',
            $prefix . 'adolescentes_hombres' => 'nullable|integer|min:0|max:999999',
            $prefix . 'docentes_hombres' => 'nullable|integer|min:0|max:999999',
            $prefix . 'docentes_mujeres' => 'nullable|integer|min:0|max:999999',
            $prefix . 'hombres' => 'nullable|integer|min:0|max:999999',
            $prefix . 'mujeres' => 'nullable|integer|min:0|max:999999',
            $prefix . 'total_poblacion_atendida' => 'nullable|integer|min:0|max:999999',
        ];
    }

    public function categoriaIds(): array
    {
        return ActividadCategoria::query()
            ->get(['id', 'nombre', 'slug'])
            ->filter(function ($categoria) {
                return $this->categoriaDataEsFomento($categoria->nombre ?? null, $categoria->slug ?? null);
            })
            ->pluck('id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->values()
            ->all();
    }

    public function categoriaEsFomento($categoriaId): bool
    {
        $categoriaId = (int) $categoriaId;

        if ($categoriaId <= 0) {
            return false;
        }

        $categoria = ActividadCategoria::query()
            ->whereKey($categoriaId)
            ->first(['id', 'nombre', 'slug']);

        return $categoria
            ? $this->categoriaDataEsFomento($categoria->nombre ?? null, $categoria->slug ?? null)
            : false;
    }

    public function actividadEsFomento(Actividad $actividad): bool
    {
        if ($this->unidadIdEsFomento($actividad->unidad_org_id)) {
            return true;
        }

        if (!$actividad->unidad_org_id && $actividad->relationLoaded('creador')) {
            return $this->usuarioEsFomento($actividad->creador);
        }

        return $this->categoriaEsFomento($actividad->actividad_categoria_id);
    }

    public function usuarioEsFomento($usuario): bool
    {
        if (!$usuario) {
            return false;
        }

        return $this->unidadIdEsFomento($usuario->unidad_id ?? null);
    }

    public function unidadIdEsFomento($unidadId): bool
    {
        $unidadId = (int) $unidadId;

        if ($unidadId <= 0) {
            return false;
        }

        if ($unidadId === 6) {
            return true;
        }

        $unidad = Unidad::query()
            ->whereKey($unidadId)
            ->first(['id', 'nombre', 'slug']);

        if (!$unidad) {
            return false;
        }

        return $this->unidadDataEsFomento($unidad->nombre ?? null, $unidad->slug ?? null);
    }

    public function syncForActividad(Actividad $actividad, array $data): ?FomentoCulturaVialDetalle
    {
        if (!$this->actividadEsFomento($actividad)) {
            $actividad->fomentoCulturaVialDetalle()->delete();
            $actividad->unsetRelation('fomentoCulturaVialDetalle');
            return null;
        }

        $payload = $this->payloadFromData($data);

        $detalle = $actividad->fomentoCulturaVialDetalle()->updateOrCreate(
            ['actividad_id' => $actividad->id],
            $payload
        );

        $actividad->fomentoCulturaVialDetalle()
            ->where('id', '!=', $detalle->id)
            ->delete();

        if ((int) ($actividad->personas_alcanzadas ?? 0) !== (int) $detalle->total_poblacion_atendida) {
            $actividad->personas_alcanzadas = (int) $detalle->total_poblacion_atendida;
            $actividad->save();
        }

        $actividad->setRelation('fomentoCulturaVialDetalle', $detalle);

        return $detalle;
    }

    public function payloadFromData(array $data): array
    {
        $source = $data['fomento'] ?? $data['fomento_cultura_vial'] ?? [];

        if (!is_array($source) || empty($source)) {
            $source = [];

            foreach (array_merge(['programa_id', 'programa_nombre', 'nivel_educativo', 'sector', 'escuela', 'nombre_institucion', 'domicilio', 'total_poblacion_atendida'], self::NUMERIC_FIELDS) as $field) {
                if (array_key_exists($field, $data)) {
                    $source[$field] = $data[$field];
                }
            }
        }

        $programa = $this->programaValido($source['programa_id'] ?? null, $data['actividad_subcategoria_id'] ?? null);

        $payload = [
            'fomento_cultura_vial_programa_id' => $programa ? $programa->id : null,
            'programa_nombre' => $programa ? $programa->nombre : $this->toUpperOrNull($source['programa_nombre'] ?? null),
            'nivel_educativo' => $this->toUpperOrNull($source['nivel_educativo'] ?? null),
            'sector' => $this->toUpperOrNull($source['sector'] ?? null),
            'nombre_institucion' => $this->toUpperOrNull($source['nombre_institucion'] ?? $source['escuela'] ?? null),
            'domicilio' => $this->toUpperOrNull($source['domicilio'] ?? null),
        ];

        $total = 0;

        foreach (self::NUMERIC_FIELDS as $field) {
            $value = max(0, (int) ($source[$field] ?? 0));
            $payload[$field] = $value;
            $total += $value;
        }

        $payload['total_poblacion_atendida'] = $total;

        return $payload;
    }

    private function programaValido($programaId, $subcategoriaId): ?FomentoCulturaVialPrograma
    {
        $programaId = (int) $programaId;

        if ($programaId <= 0) {
            return null;
        }

        $programa = FomentoCulturaVialPrograma::query()
            ->whereKey($programaId)
            ->first(['id', 'actividad_subcategoria_id', 'nombre']);

        if (!$programa) {
            return null;
        }

        $subcategoriaId = (int) $subcategoriaId;

        if ($subcategoriaId > 0 && (int) $programa->actividad_subcategoria_id !== $subcategoriaId) {
            return null;
        }

        return $programa;
    }

    private function categoriaDataEsFomento(?string $nombre, ?string $slug): bool
    {
        $slugBase = trim((string) ($slug ?: $nombre));
        $slugNormalizado = Str::slug($slugBase);

        if (in_array($slugNormalizado, [
            'fomento',
            'fomento-a-la-cultura-vial',
            'fomento-cultura-vial',
            'cultura-vial',
        ], true)) {
            return true;
        }

        return strpos($slugNormalizado, 'fomento') !== false
            && strpos($slugNormalizado, 'cultura-vial') !== false;
    }

    private function unidadDataEsFomento(?string $nombre, ?string $slug): bool
    {
        $slugBase = trim((string) ($slug ?: $nombre));
        $slugNormalizado = Str::slug($slugBase);

        return in_array($slugNormalizado, [
            'fomento',
            'fomento-a-la-cultura-vial',
            'fomento-cultura-vial',
            'cultura-vial',
        ], true) || (
            strpos($slugNormalizado, 'fomento') !== false
            && strpos($slugNormalizado, 'cultura-vial') !== false
        );
    }

    private function toUpperOrNull($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : mb_strtoupper($value, 'UTF-8');
    }
}
