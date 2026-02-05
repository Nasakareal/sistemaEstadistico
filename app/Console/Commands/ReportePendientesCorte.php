<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Hechos;
use App\Models\PendientesCorte;
use App\Models\PendientesCorteDetalle;
use Carbon\Carbon;

class ReportePendientesCorte extends Command
{
    protected $signature = 'hechos:reporte-pendientes {--corte=} {--prev=} {--json}';

    protected $description = 'Compara pendientes del corte previo contra el corte actual (domingo 6pm).';

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

        $corteActualModel = PendientesCorte::where('corte_fecha', $corteActual->toDateString())->first();
        $cortePrevioModel = PendientesCorte::where('corte_fecha', $cortePrevio->toDateString())->first();

        if (!$cortePrevioModel) {
            return $this->outputResult([
                'ok' => false,
                'message' => 'No existe el corte previo: ' . $cortePrevio->toDateString(),
            ]);
        }

        if (!$corteActualModel) {
            return $this->outputResult([
                'ok' => false,
                'message' => 'No existe el corte actual: ' . $corteActual->toDateString(),
            ]);
        }

        $idsPrev = PendientesCorteDetalle::where('pendientes_corte_id', $cortePrevioModel->id)
            ->pluck('hecho_id')
            ->unique()
            ->values()
            ->all();

        $idsNow = PendientesCorteDetalle::where('pendientes_corte_id', $corteActualModel->id)
            ->pluck('hecho_id')
            ->unique()
            ->values()
            ->all();

        $hechosPrev = Hechos::whereIn('id', $idsPrev)
            ->select(['id', 'folio_c5i', 'fecha', 'sector', 'unidad', 'situacion'])
            ->orderBy('id')
            ->get()
            ->keyBy('id');

        $resueltos = [];
        $turnados = [];
        $siguen = [];
        $otros = [];

        foreach ($idsPrev as $id) {
            $h = $hechosPrev->get($id);
            if (!$h) {
                continue;
            }

            $item = [
                'id' => $h->id,
                'folio_c5i' => $h->folio_c5i,
                'fecha' => (string) $h->fecha,
                'sector' => (string) $h->sector,
                'unidad' => (string) $h->unidad,
                'situacion_actual' => (string) $h->situacion,
                'show_url' => route('hechos.show', $h->id),
            ];

            if ($h->situacion === 'RESUELTO') {
                $resueltos[] = $item;
            } elseif ($h->situacion === 'TURNADO') {
                $turnados[] = $item;
            } elseif ($h->situacion === 'PENDIENTE') {
                $siguen[] = $item;
            } else {
                $otros[] = $item;
            }
        }

        $setPrev = array_fill_keys($idsPrev, true);
        $nuevosPendientes = [];

        $hechosNow = Hechos::whereIn('id', $idsNow)
            ->select(['id', 'folio_c5i', 'fecha', 'sector', 'unidad', 'situacion'])
            ->orderBy('id')
            ->get();

        foreach ($hechosNow as $h) {
            if (isset($setPrev[$h->id])) {
                continue;
            }

            $nuevosPendientes[] = [
                'id' => $h->id,
                'folio_c5i' => $h->folio_c5i,
                'fecha' => (string) $h->fecha,
                'sector' => (string) $h->sector,
                'unidad' => (string) $h->unidad,
                'situacion_actual' => (string) $h->situacion,
                'show_url' => route('hechos.show', $h->id),
            ];
        }

        $result = [
            'ok' => true,
            'corte_previo' => $cortePrevioModel->corte_fecha,
            'corte_actual' => $corteActualModel->corte_fecha,
            'totales' => [
                'previos' => count($idsPrev),
                'resueltos' => count($resueltos),
                'turnados' => count($turnados),
                'siguen_pendiente' => count($siguen),
                'otros' => count($otros),
                'nuevos_pendientes' => count($nuevosPendientes),
            ],
            'detalle' => [
                'resueltos' => $resueltos,
                'turnados' => $turnados,
                'siguen_pendiente' => $siguen,
                'otros' => $otros,
                'nuevos_pendientes' => $nuevosPendientes,
            ],
        ];

        return $this->outputResult($result);
    }

    private function resolveCorteActual(string $tz): Carbon
    {
        $now = Carbon::now($tz);
        $corte = $now->copy()->startOfWeek(Carbon::MONDAY)->subDay()->setTime(18, 0, 0);

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

        $this->info('Corte previo: ' . $payload['corte_previo']);
        $this->info('Corte actual: ' . $payload['corte_actual']);
        $t = $payload['totales'];

        $this->line('Previos: ' . $t['previos']);
        $this->line('Resueltos: ' . $t['resueltos']);
        $this->line('Turnados: ' . $t['turnados']);
        $this->line('Siguen PENDIENTE: ' . $t['siguen_pendiente']);
        $this->line('Otros: ' . $t['otros']);
        $this->line('Nuevos pendientes: ' . $t['nuevos_pendientes']);

        return 0;
    }
}
