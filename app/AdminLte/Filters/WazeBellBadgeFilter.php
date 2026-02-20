<?php

namespace App\AdminLte\Filters;

use App\Models\WazeAlert;
use Illuminate\Support\Facades\Cache;

class WazeBellBadgeFilter
{
    public function transform($item)
    {
        if (!is_array($item)) {
            return $item;
        }

        if (empty($item['waze_badge'])) {
            return $item;
        }

        $count = Cache::remember('waze_unread_count', 15, function () {
            return WazeAlert::query()
                ->where(function ($q) {
                    $q->whereRaw('UPPER(type) = "ACCIDENT"')
                      ->orWhereRaw('UPPER(subtype) LIKE "%ACCIDENT%"')
                      ->orWhereRaw('UPPER(subtype) LIKE "%CRASH%"')
                      ->orWhereRaw('UPPER(type) LIKE "%CRASH%"');
                })
                ->where(function ($q) {
                    $q->whereNull('is_read')->orWhere('is_read', 0);
                })
                ->count();
        });

        if ((int) $count <= 0) {
            unset($item['label'], $item['label_color'], $item['label_class']);
            return $item;
        }

        $item['label'] = (string) $count;
        $item['label_color'] = 'danger';
        $item['label_class'] = 'badge badge-danger';

        return $item;
    }
}
