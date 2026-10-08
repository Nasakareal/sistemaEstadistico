<?php

namespace App\AdminLte\Filters;

use JeroenNoten\LaravelAdminLte\Menu\Filters\FilterInterface;

class UnidadMenuFilter implements FilterInterface
{
    public function transform($item)
    {
        $usuario = auth()->user();

        if (self::debeOcultarse($item, $usuario)) {
            $item['restricted'] = true;
        }

        if (self::debePermanecerAbierto($item, $usuario)) {
            $item['force_open'] = true;
        }

        return $item;
    }

    public static function debeOcultarse(array $item, $usuario): bool
    {
        if (!$usuario || empty($item['hide_for_units'])) {
            return false;
        }

        $unidadesOcultas = array_map('intval', (array) $item['hide_for_units']);

        return in_array((int) ($usuario->unidad_id ?? 0), $unidadesOcultas, true);
    }

    public static function debePermanecerAbierto(array $item, $usuario): bool
    {
        $regla = $item['always_open_for'] ?? null;

        if (!$usuario || !is_array($regla)) {
            return false;
        }

        $unidades = array_map('intval', (array) ($regla['units'] ?? []));
        $roles = array_values(array_filter((array) ($regla['roles'] ?? []), 'is_string'));

        if (!in_array((int) ($usuario->unidad_id ?? 0), $unidades, true) || empty($roles)) {
            return false;
        }

        return method_exists($usuario, 'hasAnyRole')
            ? $usuario->hasAnyRole($roles)
            : (method_exists($usuario, 'hasRole') && collect($roles)->contains(
                fn ($rol) => $usuario->hasRole($rol)
            ));
    }
}
