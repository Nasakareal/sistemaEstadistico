<?php

namespace App\Services;

use App\Models\Personal;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class OperativosService
{
    public function contarEnServicio(Carbon $momento, EstadoFuerzaService $estadoFuerzaService): int
    {
        $momento = $momento->copy()->timezone('America/Mexico_City');

        $q = Personal::query()->with(['incidencias.tipo', 'turno']);

        if (Schema::hasColumn('personals', 'es_operativo')) {
            $q->where('es_operativo', 1);
        } elseif (Schema::hasColumn('personals', 'tipo')) {
            $q->whereRaw('UPPER(TRIM(tipo)) = ?', ['OPERATIVO']);
        } elseif (Schema::hasColumn('personals', 'categoria')) {
            $q->whereRaw('UPPER(TRIM(categoria)) = ?', ['OPERATIVO']);
        }

        $personales = $q->get();

        $count = 0;
        foreach ($personales as $p) {
            if ($estadoFuerzaService->estado($p, $momento) === 'EN_SERVICIO') {
                $count++;
            }
        }

        return $count;
    }
}
