<?php

namespace App\AdminLte\Filters;

use App\Models\ComunicacionDestinatario;
use Illuminate\Support\Facades\Auth;
use JeroenNoten\LaravelAdminLte\Menu\Filters\FilterInterface;

class ComunicacionesBellBadgeFilter implements FilterInterface
{
    public function transform($item)
    {
        if (empty($item['comunicaciones_badge'])) {
            return $item;
        }

        if (!Auth::check()) {
            return $item;
        }

        $pendientes = ComunicacionDestinatario::query()
            ->where('user_id', Auth::id())
            ->whereNull('leido_at')
            ->count();

        if ($pendientes > 0) {
            $item['label'] = $pendientes;
            $item['label_color'] = 'danger';
        } else {
            unset($item['label']);
            unset($item['label_color']);
        }

        return $item;
    }
}
