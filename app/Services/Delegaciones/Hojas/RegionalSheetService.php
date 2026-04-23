<?php

namespace App\Services\Delegaciones\Hojas;

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class RegionalSheetService
{
    public function generar($sheet, string $fecha, string $scope): void
    {
        $sheet->mergeCells('C1:I1');
        $sheet->setCellValue('C1', $scope === 'TOTAL' ? 'DELEGACIONES' : $scope);

        $sheet->setCellValue('B2', 'FECHA');
        $sheet->setCellValue('C2', $this->formatearFecha($fecha));

        $sheet->setCellValue('A3', 'No.');
        $sheet->setCellValue('B3', 'CATEGORÍA');
        $sheet->setCellValue('C3', 'ACTIVIDAD');
        $sheet->setCellValue('D3', 'CANTIDAD');
        $sheet->setCellValue('E3', 'ESTADO DE FUERZA PARTICIPANTE');
        $sheet->setCellValue('F3', 'UNIDADES PARTICIPANTES');
        $sheet->setCellValue('G3', 'KILOMETROS RECORRIDOS');
        $sheet->setCellValue('H3', 'PERSONAS ALCANZADAS');
        $sheet->setCellValue('I3', 'RECOMENDACIONES');

        $this->aplicarFormatoBase($sheet);

        $idsDelegaciones = $this->obtenerIdsDelegaciones($scope);
        $this->llenarInstituciones($sheet, $fecha, $idsDelegaciones);
        $this->llenarReportesC5i($sheet, $fecha, $idsDelegaciones);
        $this->llenarAbanderamientos($sheet, $fecha, $idsDelegaciones);
        $this->llenarOperativos($sheet, $fecha, $idsDelegaciones);
        $this->llenarProgramas($sheet, $fecha, $idsDelegaciones);
    }

    protected function llenarInstituciones($sheet, string $fecha, array $idsDelegaciones): void
    {
        $filaInicio = 4;

        $actividades = [
            'APOYO A EVENTOS PÚBLICOS',
            'APOYO A EVENTOS DEPORTIVOS',
            'APOYO A EVENTOS CULTURALES',
            'APOYO A EVENTOS RELIGIOSOS',
            'APOYOS A OTRAS DEPENDENCIAS (Publicas o privadas)',
            'ESCUELAS',
            'DILIGENCIAS',
            'OTROS TIPOS (Especificar en las novedades relevantes)',
        ];

        $datos = $this->obtenerResumenPorSubcategoria($fecha, $idsDelegaciones, 1);

        $sheet->mergeCells('A4:A11');
        $sheet->mergeCells('B4:B11');

        $sheet->setCellValue('A4', 1);
        $sheet->setCellValue('B4', 'INSTITUCIONES');

        $fila = $filaInicio;

        foreach ($actividades as $actividad) {
            $item = $datos[$actividad] ?? [
                'cantidad' => 0,
                'estado_fuerza' => 0,
                'unidades' => 0,
                'kilometros' => 0,
                'personas' => 0,
                'recomendaciones' => 0,
            ];

            $sheet->setCellValue('C' . $fila, $actividad);
            $sheet->setCellValue('D' . $fila, $item['cantidad']);
            $sheet->setCellValue('E' . $fila, $item['estado_fuerza']);
            $sheet->setCellValue('F' . $fila, $item['unidades']);
            $sheet->setCellValue('G' . $fila, $item['kilometros']);
            $sheet->setCellValue('H' . $fila, $item['personas']);
            $sheet->setCellValue('I' . $fila, $item['recomendaciones']);

            if ($actividad === 'ESCUELAS') {
                $sheet->getStyle('C' . $fila)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '6AA84F'],
                    ],
                ]);
            }

            $fila++;
        }

        $sheet->getStyle('A4:I11')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A4:B11')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('D4:I11')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    protected function obtenerResumenPorSubcategoria(string $fecha, array $idsDelegaciones, int $categoriaId): array
    {
        $registros = DB::table('actividades as a')
            ->join('actividad_subcategorias as s', 'a.actividad_subcategoria_id', '=', 's.id')
            ->select([
                's.nombre as actividad',
                'a.personas_participantes',
                'a.patrullas_participantes_texto',
                'a.kilometro',
                'a.personas_alcanzadas',
            ])
            ->whereDate('a.fecha', $fecha)
            ->where('a.unidad_org_id', 2)
            ->where('a.actividad_categoria_id', $categoriaId)
            ->when(!empty($idsDelegaciones), function ($query) use ($idsDelegaciones) {
                $query->whereIn('a.delegacion_id', $idsDelegaciones);
            })
            ->get();

        $datos = [];

        foreach ($registros as $registro) {
            $actividad = $registro->actividad;

            if (!isset($datos[$actividad])) {
                $datos[$actividad] = [
                    'cantidad' => 0,
                    'estado_fuerza' => 0,
                    'unidades' => 0,
                    'kilometros' => 0,
                    'personas' => 0,
                    'recomendaciones' => 0,
                ];
            }

            $datos[$actividad]['cantidad']++;
            $datos[$actividad]['estado_fuerza'] += (int) ($registro->personas_participantes ?? 0);
            $datos[$actividad]['unidades'] += $this->contarUnidades($registro->patrullas_participantes_texto);
            $datos[$actividad]['kilometros'] += is_numeric($registro->kilometro) ? (float) $registro->kilometro : 0;
            $datos[$actividad]['personas'] += (int) ($registro->personas_alcanzadas ?? 0);
        }

        return $datos;
    }

    protected function obtenerIdsDelegaciones(string $scope): array
    {
        if ($scope === 'TOTAL') {
            return DB::table('delegaciones')
                ->where('activa', 1)
                ->pluck('id')
                ->toArray();
        }

        $region = DB::table('delegaciones')
            ->whereRaw('UPPER(nombre) = ?', [mb_strtoupper($scope)])
            ->whereNull('delegacion_padre_id')
            ->first();

        if (!$region) {
            return [];
        }

        $hijas = DB::table('delegaciones')
            ->where('delegacion_padre_id', $region->id)
            ->pluck('id')
            ->toArray();

        return array_merge([$region->id], $hijas);
    }

    protected function contarUnidades(?string $texto): int
    {
        if (!$texto) {
            return 0;
        }

        $texto = str_replace(["\r\n", "\r", "\n", ';'], ',', $texto);
        $partes = array_filter(array_map('trim', explode(',', $texto)));

        return count($partes);
    }

    protected function aplicarFormatoBase($sheet): void
    {
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(28);
        $sheet->getColumnDimension('C')->setWidth(60);
        $sheet->getColumnDimension('D')->setWidth(16);
        $sheet->getColumnDimension('E')->setWidth(22);
        $sheet->getColumnDimension('F')->setWidth(22);
        $sheet->getColumnDimension('G')->setWidth(32);
        $sheet->getColumnDimension('H')->setWidth(22);
        $sheet->getColumnDimension('I')->setWidth(24);

        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getRowDimension(2)->setRowHeight(30);
        $sheet->getRowDimension(3)->setRowHeight(58);

        $sheet->getStyle('C1:I1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('B2:C2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A3:C3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0070C0'],
            ],
        ]);

        $sheet->getStyle('D3:G3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '00B050'],
            ],
        ]);

        $sheet->getStyle('H3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '00A2E8'],
            ],
        ]);

        $sheet->getStyle('I3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '00B0F0'],
            ],
        ]);

        $sheet->getStyle('A3:I3')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
    }

    protected function formatearFecha(string $fecha): string
    {
        $partes = explode('-', $fecha);

        if (count($partes) !== 3) {
            return $fecha;
        }

        return (int) $partes[2] . '/' . (int) $partes[1] . '/' . $partes[0];
    }

    protected function llenarReportesC5i($sheet, string $fecha, array $idsDelegaciones): void
    {
        $filaInicio = 12;

        $actividades = [
            'OBSTRUCCIÓN DE COCHERAS',
            'OTROS TIPOS DE OBSTRUCCIÓN',
            'ACTOS DELICTIVOS',
            'SINIESTROS',
            'HECHOS DE TRÁNSITO',
            'CONSENTRACION PERSONAS',
            'OTROS REPORTES (Especificar en las novedades relevantes)',
        ];

        $datosActividades = $this->obtenerResumenPorSubcategoria($fecha, $idsDelegaciones, 2);
        $datosSiniestros = $this->obtenerResumenSiniestros($fecha, $idsDelegaciones);

        $sheet->mergeCells('A12:A18');
        $sheet->mergeCells('B12:B18');

        $sheet->setCellValue('A12', 2);
        $sheet->setCellValue('B12', 'REPORTES C5i');

        $fila = $filaInicio;

        foreach ($actividades as $actividad) {
            $item = $datosActividades[$actividad] ?? [
                'cantidad' => 0,
                'estado_fuerza' => 0,
                'unidades' => 0,
                'kilometros' => 0,
                'personas' => 0,
                'recomendaciones' => 0,
            ];

            if ($actividad === 'SINIESTROS') {
                $item = $datosSiniestros;
            }

            if ($actividad === 'HECHOS DE TRÁNSITO') {
                $item = [
                    'cantidad' => 0,
                    'estado_fuerza' => 0,
                    'unidades' => 0,
                    'kilometros' => 0,
                    'personas' => 0,
                    'recomendaciones' => 0,
                ];
            }

            $sheet->setCellValue('C' . $fila, $actividad);
            $sheet->setCellValue('D' . $fila, $item['cantidad']);
            $sheet->setCellValue('E' . $fila, $item['estado_fuerza']);
            $sheet->setCellValue('F' . $fila, $item['unidades']);
            $sheet->setCellValue('G' . $fila, $item['kilometros']);
            $sheet->setCellValue('H' . $fila, $item['personas']);
            $sheet->setCellValue('I' . $fila, $item['recomendaciones']);

            $fila++;
        }

        $sheet->getStyle('A12:I18')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '9DC3E6'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A12:B18')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('D12:I18')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
    protected function obtenerResumenSiniestros(string $fecha, array $idsDelegaciones): array
    {
        $hechos = DB::table('hechos as h')
            ->select([
                'h.id',
                'h.unidad',
                'h.delegacion_id',
            ])
            ->whereDate('h.fecha', $fecha)
            ->where('h.unidad_org_id', 2)
            ->when(!empty($idsDelegaciones), function ($query) use ($idsDelegaciones) {
                $query->whereIn('h.delegacion_id', $idsDelegaciones);
            })
            ->get();

        $hechoIds = $hechos->pluck('id')->toArray();

        $personas = 0;

        if (!empty($hechoIds)) {
            $personas = DB::table('hecho_vehiculo as hv')
                ->join('vehiculo_conductor as vc', 'hv.vehiculo_id', '=', 'vc.vehiculo_id')
                ->whereIn('hv.hecho_id', $hechoIds)
                ->distinct()
                ->count('vc.conductor_id');
        }

        return [
            'cantidad' => $hechos->count(),
            'estado_fuerza' => $hechos->count(),
            'unidades' => $hechos->pluck('unidad')->filter()->unique()->count(),
            'kilometros' => 0,
            'personas' => $personas,
            'recomendaciones' => 0,
        ];
    }

    protected function llenarAbanderamientos($sheet, string $fecha, array $idsDelegaciones): void
    {
        $filaInicio = 19;

        $actividades = [
            'CORTES DE CIRCULACIÓN',
            'ACCIDENTES',
            'MARCHAS',
            'MÍTINES',
            'OBRAS PÚBLICAS',
            'ACOMPAÑAMIENTO A CARAVANAS U OTROS',
            'OTROS ABANDERAMIENTOS (Especificar en las novedades relevantes)',
        ];

        $datos = $this->obtenerResumenPorSubcategoria($fecha, $idsDelegaciones, 3);
        $datosSiniestros = $this->obtenerResumenSiniestros($fecha, $idsDelegaciones);

        $sheet->mergeCells('A19:A25');
        $sheet->mergeCells('B19:B25');

        $sheet->setCellValue('A19', 3);
        $sheet->setCellValue('B19', 'ABANDERAMIENTOS');

        $fila = $filaInicio;

        foreach ($actividades as $actividad) {
            $item = $datos[$actividad] ?? [
                'cantidad' => 0,
                'estado_fuerza' => 0,
                'unidades' => 0,
                'kilometros' => 0,
                'personas' => 0,
                'recomendaciones' => 0,
            ];

            if ($actividad === 'ACCIDENTES') {
                $item = $datosSiniestros;
            }

            $sheet->setCellValue('C' . $fila, $actividad);
            $sheet->setCellValue('D' . $fila, $item['cantidad']);
            $sheet->setCellValue('E' . $fila, $item['estado_fuerza']);
            $sheet->setCellValue('F' . $fila, $item['unidades']);
            $sheet->setCellValue('G' . $fila, $item['kilometros']);
            $sheet->setCellValue('H' . $fila, $item['personas']);
            $sheet->setCellValue('I' . $fila, $item['recomendaciones']);

            $fila++;
        }

        $sheet->getStyle('A19:I25')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A19:B25')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('D19:I25')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    protected function llenarOperativos($sheet, string $fecha, array $idsDelegaciones): void
    {
        $filaInicio = 26;

        $actividades = [
            'ESCUELAS SEGURAS',
            'GUARDIAN VIAL',
            'MONITOREO INTEGRAL',
            'CONCIENTIZACIÓN USO DE CASCO',
            'AMPLIACION CARRETERA CIUDAD HIDALGO-ZITACUARO',
            'SEGURIDAD EN CARRETERAS - ZINAPECUARO - MORELIA',
            'SEGURIDAD EN CARRETERAS - SALAMANCA - MORELIA',
            'APOYO COCOTRA',
            'BASES DE OPERACIONES INTERINSTITUCIONAL',
            'OTROS OPERATIVOS (Especificar en las novedades relevantes)',
        ];

        $datos = $this->obtenerResumenPorSubcategoria($fecha, $idsDelegaciones, 4);

        $sheet->mergeCells('A26:A35');
        $sheet->mergeCells('B26:B35');

        $sheet->setCellValue('A26', 4);
        $sheet->setCellValue('B26', 'OPERATIVOS');

        $fila = $filaInicio;

        foreach ($actividades as $actividad) {
            $item = $datos[$actividad] ?? [
                'cantidad' => 0,
                'estado_fuerza' => 0,
                'unidades' => 0,
                'kilometros' => 0,
                'personas' => 0,
                'recomendaciones' => 0,
            ];

            $sheet->setCellValue('C' . $fila, $actividad);
            $sheet->setCellValue('D' . $fila, $item['cantidad']);
            $sheet->setCellValue('E' . $fila, $item['estado_fuerza']);
            $sheet->setCellValue('F' . $fila, $item['unidades']);
            $sheet->setCellValue('G' . $fila, $item['kilometros']);
            $sheet->setCellValue('H' . $fila, $item['personas']);
            $sheet->setCellValue('I' . $fila, $item['recomendaciones']);

            $fila++;
        }

        $sheet->getStyle('A26:I35')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '9DC3E6'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A26:B35')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('D26:I35')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('C26')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '6AA84F'],
            ],
        ]);

        $sheet->getStyle('C27')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFF00'],
            ],
        ]);

        $sheet->getStyle('C28')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F4B183'],
            ],
        ]);
    }

    protected function llenarProgramas($sheet, string $fecha, array $idsDelegaciones): void
    {
        $filaInicio = 36;

        $actividades = [
            'CONDUCE SIN ALCOHOL (ALCOHOLÍMETRO)',
            'OTROS PROGRAMAS (Especificar en las novedades relevantes)',
        ];

        $datos = $this->obtenerResumenPorSubcategoria($fecha, $idsDelegaciones, 5);

        $sheet->mergeCells('A36:A37');
        $sheet->mergeCells('B36:B37');

        $sheet->setCellValue('A36', 5);
        $sheet->setCellValue('B36', 'PROGRAMAS');

        $fila = $filaInicio;

        foreach ($actividades as $actividad) {
            $item = $datos[$actividad] ?? [
                'cantidad' => 0,
                'estado_fuerza' => 0,
                'unidades' => 0,
                'kilometros' => 0,
                'personas' => 0,
                'recomendaciones' => 0,
            ];

            $sheet->setCellValue('C' . $fila, $actividad);
            $sheet->setCellValue('D' . $fila, $item['cantidad']);
            $sheet->setCellValue('E' . $fila, $item['estado_fuerza']);
            $sheet->setCellValue('F' . $fila, $item['unidades']);
            $sheet->setCellValue('G' . $fila, $item['kilometros']);
            $sheet->setCellValue('H' . $fila, $item['personas']);
            $sheet->setCellValue('I' . $fila, $item['recomendaciones']);

            $fila++;
        }

        $sheet->getStyle('A36:I37')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A36:B37')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('D36:I37')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
}
