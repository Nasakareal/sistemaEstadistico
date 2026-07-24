<?php

namespace App\Support;

use App\Models\ActividadSubcategoria;
use Illuminate\Support\Collection;

final class ActividadSubcategoriaCaptura
{
    private const UNIDAD_DELEGACIONES_ID = 2;

    public const MENSAJE_OTROS_DELEGACIONES = 'La opción "Otros" ya no está disponible para Delegaciones. Actualiza la aplicación y selecciona una subcategoría específica.';

    public static function filtrarParaUsuario(Collection $subcategorias, $usuario): Collection
    {
        if (!self::usuarioEsDeDelegaciones($usuario)) {
            return $subcategorias;
        }

        return $subcategorias
            ->reject(function ($subcategoria) {
                return self::esOpcionOtros($subcategoria->nombre ?? null);
            })
            ->values();
    }

    public static function permitidaParaUsuario(ActividadSubcategoria $subcategoria, $usuario): bool
    {
        return !self::usuarioEsDeDelegaciones($usuario)
            || !self::esOpcionOtros($subcategoria->nombre);
    }

    public static function mensajeRechazoParaUsuario(
        ActividadSubcategoria $subcategoria,
        $usuario
    ): ?string {
        if (self::usuarioEsDeDelegaciones($usuario) && self::esOpcionOtros($subcategoria->nombre)) {
            return self::MENSAJE_OTROS_DELEGACIONES;
        }

        return null;
    }

    public static function esOpcionOtros(?string $nombre): bool
    {
        $nombreNormalizado = mb_strtoupper(trim((string) $nombre), 'UTF-8');

        return preg_match('/^OTR(?:OS|AS)(?:[^\p{L}\p{N}]|$)/u', $nombreNormalizado) === 1;
    }

    private static function usuarioEsDeDelegaciones($usuario): bool
    {
        return (int) ($usuario->unidad_id ?? 0) === self::UNIDAD_DELEGACIONES_ID;
    }
}
