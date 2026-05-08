<?php

namespace App\Services\Delegaciones;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DelegacionesExcelRevisionService
{
    private const HOJAS_NO_REGIONALES = [
        'MORELIA_RP',
        'NOV_REL',
        'TOTAL',
    ];

    public function resumen(string $fecha): array
    {
        $tz = 'America/Mexico_City';
        $fecha = Carbon::parse($fecha, $tz)->format('Y-m-d');
        [$inicio, $fin] = $this->rangoCorte($fecha);

        $tempPath = app(ExcelDelegacionesGenerator::class)->generar($fecha);
        $spreadsheet = IOFactory::load($tempPath);

        try {
            $totalSheet = $spreadsheet->getSheetByName('TOTAL');

            if (!$totalSheet) {
                throw new \RuntimeException('El Excel de delegaciones no contiene la hoja TOTAL.');
            }

            $totales = $this->leerResumenHoja($totalSheet);
            $regionales = $this->leerRegionales($spreadsheet);
            $topActividades = $this->leerTopActividades($totalSheet);
            $fuentes = $this->leerFuentes($inicio, $fin);
            $archivoDiario = $this->archivoDiario($fecha);
            $alertas = $this->construirAlertas($fuentes, $totales);

            return [
                'fecha' => $fecha,
                'corte' => [
                    'inicio' => $inicio->toDateTimeString(),
                    'fin' => $fin->toDateTimeString(),
                    'hora' => config('cortes.hora_corte_delegaciones', '17:00:00'),
                    'zona_horaria' => $tz,
                    'label' => $inicio->format('d/m/Y H:i') . ' - ' . $fin->format('d/m/Y H:i'),
                ],
                'excel' => [
                    'generado_para_revision' => true,
                    'archivo_diario' => $archivoDiario,
                ],
                'totales' => $totales,
                'fuentes' => $fuentes,
                'alertas' => $alertas,
                'regionales' => $regionales,
                'top_actividades' => $topActividades,
            ];
        } finally {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }
    }

    private function rangoCorte(string $fecha): array
    {
        $horaCorte = config('cortes.hora_corte_delegaciones', '17:00:00');
        $fin = Carbon::parse($fecha . ' ' . $horaCorte, 'America/Mexico_City');
        $inicio = $fin->copy()->subDay();

        return [$inicio, $fin];
    }

    private function archivoDiario(string $fecha): array
    {
        $nombre = 'excel_delegaciones_' . $fecha . '.xlsx';
        $ruta = storage_path('app/cortes/excel_delegaciones/' . $nombre);
        $existe = File::exists($ruta);

        return [
            'existe' => $existe,
            'nombre' => $nombre,
            'actualizado_at' => $existe
                ? Carbon::createFromTimestamp(File::lastModified($ruta), 'America/Mexico_City')->toDateTimeString()
                : null,
            'size_bytes' => $existe ? File::size($ruta) : 0,
        ];
    }

    private function leerRegionales($spreadsheet): array
    {
        $regionales = [];

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $nombre = $sheet->getTitle();

            if (in_array($nombre, self::HOJAS_NO_REGIONALES, true)) {
                continue;
            }

            $resumen = $this->leerResumenHoja($sheet);
            $alertas = [];

            if (($resumen['hechos_pendientes'] ?? 0) > 0) {
                $alertas[] = 'Tiene hechos pendientes dentro del corte.';
            }

            if (($resumen['hechos_turnados'] ?? 0) > 0) {
                $alertas[] = 'Tiene hechos turnados a MP.';
            }

            if (($resumen['dispositivos'] ?? 0) <= 0 && ($resumen['hechos_total'] ?? 0) <= 0) {
                $estado = 'vacio';
            } elseif (!empty($alertas)) {
                $estado = 'atencion';
            } else {
                $estado = 'ok';
            }

            $regionales[] = [
                'nombre' => $nombre,
                'estado' => $estado,
                'alertas' => $alertas,
            ] + $resumen;
        }

        usort($regionales, function (array $a, array $b) {
            return ($b['dispositivos'] <=> $a['dispositivos'])
                ?: ($b['hechos_total'] <=> $a['hechos_total'])
                ?: strcmp($a['nombre'], $b['nombre']);
        });

        return $regionales;
    }

    private function leerResumenHoja(Worksheet $sheet): array
    {
        return [
            'dispositivos' => $this->intCell($sheet, 'D78'),
            'estado_fuerza' => $this->intCell($sheet, 'E78'),
            'unidades' => $this->intCell($sheet, 'F78'),
            'km_recorridos' => $this->floatCell($sheet, 'G78'),
            'personas_alcanzadas' => $this->intCell($sheet, 'H78'),
            'recomendaciones' => $this->intCell($sheet, 'I78'),
            'control_vehicular_total' => $this->intCell($sheet, 'D94')
                + $this->intCell($sheet, 'E94')
                + $this->intCell($sheet, 'F94')
                + $this->intCell($sheet, 'G94'),
            'aseguramientos_total' => $this->intCell($sheet, 'D110'),
            'hechos_resueltos' => $this->intCell($sheet, 'D120'),
            'hechos_pendientes' => $this->intCell($sheet, 'D121'),
            'hechos_turnados' => $this->intCell($sheet, 'D122'),
            'hechos_total' => $this->intCell($sheet, 'D123'),
            'involucrados_hombres' => $this->intCell($sheet, 'H120'),
            'involucrados_mujeres' => $this->intCell($sheet, 'H121'),
            'involucrados_menores' => $this->intCell($sheet, 'H122'),
            'involucrados_total' => $this->intCell($sheet, 'H123'),
            'monto_danos' => $this->floatCell($sheet, 'H149'),
        ];
    }

    private function leerTopActividades(Worksheet $sheet): array
    {
        $rows = [];

        for ($row = 4; $row <= 77; $row++) {
            $actividad = trim((string) $sheet->getCell('C' . $row)->getCalculatedValue());
            $cantidad = $this->intCell($sheet, 'D' . $row);

            if ($actividad === '' || $cantidad <= 0) {
                continue;
            }

            $rows[] = [
                'actividad' => $actividad,
                'cantidad' => $cantidad,
                'estado_fuerza' => $this->intCell($sheet, 'E' . $row),
                'unidades' => $this->intCell($sheet, 'F' . $row),
                'km_recorridos' => $this->floatCell($sheet, 'G' . $row),
                'personas_alcanzadas' => $this->intCell($sheet, 'H' . $row),
            ];
        }

        usort($rows, fn (array $a, array $b) => $b['cantidad'] <=> $a['cantidad']);

        return array_slice($rows, 0, 10);
    }

    private function leerFuentes(Carbon $inicio, Carbon $fin): array
    {
        $actividadesCorte = DB::table('actividades as a')
            ->whereRaw('TIMESTAMP(a.fecha, a.hora) >= ? AND TIMESTAMP(a.fecha, a.hora) < ?', [
                $inicio->toDateTimeString(),
                $fin->toDateTimeString(),
            ])
            ->where('a.unidad_org_id', 2);

        $hechosPorFecha = DB::table('hechos as h')
            ->whereRaw("TIMESTAMP(h.fecha, COALESCE(h.hora, '00:00:00')) >= ? AND TIMESTAMP(h.fecha, COALESCE(h.hora, '00:00:00')) < ?", [
                $inicio->toDateTimeString(),
                $fin->toDateTimeString(),
            ])
            ->where('h.unidad_org_id', 2);

        $hechosContadosExcel = DB::table('hechos as h')
            ->where('h.captura_completa', 1)
            ->whereNotNull('h.captura_completa_at')
            ->where('h.captura_completa_at', '>=', $inicio->toDateTimeString())
            ->where('h.captura_completa_at', '<', $fin->toDateTimeString())
            ->where('h.unidad_org_id', 2);

        $incompletos = (clone $hechosPorFecha)
            ->where(function ($q) {
                $q->whereNull('h.captura_completa')
                    ->orWhere('h.captura_completa', 0);
            });

        $completadosFueraCorte = (clone $hechosPorFecha)
            ->where('h.captura_completa', 1)
            ->whereNotNull('h.captura_completa_at')
            ->where(function ($q) use ($inicio, $fin) {
                $q->where('h.captura_completa_at', '<', $inicio->toDateTimeString())
                    ->orWhere('h.captura_completa_at', '>=', $fin->toDateTimeString());
            });

        return [
            'actividades_en_corte' => (clone $actividadesCorte)->count(),
            'actividades_sin_delegacion' => (clone $actividadesCorte)->whereNull('a.delegacion_id')->count(),
            'actividades_sin_categoria' => (clone $actividadesCorte)->whereNull('a.actividad_categoria_id')->count(),
            'actividades_sin_subcategoria' => (clone $actividadesCorte)->whereNull('a.actividad_subcategoria_id')->count(),
            'actividades_pendientes_revision' => (clone $actividadesCorte)
                ->where(function ($q) {
                    $q->whereNull('a.estado_revision')
                        ->orWhere('a.estado_revision', '<>', 'aprobado');
                })
                ->count(),
            'hechos_por_fecha_en_corte' => (clone $hechosPorFecha)->count(),
            'hechos_contados_excel' => (clone $hechosContadosExcel)->count(),
            'hechos_incompletos_en_corte' => (clone $incompletos)->count(),
            'hechos_completados_fuera_corte' => (clone $completadosFueraCorte)->count(),
            'hechos_sin_delegacion' => (clone $hechosContadosExcel)->whereNull('h.delegacion_id')->count(),
            'hechos_sin_vehiculos_esperados' => (clone $hechosPorFecha)->where('h.vehiculos_esperados', '<=', 0)->count(),
            'hechos_sin_conductores_esperados' => (clone $hechosPorFecha)->where('h.conductores_esperados', '<=', 0)->count(),
            'hechos_sin_lesionados_esperados' => (clone $hechosPorFecha)->whereNull('h.lesionados_esperados')->count(),
        ];
    }

    private function construirAlertas(array $fuentes, array $totales): array
    {
        $alertas = [];

        $agregar = function (string $tipo, string $titulo, string $detalle, int $conteo) use (&$alertas) {
            if ($conteo <= 0) {
                return;
            }

            $alertas[] = compact('tipo', 'titulo', 'detalle', 'conteo');
        };

        $agregar(
            'critica',
            'Hechos todavía fuera del Excel',
            'Tienen fecha dentro del corte, pero siguen incompletos; el Excel diario los cuenta hasta que se complete la captura.',
            (int) ($fuentes['hechos_incompletos_en_corte'] ?? 0)
        );

        $agregar(
            'aviso',
            'Hechos completados en otro corte',
            'Ocurrieron en este corte, pero su captura completa quedó antes o después de la hora de corte; aparecerán en el corte correspondiente a la finalización.',
            (int) ($fuentes['hechos_completados_fuera_corte'] ?? 0)
        );

        $agregar(
            'critica',
            'Registros sin delegación',
            'No se pueden ubicar con confianza en una regional del Excel.',
            (int) ($fuentes['actividades_sin_delegacion'] ?? 0) + (int) ($fuentes['hechos_sin_delegacion'] ?? 0)
        );

        $agregar(
            'aviso',
            'Actividades sin catálogo completo',
            'Hay capturas sin categoría o subcategoría; pueden no entrar al renglón esperado del formato.',
            (int) ($fuentes['actividades_sin_categoria'] ?? 0) + (int) ($fuentes['actividades_sin_subcategoria'] ?? 0)
        );

        if (($totales['hechos_pendientes'] ?? 0) > 0) {
            $alertas[] = [
                'tipo' => 'aviso',
                'titulo' => 'Hechos pendientes en el conteo',
                'detalle' => 'El Excel ya los cuenta, pero siguen como pendientes en la sección de hechos de tránsito.',
                'conteo' => (int) $totales['hechos_pendientes'],
            ];
        }

        return $alertas;
    }

    private function intCell(Worksheet $sheet, string $cell): int
    {
        $value = $sheet->getCell($cell)->getCalculatedValue();

        if (is_numeric($value)) {
            return (int) round((float) $value);
        }

        return 0;
    }

    private function floatCell(Worksheet $sheet, string $cell): float
    {
        $value = $sheet->getCell($cell)->getCalculatedValue();

        if (is_numeric($value)) {
            return round((float) $value, 2);
        }

        return 0.0;
    }
}
