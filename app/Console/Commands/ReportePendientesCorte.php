<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Hechos;
use App\Models\PendientesCorte;
use App\Models\PendientesCorteDetalle;
use Carbon\Carbon;

class GenerarPendientesCorte extends Command
{
    protected $signature = 'hechos:generar-corte-pendientes {--corte=} {--prev=} {--json}';

    protected $description = 'Genera el corte semanal (domingo 6pm) guardando SOLO: pendientes del corte pasado + nuevos pendientes de la semana.';

    public function handle()
    {
        $tz = 'America/Mexico_City';

        $corteActual = $this->resolveCorteActual($tz);
        $cortePrevio = $corteActual->copy()->subWeek();

        $optCorte = $this->option('corte');
        $optPrev = $this->option('prev');

        if ($optCorte) {
            $corteActual = Carbon::parse($optCorte, $tz)->startOfDay();
        }

        if ($optPrev) {
            $cortePrevio = Carbon::parse($optPrev, $tz)->startOfDay();
        }

        $inicioVentana = $cortePrevio->copy()->setTime(18, 0, 0);
        $finVentana = $corteActual->copy()->setTime(18, 0, 0);

        $cortePrevioModel = PendientesCorte::where('corte_fecha', '<', $corteActual->toDateString())
            ->orderByDesc('corte_fecha')
            ->first();

        $corteActualModel = PendientesCorte::firstOrCreate(
            ['corte_fecha' => $corteActual->toDateString()]
        );

        $idsPrev = [];
        if ($cortePrevioModel) {
            $idsPrev = PendientesCorteDetalle::where('pendientes_corte_id', $cortePrevioModel->id)
                ->pluck('hecho_id')
                ->unique()
                ->values()
                ->all();
        }

        $inicio = $inicioVentana->toDateTimeString();
        $fin = $finVentana->toDateTimeString();

        $nuevosSemana = Hechos::where('situacion', 'PENDIENTE')
            ->whereRaw(
                "STR_TO_DATE(CONCAT(fecha,' ',COALESCE(hora,'00:00:00')), '%Y-%m-%d %H:%i:%s') >= ?
                 AND STR_TO_DATE(CONCAT(fecha,' ',COALESCE(hora,'00:00:00')), '%Y-%m-%d %H:%i:%s') < ?",
                [$inicio, $fin]
            )
            ->pluck('id')
            ->unique()
            ->values()
            ->all();

        $idsFinal = array_values(array_unique(array_merge($idsPrev, $nuevosSemana)));

        DB::transaction(function () use ($corteActualModel, $idsFinal) {
            PendientesCorteDetalle::where('pendientes_corte_id', $corteActualModel->id)->delete();

            if (count($idsFinal) === 0) {
                return;
            }

            $now = now();
            $rows = [];

            $hechos = Hechos::whereIn('id', $idsFinal)
                ->select(['id', 'situacion'])
                ->get()
                ->keyBy('id');

            foreach ($idsFinal as $hechoId) {
                $h = $hechos->get($hechoId);
                if (!$h) {
                    continue;
                }

                $rows[] = [
                    'pendientes_corte_id' => $corteActualModel->id,
                    'hecho_id' => $hechoId,
                    'situacion_en_corte' => (string) $h->situacion,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            PendientesCorteDetalle::insert($rows);
        });

        $payload = [
            'ok' => true,
            'corte_actual' => $corteActualModel->corte_fecha,
            'corte_previo' => $cortePrevioModel ? $cortePrevioModel->corte_fecha : null,
            'totales' => [
                'arrastrados_del_corte_previo' => count($idsPrev),
                'nuevos_de_la_semana' => count($nuevosSemana),
                'total_guardados_en_corte_actual' => count($idsFinal),
            ],
        ];

        return $this->outputResult($payload);
    }

    private function resolveCorteActual(string $tz): Carbon
    {
        $now = Carbon::now($tz);

        $corte = $now->copy()
            ->startOfWeek(Carbon::SUNDAY)
            ->setTime(18, 0, 0);

        if ($now->lt($corte)) {
            $corte = $corte->subWeek();
        }

        return $corte->startOfDay();
    }

    private function outputResult(array $payload): int
    {
        $asJson = (bool) $this->option('json');

        if ($asJson) {
            $this->line(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            return ($payload['ok'] ?? false) ? 0 : 1;
        }

        if (!($payload['ok'] ?? false)) {
            $this->error((string) ($payload['message'] ?? 'Error'));
            return 1;
        }

        $this->info('Corte actual: ' . $payload['corte_actual']);
        $this->info('Corte previo: ' . ($payload['corte_previo'] ?? 'No disponible'));

        $t = $payload['totales'];
        $this->line('Arrastrados del corte previo: ' . $t['arrastrados_del_corte_previo']);
        $this->line('Nuevos de la semana: ' . $t['nuevos_de_la_semana']);
        $this->line('Total guardados en corte actual: ' . $t['total_guardados_en_corte_actual']);

        return 0;
    }
}
