<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Hechos;
use App\Models\PendientesCorte;
use App\Models\PendientesCorteDetalle;
use App\Services\PendientesCortesService;
use Carbon\Carbon;

class ReportePendientesCorte extends Command
{
    protected $signature = 'hechos:generar-corte-pendientes {--corte=} {--prev=} {--json}';

    protected $description = 'Genera el corte semanal de Siniestros y Delegaciones guardando pendientes previos + pendientes actuales.';

    public function handle(PendientesCortesService $cortesService)
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

        $finVentana = $corteActual->copy()->setTime(18, 0, 0);

        $cortePrevioModel = $optPrev
            ? PendientesCorte::where('corte_fecha', $cortePrevio->toDateString())->first()
            : PendientesCorte::where('corte_fecha', '<', $corteActual->toDateString())
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

            if (!empty($idsPrev)) {
                $idsPrevQuery = Hechos::whereIn('id', $idsPrev);
                $cortesService->applyHechosUnidadesScope($idsPrevQuery, [
                    PendientesCortesService::UNIDAD_SINIESTROS_ID,
                    PendientesCortesService::UNIDAD_DELEGACIONES_ID,
                ]);
                $idsPrev = $idsPrevQuery
                    ->pluck('id')
                    ->unique()
                    ->values()
                    ->all();
            }
        }

        $fin = $finVentana->toDateTimeString();

        $pendientesActualesQuery = Hechos::where('situacion', 'PENDIENTE')
            ->whereRaw(
                "STR_TO_DATE(CONCAT(fecha,' ',COALESCE(hora,'00:00:00')), '%Y-%m-%d %H:%i:%s') < ?",
                [$fin]
            );

        $cortesService->applyHechosUnidadesScope($pendientesActualesQuery, [
            PendientesCortesService::UNIDAD_SINIESTROS_ID,
            PendientesCortesService::UNIDAD_DELEGACIONES_ID,
        ]);

        $pendientesActuales = $pendientesActualesQuery
            ->pluck('id')
            ->unique()
            ->values()
            ->all();

        $idsFinal = array_values(array_unique(array_merge($idsPrev, $pendientesActuales)));
        $idsPrevSet = array_fill_keys($idsPrev, true);
        $idsNuevos = array_values(array_filter($pendientesActuales, fn ($id) => !isset($idsPrevSet[$id])));

        DB::transaction(function () use ($corteActualModel, $idsFinal, $cortesService) {
            PendientesCorteDetalle::where('pendientes_corte_id', $corteActualModel->id)->delete();

            if (count($idsFinal) === 0) {
                return;
            }

            $now = now();
            $rows = [];

            $hechosQuery = Hechos::whereIn('id', $idsFinal)
                ->select(['id', 'situacion']);

            $cortesService->applyHechosUnidadesScope($hechosQuery, [
                PendientesCortesService::UNIDAD_SINIESTROS_ID,
                PendientesCortesService::UNIDAD_DELEGACIONES_ID,
            ]);

            $hechos = $hechosQuery->get()->keyBy('id');

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
                'nuevos_pendientes' => count($idsNuevos),
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
        $this->line('Nuevos pendientes: ' . $t['nuevos_pendientes']);
        $this->line('Total guardados en corte actual: ' . $t['total_guardados_en_corte_actual']);

        return 0;
    }
}
