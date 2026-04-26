<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Hechos;
use App\Models\PendientesCorte;
use App\Models\PendientesCorteDetalle;
use Carbon\Carbon;

class GenerarCortePendientes extends Command
{
    protected $signature = 'hechos:corte-pendientes';

    protected $description = 'Genera el corte semanal de hechos pendientes (domingo 6pm).';

    public function handle()
    {
        $tz = 'America/Mexico_City';
        $now = Carbon::now($tz);

        $corte = $now->copy()->previous(Carbon::SUNDAY)->setTime(18, 0, 0);

        if ($now->dayOfWeek === Carbon::SUNDAY) {
            $domingoHoy1800 = $now->copy()->setTime(18, 0, 0);
            if ($now->gte($domingoHoy1800)) {
                $corte = $domingoHoy1800;
            }
        }

        $corteFecha = $corte->toDateString();

        $this->info("NOW  ({$tz}): " . $now->toDateTimeString());
        $this->info("CORTE({$tz}): " . $corte->toDateTimeString());

        $existe = PendientesCorte::where('corte_fecha', $corteFecha)->first();

        if ($existe) {
            $this->info("Ya existe un corte para {$corteFecha}");
            return 0;
        }

        $nuevoCorte = PendientesCorte::create([
            'corte_fecha' => $corteFecha,
            'generado_by' => null,
            'observaciones' => null,
        ]);

        $pendientes = Hechos::where('situacion', 'PENDIENTE')
            ->where('unidad_org_id', 1)
            ->where('created_at', '<=', $corte)
            ->orderBy('id')
            ->get();

        foreach ($pendientes as $hecho) {
            PendientesCorteDetalle::create([
                'pendientes_corte_id' => $nuevoCorte->id,
                'hecho_id' => $hecho->id,
                'situacion_en_corte' => $hecho->situacion,
            ]);
        }

        $this->info("Corte generado: {$corteFecha}");
        $this->info("Pendientes guardados: " . $pendientes->count());

        return 0;
    }
}
