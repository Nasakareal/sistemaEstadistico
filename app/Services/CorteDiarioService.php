<?php

namespace App\Services;

use Carbon\Carbon;

class CorteDiarioService
{
    public function rango(Carbon $corte): array
    {
        $corte = $corte->copy()->timezone('America/Mexico_City');
        $fin = $corte->copy()->setTime(18, 0, 0);
        $inicio = $fin->copy()->subDay();
        return [$inicio, $fin];
    }
}
