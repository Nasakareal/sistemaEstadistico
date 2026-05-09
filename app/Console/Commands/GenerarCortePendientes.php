<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Hechos;
use App\Models\PendientesCorte;
use App\Models\PendientesCorteDetalle;
use App\Services\PendientesCortesService;
use Carbon\Carbon;

class GenerarCortePendientes extends Command
{
    protected $signature = 'hechos:corte-pendientes';

    protected $description = 'Genera el corte semanal de hechos pendientes para Siniestros y Delegaciones (domingo 6pm).';

    public function handle(PendientesCortesService $cortesService)
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

        $corteModel = PendientesCorte::firstOrCreate(
            ['corte_fecha' => $corteFecha],
            [
                'generado_by' => null,
                'observaciones' => null,
            ]
        );

        $pendientesQuery = Hechos::where('situacion', 'PENDIENTE')
            ->where('created_at', '<=', $corte);

        $cortesService->applyHechosUnidadesScope($pendientesQuery, [
            PendientesCortesService::UNIDAD_SINIESTROS_ID,
            PendientesCortesService::UNIDAD_DELEGACIONES_ID,
        ]);

        $pendientes = $pendientesQuery->orderBy('id')->get();

        $creados = 0;
        $actualizados = 0;

        foreach ($pendientes as $hecho) {
            $detalle = PendientesCorteDetalle::updateOrCreate(
                [
                    'pendientes_corte_id' => $corteModel->id,
                    'hecho_id' => $hecho->id,
                ],
                [
                    'situacion_en_corte' => $hecho->situacion,
                ]
            );

            if ($detalle->wasRecentlyCreated) {
                $creados++;
            } elseif ($detalle->wasChanged('situacion_en_corte')) {
                $actualizados++;
            }
        }

        $this->info(($corteModel->wasRecentlyCreated ? 'Corte generado: ' : 'Corte actualizado: ') . $corteFecha);
        $this->info('Pendientes considerados: ' . $pendientes->count());
        $this->info("Detalles nuevos: {$creados}");
        $this->info("Detalles actualizados: {$actualizados}");

        return 0;
    }
}
