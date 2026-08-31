<?php

namespace App\AdminLte\Filters;

use JeroenNoten\LaravelAdminLte\Menu\Filters\FilterInterface;

class UnidadMenuFilter implements FilterInterface
{
    public function transform($item)
    {
        if (self::debeOcultarse($item, auth()->user())) {
            $item['restricted'] = true;
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
}
