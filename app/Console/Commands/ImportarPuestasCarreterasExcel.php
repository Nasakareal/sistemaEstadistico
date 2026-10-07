<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Carreteras\PuestasCarreterasExcelImportService;
use Illuminate\Console\Command;

class ImportarPuestasCarreterasExcel extends Command
{
    protected $signature = 'puestas:importar-carreteras
        {archivo : Ruta del archivo XLSX recibido de Carreteras}
        {--fuente= : Identificador estable de la fuente; por defecto IPH_CARRETERAS_AAAA}
        {--user-id= : Usuario que quedara como creador de los registros nuevos}
        {--confirmar : Ejecuta la escritura; sin esta opcion solo se simula}
        {--vincular-existentes : Vincula coincidencias exactas con registros ya capturados}
        {--json : Imprime el resultado como JSON}';

    protected $description = 'Valida e importa el listado historico de puestas a disposicion de Carreteras.';

    public function handle(PuestasCarreterasExcelImportService $service): int
    {
        $path = (string) $this->argument('archivo');
        $source = $this->option('fuente');
        $createdBy = $this->option('user-id') !== null && $this->option('user-id') !== ''
            ? (int) $this->option('user-id')
            : null;

        if ($createdBy !== null && !User::query()->whereKey($createdBy)->exists()) {
            $this->error("No existe el usuario {$createdBy}.");
            return self::FAILURE;
        }

        try {
            $plan = $service->planificar($path, is_string($source) ? $source : null);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        if ((bool) $this->option('json')) {
            $payload = [
                'modo' => $this->option('confirmar') ? 'escritura' : 'simulacion',
                'fuente' => $plan['fuente'],
                'totales' => $plan['analisis']['totales_detalle'],
                'acciones' => $plan['conteos'],
                'clasificacion' => $plan['clasificacion'],
                'advertencias' => $plan['analisis']['advertencias'],
                'errores' => $plan['analisis']['errores'],
                'registros_revision' => collect($plan['registros'])
                    ->whereIn('accion', ['vincular', 'error'])
                    ->values()
                    ->all(),
            ];
            $this->line(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        } else {
            $this->renderPlan($plan);
        }

        if (!empty($plan['analisis']['errores']) || ($plan['conteos']['error'] ?? 0) > 0) {
            $this->error('La importacion esta bloqueada hasta corregir los errores.');
            return self::FAILURE;
        }

        if (!(bool) $this->option('confirmar')) {
            $this->warn('Simulacion terminada. No se escribio ningun registro.');
            if (($plan['conteos']['vincular'] ?? 0) > 0) {
                $this->line('Para ejecutar despues de revisar las coincidencias: agregue --confirmar --vincular-existentes.');
            } else {
                $this->line('Para ejecutar despues de revisar: agregue --confirmar.');
            }
            return self::SUCCESS;
        }

        try {
            $result = $service->ejecutar(
                $plan,
                $createdBy,
                (bool) $this->option('vincular-existentes')
            );
        } catch (\Throwable $e) {
            $this->error('No se aplico la importacion: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info(sprintf(
            'Importacion terminada: %d creados, %d vinculados y %d omitidos.',
            $result['creados'],
            $result['vinculados'],
            $result['omitidos']
        ));

        return self::SUCCESS;
    }

    private function renderPlan(array $plan): void
    {
        $totals = $plan['analisis']['totales_detalle'];
        $this->info('Revision de listado IPH de Carreteras');
        $this->line('Fuente: ' . $plan['fuente']);
        $this->line('Registros: ' . $totals['puestas']);
        $this->line('Detalle: ' . $totals['faltas'] . ' faltas, ' . $totals['detenciones'] . ' detenciones, ' . $totals['aseguramientos'] . ' aseguramientos.');
        $this->line(sprintf(
            'Plan: %d nuevos, %d para vincular, %d ya importados, %d con error.',
            $plan['conteos']['crear'],
            $plan['conteos']['vincular'],
            $plan['conteos']['omitido'],
            $plan['conteos']['error']
        ));
        $this->line('Tipos destino: ' . collect($plan['clasificacion']['tipos'])->map(function ($total, $tipo) {
            return $tipo . '=' . $total;
        })->implode(', '));

        foreach ($plan['analisis']['advertencias'] as $warning) {
            $this->warn($warning);
        }
        foreach ($plan['analisis']['errores'] as $error) {
            $this->error($error);
        }

        $reviewRows = collect($plan['registros'])
            ->whereIn('accion', ['vincular', 'error'])
            ->map(function ($record) {
                return [
                    $record['secuencia_origen'],
                    $record['fecha_puesta'],
                    $record['destacamento'],
                    $record['accion'],
                    $record['existente_id'] ?: '-',
                    $record['mensaje'],
                ];
            })
            ->values()
            ->all();

        if ($reviewRows) {
            $this->table(
                ['Secuencia', 'Fecha', 'Destacamento', 'Accion', 'ID existente', 'Detalle'],
                $reviewRows
            );
        }
    }
}
