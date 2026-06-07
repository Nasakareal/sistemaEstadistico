<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    public const UNIDAD_SINIESTROS_ID = 1;
    public const UNIDAD_DELEGACIONES_ID = 2;
    public const UNIDAD_CARRETERAS_ID = 4;
    public const UNIDAD_VIALIDADES_URBANAS_ID = 5;

    private const UNIDADES_NOMBRES = [
        self::UNIDAD_SINIESTROS_ID => 'SINIESTROS',
        self::UNIDAD_DELEGACIONES_ID => 'DELEGACIONES',
        self::UNIDAD_CARRETERAS_ID => 'PROTECCION A CARRETERAS',
        self::UNIDAD_VIALIDADES_URBANAS_ID => 'PROTECCION EN VIALIDADES URBANAS',
    ];

    private const ROLES_EXCLUSIVOS_POR_UNIDAD = [
        self::UNIDAD_SINIESTROS_ID => [
            'Perito',
            'Jefe de Grupo',
        ],
        self::UNIDAD_DELEGACIONES_ID => [
            'Policía',
            'Policia',
            'Delegado',
        ],
        self::UNIDAD_CARRETERAS_ID => [
            'Agente Upec',
            'RT',
            'Encargado de Destacamento',
        ],
        self::UNIDAD_VIALIDADES_URBANAS_ID => [
            'Agente Vial',
            'Motociclista',
            'Responsable de Turno',
        ],
    ];

    protected $fillable = [
        'name',
        'guard_name',
        'unidad_id',
    ];

    public function unidad()
    {
        return $this->belongsTo(Unidad::class);
    }

    public function unidadIdEfectiva(): ?int
    {
        if (!is_null($this->unidad_id)) {
            return (int) $this->unidad_id;
        }

        return self::unidadIdExclusivaPorNombre($this->name);
    }

    public function unidadEfectivaNombre(): ?string
    {
        $unidadId = $this->unidadIdEfectiva();

        if (is_null($unidadId)) {
            return null;
        }

        if (!is_null($this->unidad_id)) {
            $unidad = $this->relationLoaded('unidad') ? $this->unidad : null;
            if ($unidad && !empty($unidad->nombre)) {
                return $unidad->nombre;
            }
        }

        return self::UNIDADES_NOMBRES[$unidadId] ?? 'UNIDAD ' . $unidadId;
    }

    public function esGlobalEfectivo(): bool
    {
        return is_null($this->unidadIdEfectiva());
    }

    public static function unidadIdExclusivaPorNombre(?string $name): ?int
    {
        $normalizado = self::normalizarNombreRol($name);

        if ($normalizado === '') {
            return null;
        }

        foreach (self::ROLES_EXCLUSIVOS_POR_UNIDAD as $unidadId => $nombres) {
            foreach ($nombres as $nombre) {
                if (self::normalizarNombreRol($nombre) === $normalizado) {
                    return (int) $unidadId;
                }
            }
        }

        return null;
    }

    public static function nombresExclusivosParaOtrasUnidades(?int $unidadId): array
    {
        $nombres = [];

        foreach (self::ROLES_EXCLUSIVOS_POR_UNIDAD as $rolUnidadId => $roles) {
            if (!is_null($unidadId) && (int) $rolUnidadId === (int) $unidadId) {
                continue;
            }

            $nombres = array_merge($nombres, $roles);
        }

        return array_values(array_unique($nombres));
    }

    private static function normalizarNombreRol(?string $name): string
    {
        $text = strtoupper(trim((string) $name));
        $text = str_replace(
            ['Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'],
            ['A', 'E', 'I', 'O', 'U', 'N'],
            $text
        );
        $text = preg_replace('/\s+/', ' ', $text) ?: '';

        return trim($text);
    }
}
