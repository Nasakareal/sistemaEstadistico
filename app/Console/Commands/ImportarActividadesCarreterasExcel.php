<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Carreteras\ActividadesCarreterasExcelImportService;
use Illuminate\Console\Command;

class ImportarActividadesCarreterasExcel extends Command
{
    protected $signature = 'actividades:importar-carreteras
        {archivo : Ruta del reporte semanal XLSX de Carreteras}
        {--fuente= : Identificador estable; por defecto ACTIVIDADES_CARRETERAS_AAAA}
        {--user-id= : Usuario que quedara como creador de los registros agregados}
        {--confirmar : Ejecuta la escritura; sin esta opcion solo se simula}
        {--json : Imprime el resultado como JSON}';

    protected $description = 'Importa exclusivamente los totales diarios de actividades de Carreteras, sin tocar puestas a disposicion.';

    public function handle(ActividadesCarreterasExcelImportService $service): int
    {
        $createdBy = $this->option('user-id') !== null && $this->option('user-id') !== ''
            ? (int) $this->option('user-id')
            : null;

        if ($createdBy !== null && !User::query()->whereKey($createdBy)->exists()) {
            $this->error("No existe el usuario {$createdBy}.");
            return self::FAILURE;
        }

        try {
            $plan = $service->planificar(
                (string) $this->argument('archivo'),
                is_string($this->option('fuente')) ? $this->option('fuente') : null
            );
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        if ((bool) $this->option('json')) {
            $this->line(json_encode([
                'modo' => $this->option('confirmar') ? 'escritura' : 'simulacion',
                'fuente' => $plan['fuente'],
                'unidad' => $plan['unidad_nombre'],
                'hojas' => $plan['analisis']['hojas'],
                'puestas_excluidas' => $plan['analisis']['puestas_excluidas'],
                'totales_archivo' => $plan['analisis']['totales'],
                'acciones' => $plan['conteos'],
                'cantidades' => $plan['cantidades'],
                'advertencias' => $plan['analisis']['advertencias'],
                'errores' => $plan['analisis']['errores'],
                'registros_revision' => collect($plan['registros'])
                    ->whereIn('accion', ['advertencia'])
                    ->values()
                    ->all(),
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        } else {
            $this->renderPlan($plan);
        }

        if (!empty($plan['analisis']['errores']) || ($plan['conteos']['error'] ?? 0) > 0) {
            $this->error('La importacion esta bloqueada hasta corregir los errores.');
            return self::FAILURE;
        }

        if (!(bool) $this->option('confirmar')) {
            $this->warn('Simulacion terminada. No se escribio ningun registro.');
            return self::SUCCESS;
        }

        try {
            $result = $service->ejecutar($plan, $createdBy);
        } catch (\Throwable $e) {
            $this->error('No se aplico la importacion: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info(sprintf(
            'Importacion terminada: %d registros agregados con %d actividades; %d filas omitidas. No se modificaron registros existentes ni puestas a disposicion.',
            $result['registros_creados'],
            $result['actividades_agregadas'],
            $result['omitidos']
        ));

        return self::SUCCESS;
    }

    private function renderPlan(array $plan): void
    {
        $this->info('Revision de actividades historicas de Carreteras');
        $this->line('Fuente: ' . $plan['fuente']);
        $this->line('Hojas: ' . implode(', ', $plan['analisis']['hojas']));
        $this->line('Actividades en el archivo: ' . $plan['cantidades']['origen']);
        $this->line('Actividades ya cubiertas: ' . $plan['cantidades']['existente']);
        $this->line('Actividades por agregar: ' . $plan['cantidades']['crear']);
        $this->line('Puestas excluidas: ' . $plan['analisis']['puestas_excluidas']);
        $this->line(sprintf(
            'Plan: %d filas nuevas, %d omitidas y %d advertencias.',
            $plan['conteos']['crear'],
            $plan['conteos']['omitido'],
            $plan['conteos']['advertencia']
        ));

        foreach ($plan['analisis']['advertencias'] as $warning) {
            $this->warn($warning);
        }
        foreach ($plan['analisis']['errores'] as $error) {
            $this->error($error);
        }

        $review = collect($plan['registros'])
            ->where('accion', 'advertencia')
            ->map(fn ($record) => [
                $record['fecha'],
                $record['categoria'],
                $record['cantidad_origen'],
                $record['cantidad_existente'],
                $record['mensaje'],
            ])
            ->values()
            ->all();

        if ($review) {
            $this->table(['Fecha', 'Categoria', 'Archivo', 'Existente', 'Detalle'], $review);
        }
    }
}
