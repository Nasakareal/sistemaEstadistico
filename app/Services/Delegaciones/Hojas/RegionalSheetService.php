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
        $this->llenarMonitoreos($sheet, $fecha, $idsDelegaciones);
        $this->llenarAuxilioVial($sheet, $fecha, $idsDelegaciones);
        $this->llenarDispositivosSeguridadVial($sheet, $fecha, $idsDelegaciones);
        $this->llenarCapacitaciones($sheet, $fecha, $idsDelegaciones);
        $this->llenarCampanas($sheet, $fecha, $idsDelegaciones);
        $this->llenarProximidadSocial($sheet, $fecha, $idsDelegaciones);
        $this->llenarTotales($sheet);
        $this->llenarControlVehicular($sheet, $fecha, $idsDelegaciones);
        $this->llenarControlAseguramientos($sheet, $fecha, $idsDelegaciones);
        $this->llenarOtrosAseguramientos($sheet, $fecha, $idsDelegaciones);
        $this->llenarHechosTransito($sheet, $fecha, $idsDelegaciones);
        $this->llenarTiposHechosTransito($sheet, $fecha, $idsDelegaciones);
        $this->llenarChoquesDanios($sheet, $fecha, $idsDelegaciones);
        $this->llenarClasificacionVehiculos($sheet, $fecha, $idsDelegaciones);
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
        [$inicio, $fin] = $this->rangoCorte($fecha);

        $registros = DB::table('actividades as a')
            ->join('actividad_subcategorias as s', 'a.actividad_subcategoria_id', '=', 's.id')
            ->select([
                's.nombre as actividad',
                'a.personas_participantes',
                'a.patrullas_participantes_texto',
                'a.kilometro',
                'a.personas_alcanzadas',
            ])
            ->whereRaw("TIMESTAMP(a.fecha, a.hora) >= ? AND TIMESTAMP(a.fecha, a.hora) < ?", [$inicio, $fin])
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
        [$inicio, $fin] = $this->rangoCorte($fecha);
        
        $hechos = DB::table('hechos as h')
            ->select([
                'h.id',
                'h.unidad',
                'h.delegacion_id',
            ])
            ->whereRaw("TIMESTAMP(h.fecha, h.hora) >= ? AND TIMESTAMP(h.fecha, h.hora) < ?", [$inicio, $fin])
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

    protected function llenarMonitoreos($sheet, string $fecha, array $idsDelegaciones): void
    {
        $filaInicio = 38;

        $actividades = [
            'VÍAS FÉRREAS',
            'PERIFÉRICOS',
            'AVENIDAS',
            'TIENDAS DEPARTAMENTALES',
            'BANCOS',
            'GASOLINERAS',
            'OFICINAS GUBERNAMENTALES',
            'MANIFESTACIONES',
            'OTROS MONITOREOS (Especificar en las novedades relevantes)',
        ];

        $datos = $this->obtenerResumenPorSubcategoria($fecha, $idsDelegaciones, 6);

        $sheet->mergeCells('A38:A46');
        $sheet->mergeCells('B38:B46');

        $sheet->setCellValue('A38', 6);
        $sheet->setCellValue('B38', 'MONITOREOS');

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

        $sheet->getStyle('A38:I46')->applyFromArray([
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

        $sheet->getStyle('A38:B46')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('D38:I46')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('C39:C40')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFF00'],
            ],
        ]);

        $sheet->getStyle('C41:C44')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F4B183'],
            ],
        ]);
    }

    protected function llenarAuxilioVial($sheet, string $fecha, array $idsDelegaciones): void
    {
        $filaInicio = 47;

        $actividades = [
            'FALLAS MECÁNICAS',
            'PEATÓN',
            'ESCOLTA EN SITUACIONES DE EMERGENCIA',
            'AGRICOLAS',
            'OTROS AUXILIOS (Especificar en las novedades relevantes)',
        ];

        $datos = $this->obtenerResumenPorSubcategoria($fecha, $idsDelegaciones, 7);

        $sheet->mergeCells('A47:A51');
        $sheet->mergeCells('B47:B51');

        $sheet->setCellValue('A47', 7);
        $sheet->setCellValue('B47', 'AUXILIO VIAL A CONDUCTORES');

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

        $sheet->getStyle('A47:I51')->applyFromArray([
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

        $sheet->getStyle('A47:B51')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('D47:I51')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    protected function llenarDispositivosSeguridadVial($sheet, string $fecha, array $idsDelegaciones): void
    {
        $filaInicio = 52;

        $actividades = [
            'APOYO A LA VIALIDAD',
            'PASO LIBRE DE FUNCIONARIOS',
            'ZONAS DE MAYOR PASE DE TRANSEÚNTES',
            'PASOS PEATONALES',
            'MEDIDAS DE PROTECCIÓN',
            'PATRULLAJES',
            'SERVICIOS DE ESCOLTAS',
            'OTROS (Especificar en las novedades relevantes)',
        ];

        $datos = $this->obtenerResumenPorSubcategoria($fecha, $idsDelegaciones, 8);

        $sheet->mergeCells('A52:A59');
        $sheet->mergeCells('B52:B59');

        $sheet->setCellValue('A52', 8);
        $sheet->setCellValue('B52', 'DISPOSITIVOS DE SEGURIDAD VIAL');

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

        $sheet->getStyle('A52:I59')->applyFromArray([
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

        $sheet->getStyle('A52:B59')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        $sheet->getStyle('D52:I59')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('C52')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFF00'],
            ],
        ]);

        $sheet->getStyle('C55')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFF00'],
            ],
        ]);

        $sheet->getStyle('C57')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFF00'],
            ],
        ]);
    }

    protected function llenarCapacitaciones($sheet, string $fecha, array $idsDelegaciones): void
    {
        $filaInicio = 60;

        $actividades = [
            'TALLER EDUCACIÓN SEGURIDAD VIAL',
            'CAMPAÑA EDUCACIÓN SEGURIDAD VIAL',
            'CAPACITACIONES EDUCACIÓN SEGURIDAD VIAL',
            'MÓDULOS EDUCACIÓN SEGURIDAD VIAL',
            'SSP',
            'CALEA',
            'OTRAS (Especificar en las novedades relevantes)',
        ];

        $datos = $this->obtenerResumenPorSubcategoria($fecha, $idsDelegaciones, 9);

        $sheet->mergeCells('A60:A66');
        $sheet->mergeCells('B60:B66');

        $sheet->setCellValue('A60', 9);
        $sheet->setCellValue('B60', 'CAPACITACIONES');

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

        $sheet->getStyle('A60:I66')->applyFromArray([
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

        $sheet->getStyle('A60:B66')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('D60:I66')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    protected function llenarCampanas($sheet, string $fecha, array $idsDelegaciones): void
    {
        $filaInicio = 67;

        $actividades = [
            'CONCIENTIZACIÓN Y PREVENCIÓN',
            'REPARTICIÓN DE TRÍPTICOS',
            'ESTACIONALES (SEMANA SANTA, NAVIDAD ETC.)',
            'OTRAS (Especificar en las novedades relevantes)',
        ];

        $datos = $this->obtenerResumenPorSubcategoria($fecha, $idsDelegaciones, 10);

        $sheet->mergeCells('A67:A70');
        $sheet->mergeCells('B67:B70');

        $sheet->setCellValue('A67', 10);
        $sheet->setCellValue('B67', 'CAMPAÑAS');

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

        $sheet->getStyle('A67:I70')->applyFromArray([
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

        $sheet->getStyle('A67:B70')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('D67:I70')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    protected function llenarProximidadSocial($sheet, string $fecha, array $idsDelegaciones): void
    {
        $filaInicio = 71;

        $actividades = [
            'PREVENCIÓN SOCIAL',
            'RECORRIDOS DE PROXIMIDAD',
            'APOYO A TURISTAS',
            'APOYO A PERSONAS DE LA TERCERA EDAD',
            'APOYO A PERSONAS PERDIDAS',
            'RECUPERACIÓN DE ESPACIOS',
            'OTRAS (Especificar en las novedades relevantes)',
        ];

        $datos = $this->obtenerResumenPorSubcategoria($fecha, $idsDelegaciones, 11);

        $sheet->mergeCells('A71:A77');
        $sheet->mergeCells('B71:B77');

        $sheet->setCellValue('A71', 11);
        $sheet->setCellValue('B71', 'PROXIMIDAD SOCIAL');

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

        $sheet->getStyle('A71:I77')->applyFromArray([
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

        $sheet->getStyle('A71:B77')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        $sheet->getStyle('D71:I77')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    protected function llenarTotales($sheet): void
    {
        $fila = 78;

        $sheet->mergeCells('A78:B78');

        $sheet->setCellValue('A78', 'TOTAL');
        $sheet->setCellValue('C78', 'DISPOSITIVOS REALIZADOS');
        $sheet->setCellValue('D78', '=SUM(D4:D77)');
        $sheet->setCellValue('E78', '=SUM(E4:E77)');
        $sheet->setCellValue('F78', '=SUM(F4:F77)');
        $sheet->setCellValue('G78', '=SUM(G4:G77)');
        $sheet->setCellValue('H78', '=SUM(H4:H77)');
        $sheet->setCellValue('I78', '=SUM(I4:I77)');

        $sheet->getStyle('A78:I78')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '00B0F0'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('C78')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('D78:I78')->getNumberFormat()->setFormatCode('#,##0');
    }

    protected function llenarControlVehicular($sheet, string $fecha, array $idsDelegaciones): void
    {
        $filaInicio = 80;

        $conceptos = [
            1 => 'REVISIÓN DE ANTECEDENTES',
            2 => 'VEHÍCULOS REVISADOS DE PROCEDENCIA EXTRANJERA',
            3 => 'DESPOLARIZADO',
            4 => 'CORRALON POR FALTAS ADMINISTRATIVAS',
            5 => 'CORRALÓN POR HECHOS DE TRANSITO',
            6 => 'PUESTOS A DISPOSICIÓN DEL MP POR HECHO DE TRÁNSITO',
            7 => 'PRESENTADOS AL MP',
            8 => 'RESGUARDADOS POR ABANDONO',
            9 => 'ASEGURADOS POR HECHOS DELICTIVOS',
            10 => 'RECUPERADOS CON ALTERACIONES EN SUS MEDIOS DE IDENTIFICACIÓN',
            11 => 'RECUPERADOS CON REPORTE DE ROBO',
            12 => 'CONOCIMIENTO DE REPORTE DE ROBO',
            13 => 'ASEGURADOS POR OTROS MOTIVOS',
        ];

        $datos = $this->obtenerResumenControlVehicular($fecha, $idsDelegaciones);

        $sheet->setCellValue('B80', 'No.');
        $sheet->setCellValue('C80', 'CONTROL VEHÍCULAR');
        $sheet->setCellValue('D80', 'VEHÍCULOS');
        $sheet->setCellValue('E80', 'MOTOCICLETAS');
        $sheet->setCellValue('F80', 'CAMIONES');
        $sheet->setCellValue('G80', 'OTROS');

        $fila = 81;

        foreach ($conceptos as $numero => $concepto) {
            $item = $datos[$numero] ?? [
                'vehiculos' => 0,
                'motocicletas' => 0,
                'camiones' => 0,
                'otros' => 0,
            ];

            $sheet->setCellValue('B' . $fila, $numero);
            $sheet->setCellValue('C' . $fila, $concepto);
            $sheet->setCellValue('D' . $fila, $item['vehiculos']);
            $sheet->setCellValue('E' . $fila, $item['motocicletas']);
            $sheet->setCellValue('F' . $fila, $item['camiones']);
            $sheet->setCellValue('G' . $fila, $item['otros']);

            $fila++;
        }

        $sheet->mergeCells('B94:C94');
        $sheet->setCellValue('B94', 'TOTAL');
        $sheet->setCellValue('D94', '=SUM(D81:D93)');
        $sheet->setCellValue('E94', '=SUM(E81:E93)');
        $sheet->setCellValue('F94', '=SUM(F81:F93)');
        $sheet->setCellValue('G94', '=SUM(G81:G93)');

        $sheet->getStyle('B80:G80')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0070C0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->getStyle('B81:G93')->applyFromArray([
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

        $sheet->getStyle('B81:B93')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $sheet->getStyle('D81:G93')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('B94:G94')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '00B0F0'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    protected function obtenerResumenControlVehicular(string $fecha, array $idsDelegaciones): array
    {
        [$inicio, $fin] = $this->rangoCorte($fecha);

        $datos = [];

        for ($i = 1; $i <= 13; $i++) {
            $datos[$i] = [
                'vehiculos' => 0,
                'motocicletas' => 0,
                'camiones' => 0,
                'otros' => 0,
            ];
        }

        $hechos = DB::table('hechos as h')
            ->select([
                'h.id',
                'h.checaron_antecedentes',
                'h.oficio_mp',
                'h.vehiculos_mp',
            ])
            ->whereRaw("TIMESTAMP(h.fecha, h.hora) >= ? AND TIMESTAMP(h.fecha, h.hora) < ?", [$inicio, $fin])
            ->where('h.unidad_org_id', 2)
            ->when(!empty($idsDelegaciones), function ($query) use ($idsDelegaciones) {
                $query->whereIn('h.delegacion_id', $idsDelegaciones);
            })
            ->get();

        foreach ($hechos as $hecho) {
            if ((int) ($hecho->checaron_antecedentes ?? 0) === 1) {
                $datos[1]['vehiculos']++;
            }

            $vehiculosMp = (int) ($hecho->vehiculos_mp ?? 0);

            if ($vehiculosMp > 0 || !empty($hecho->oficio_mp)) {
                $datos[6]['vehiculos'] += $vehiculosMp > 0 ? $vehiculosMp : 1;
            }
        }

        $puestas = DB::table('puestas_disposicion as p')
            ->join('puestas_disposicion_vehiculos as pv', 'p.id', '=', 'pv.puesta_disposicion_id')
            ->select([
                'p.motivo',
                'pv.tipo',
                'pv.calidad',
                'pv.con_reporte_robo',
            ])
            ->whereRaw("TIMESTAMP(p.fecha_puesta, p.hora_puesta) >= ? AND TIMESTAMP(p.fecha_puesta, p.hora_puesta) < ?", [$inicio, $fin])
            ->where('p.unidad_id', 2)
            ->when(!empty($idsDelegaciones), function ($query) use ($idsDelegaciones) {
                $query->whereIn('p.delegacion_id', $idsDelegaciones);
            })
            ->get();

        foreach ($puestas as $puesta) {
            $columna = $this->clasificarTipoVehiculoControl($puesta->tipo);
            $motivo = mb_strtoupper(trim($puesta->motivo ?? ''));
            $calidad = mb_strtoupper(trim($puesta->calidad ?? ''));

            if (str_contains($motivo, 'HECHO DE TRÁNSITO') || str_contains($motivo, 'HECHO DE TRANSITO')) {
                $datos[6][$columna]++;
            } elseif (str_contains($motivo, 'ABANDONO')) {
                $datos[8][$columna]++;
            } elseif (str_contains($motivo, 'HECHO DELICTIVO')) {
                $datos[9][$columna]++;
            } elseif (str_contains($motivo, 'ALTERACION') || str_contains($motivo, 'ALTERACIÓN')) {
                $datos[10][$columna]++;
            } elseif (str_contains($motivo, 'REPORTE DE ROBO') || (int) ($puesta->con_reporte_robo ?? 0) === 1 || $calidad === 'ROBADO') {
                $datos[11][$columna]++;
            } else {
                $datos[13][$columna]++;
            }
        }

        return $datos;
    }

    protected function clasificarTipoVehiculoControl(?string $tipo): string
    {
        $tipo = mb_strtoupper(trim($tipo ?? ''));

        if (str_contains($tipo, 'MOTO')) {
            return 'motocicletas';
        }

        if (str_contains($tipo, 'CAMION') || str_contains($tipo, 'CAMIÓN') || str_contains($tipo, 'TRACTO') || str_contains($tipo, 'TORTON')) {
            return 'camiones';
        }

        if ($tipo === '') {
            return 'otros';
        }

        return 'vehiculos';
    }

    protected function llenarControlAseguramientos($sheet, string $fecha, array $idsDelegaciones): void
    {
        $personas = [
            1 => 'CONSULTA DE ANTECEDENTES PENALES',
            2 => 'PERSONAS A BARANDILLA',
            3 => 'POR ALCOHOLEMIA',
            4 => 'PERSONAS PRESENTADAS AL MP',
            5 => 'POR ROBOS DIVERSOS',
            6 => 'POR LESIONES',
            7 => 'POR HOMICIDIO CULPOSO',
            8 => 'POR HOMICIDIO DOLOSO',
            9 => 'PERSONAS AL MP POR VEHÍCULOS, MOTOS O CAMIONES ROBADOS',
            10 => 'PERSONAS AL MP POR PORTACION DE ARMAS',
            11 => 'PERSONAS AL MP POR DROGA',
            12 => 'OTROS DELITOS',
        ];

        $armas = [
            1 => 'ARMAS',
            2 => 'CORTAS',
            3 => 'LARGAS',
            4 => 'CARGADORES',
            5 => 'CARTUCHOS',
            6 => 'GRANADAS',
            7 => 'LANZA GRANADAS',
            8 => 'PUNZOCORTANTE',
        ];

        $drogas = [
            1 => 'DROGA',
            2 => 'MARIHUANA GRS',
            3 => 'CRISTAL GRS',
            4 => 'COCAINA GRS',
            5 => 'PASTILLAS',
            6 => 'PLANTIOS',
            7 => 'PLANTAS DE MARIHUANA',
            8 => 'OTRAS DROGAS',
        ];

        $datos = $this->obtenerResumenControlAseguramientos($fecha, $idsDelegaciones);

        $sheet->mergeCells('B96:H96');
        $sheet->setCellValue('B96', 'CONTROL DE ASEGURAMIENTOS');

        $sheet->setCellValue('B97', 'No.');
        $sheet->setCellValue('C97', 'PERSONAS ASEGURADAS');
        $sheet->setCellValue('D97', 'TOTAL');
        $sheet->setCellValue('E97', 'ARMAS');
        $sheet->setCellValue('F97', 'TOTAL');
        $sheet->setCellValue('G97', 'DROGA');
        $sheet->setCellValue('H97', 'TOTAL');

        for ($i = 1; $i <= 12; $i++) {
            $fila = 97 + $i;

            $sheet->setCellValue('B' . $fila, $i);
            $sheet->setCellValue('C' . $fila, $personas[$i]);
            $sheet->setCellValue('D' . $fila, $datos['personas'][$i] ?? 0);

            if ($i <= 8) {
                $sheet->setCellValue('E' . $fila, $armas[$i]);
                $sheet->setCellValue('F' . $fila, $datos['armas'][$i] ?? 0);
                $sheet->setCellValue('G' . $fila, $drogas[$i]);
                $sheet->setCellValue('H' . $fila, $datos['drogas'][$i] ?? 0);
            }

            if ($i === 9) {
                $sheet->setCellValue('E' . $fila, 'TOTAL');
                $sheet->setCellValue('F' . $fila, '=SUM(F98:F105)');
                $sheet->setCellValue('G' . $fila, 'TOTAL');
                $sheet->setCellValue('H' . $fila, '=SUM(H98:H105)');
            }
        }

        $sheet->mergeCells('B110:C110');
        $sheet->setCellValue('B110', 'TOTAL');
        $sheet->setCellValue('D110', '=SUM(D98:D109)');

        $sheet->getStyle('B96:H97')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0070C0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->getStyle('B98:H110')->applyFromArray([
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

        $sheet->getStyle('B98:B109')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $sheet->getStyle('D98:D110')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F98:F106')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H98:H106')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('E106:H106')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '00B0F0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('B110:D110')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '00B0F0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    protected function obtenerResumenControlAseguramientos(string $fecha, array $idsDelegaciones): array
    {
        [$inicio, $fin] = $this->rangoCorte($fecha);

        $datos = [
            'personas' => [],
            'armas' => [],
            'drogas' => [],
        ];

        for ($i = 1; $i <= 12; $i++) {
            $datos['personas'][$i] = 0;
        }

        for ($i = 1; $i <= 8; $i++) {
            $datos['armas'][$i] = 0;
            $datos['drogas'][$i] = 0;
        }

        $hechos = DB::table('hechos as h')
            ->select([
                'h.personas_mp',
                'h.checaron_antecedentes',
            ])
            ->whereRaw("TIMESTAMP(h.fecha, h.hora) >= ? AND TIMESTAMP(h.fecha, h.hora) < ?", [$inicio, $fin])
            ->where('h.unidad_org_id', 2)
            ->when(!empty($idsDelegaciones), function ($query) use ($idsDelegaciones) {
                $query->whereIn('h.delegacion_id', $idsDelegaciones);
            })
            ->get();

        foreach ($hechos as $hecho) {
            if ((int) ($hecho->checaron_antecedentes ?? 0) === 1) {
                $datos['personas'][1]++;
            }

            $personasMp = (int) ($hecho->personas_mp ?? 0);

            if ($personasMp > 0) {
                $datos['personas'][4] += $personasMp;
            }
        }

        $personasPuestas = DB::table('puestas_disposicion as p')
            ->join('puestas_disposicion_personas as pp', 'p.id', '=', 'pp.puesta_disposicion_id')
            ->select([
                'p.motivo',
                'p.tipo_puesta',
                'pp.delito_o_motivo',
                'pp.calidad',
            ])
            ->whereRaw("TIMESTAMP(p.fecha_puesta, p.hora_puesta) >= ? AND TIMESTAMP(p.fecha_puesta, p.hora_puesta) < ?", [$inicio, $fin])
            ->where('p.unidad_id', 2)
            ->when(!empty($idsDelegaciones), function ($query) use ($idsDelegaciones) {
                $query->whereIn('p.delegacion_id', $idsDelegaciones);
            })
            ->get();

        foreach ($personasPuestas as $persona) {
            $texto = mb_strtoupper(trim(
                ($persona->motivo ?? '') . ' ' .
                ($persona->tipo_puesta ?? '') . ' ' .
                ($persona->delito_o_motivo ?? '') . ' ' .
                ($persona->calidad ?? '')
            ));

            $datos['personas'][4]++;

            if (str_contains($texto, 'BARANDILLA')) {
                $datos['personas'][2]++;
            } elseif (str_contains($texto, 'ALCOHOL')) {
                $datos['personas'][3]++;
            } elseif (str_contains($texto, 'ROBO') && (str_contains($texto, 'VEHIC') || str_contains($texto, 'MOTO') || str_contains($texto, 'CAMION') || str_contains($texto, 'CAMIÓN'))) {
                $datos['personas'][9]++;
            } elseif (str_contains($texto, 'ROBO')) {
                $datos['personas'][5]++;
            } elseif (str_contains($texto, 'LESION')) {
                $datos['personas'][6]++;
            } elseif (str_contains($texto, 'HOMICIDIO CULPOSO')) {
                $datos['personas'][7]++;
            } elseif (str_contains($texto, 'HOMICIDIO DOLOSO')) {
                $datos['personas'][8]++;
            } elseif (str_contains($texto, 'ARMA')) {
                $datos['personas'][10]++;
            } elseif (str_contains($texto, 'DROGA') || str_contains($texto, 'MARIHUANA') || str_contains($texto, 'CRISTAL') || str_contains($texto, 'COCAINA') || str_contains($texto, 'COCAÍNA')) {
                $datos['personas'][11]++;
            } else {
                $datos['personas'][12]++;
            }
        }

        $objetos = DB::table('puestas_disposicion as p')
            ->join('puestas_disposicion_objetos as po', 'p.id', '=', 'po.puesta_disposicion_id')
            ->select([
                'po.tipo_objeto',
                'po.descripcion',
                'po.cantidad',
                'po.unidad_medida',
            ])
            ->whereRaw("TIMESTAMP(p.fecha_puesta, p.hora_puesta) >= ? AND TIMESTAMP(p.fecha_puesta, p.hora_puesta) < ?", [$inicio, $fin])
            ->where('p.unidad_id', 2)
            ->when(!empty($idsDelegaciones), function ($query) use ($idsDelegaciones) {
                $query->whereIn('p.delegacion_id', $idsDelegaciones);
            })
            ->get();

        foreach ($objetos as $objeto) {
            $cantidad = is_numeric($objeto->cantidad) ? (float) $objeto->cantidad : 1;

            $texto = mb_strtoupper(trim(
                ($objeto->tipo_objeto ?? '') . ' ' .
                ($objeto->descripcion ?? '') . ' ' .
                ($objeto->unidad_medida ?? '')
            ));

            if (str_contains($texto, 'CORTA')) {
                $datos['armas'][2] += $cantidad;
            } elseif (str_contains($texto, 'LARGA')) {
                $datos['armas'][3] += $cantidad;
            } elseif (str_contains($texto, 'CARGADOR')) {
                $datos['armas'][4] += $cantidad;
            } elseif (str_contains($texto, 'CARTUCHO')) {
                $datos['armas'][5] += $cantidad;
            } elseif (str_contains($texto, 'GRANADA') && !str_contains($texto, 'LANZA')) {
                $datos['armas'][6] += $cantidad;
            } elseif (str_contains($texto, 'LANZA')) {
                $datos['armas'][7] += $cantidad;
            } elseif (str_contains($texto, 'PUNZO') || str_contains($texto, 'CUCHILLO') || str_contains($texto, 'NAVAJA')) {
                $datos['armas'][8] += $cantidad;
            } elseif (str_contains($texto, 'ARMA')) {
                $datos['armas'][1] += $cantidad;
            }

            if (str_contains($texto, 'MARIHUANA') && str_contains($texto, 'PLANTA')) {
                $datos['drogas'][7] += $cantidad;
            } elseif (str_contains($texto, 'MARIHUANA') || str_contains($texto, 'CANNABIS')) {
                $datos['drogas'][2] += $cantidad;
            } elseif (str_contains($texto, 'CRISTAL')) {
                $datos['drogas'][3] += $cantidad;
            } elseif (str_contains($texto, 'COCAINA') || str_contains($texto, 'COCAÍNA')) {
                $datos['drogas'][4] += $cantidad;
            } elseif (str_contains($texto, 'PASTILLA')) {
                $datos['drogas'][5] += $cantidad;
            } elseif (str_contains($texto, 'PLANTIO') || str_contains($texto, 'PLANTÍO')) {
                $datos['drogas'][6] += $cantidad;
            } elseif (str_contains($texto, 'DROGA')) {
                $datos['drogas'][1] += $cantidad;
            }
        }

        return $datos;
    }

    protected function llenarOtrosAseguramientos($sheet, string $fecha, array $idsDelegaciones): void
    {
        $datos = $this->obtenerResumenOtrosAseguramientos($fecha, $idsDelegaciones);

        $sheet->setCellValue('B112', 'No.');
        $sheet->setCellValue('C112', 'OTROS ASEGURAMIENTOS');
        $sheet->setCellValue('D112', 'TOTAL');

        $conceptos = [
            1 => 'AGUACATE',
            2 => 'MADERA',
            3 => 'DINERO',
            4 => 'OTROS ASEGURAMIENTOS (AGREGARLOS)',
        ];

        $fila = 113;

        foreach ($conceptos as $numero => $concepto) {
            $sheet->setCellValue('B' . $fila, $numero);
            $sheet->setCellValue('C' . $fila, $concepto);
            $sheet->setCellValue('D' . $fila, $datos[$numero] ?? 0);

            $fila++;
        }

        $sheet->mergeCells('B117:C117');
        $sheet->setCellValue('B117', 'TOTAL');
        $sheet->setCellValue('D117', '=SUM(D113:D116)');

        $sheet->getStyle('B112:D112')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0070C0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->getStyle('B113:D117')->applyFromArray([
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

        $sheet->getStyle('B113:B116')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $sheet->getStyle('D113:D117')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('B117:D117')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '00B0F0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    protected function obtenerResumenOtrosAseguramientos(string $fecha, array $idsDelegaciones): array
    {
        [$inicio, $fin] = $this->rangoCorte($fecha);

        $datos = [
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
        ];

        $objetos = DB::table('puestas_disposicion as p')
            ->join('puestas_disposicion_objetos as po', 'p.id', '=', 'po.puesta_disposicion_id')
            ->select([
                'po.tipo_objeto',
                'po.descripcion',
                'po.cantidad',
                'po.unidad_medida',
            ])
            ->whereRaw("TIMESTAMP(p.fecha_puesta, p.hora_puesta) >= ? AND TIMESTAMP(p.fecha_puesta, p.hora_puesta) < ?", [$inicio, $fin])
            ->where('p.unidad_id', 2)
            ->when(!empty($idsDelegaciones), function ($query) use ($idsDelegaciones) {
                $query->whereIn('p.delegacion_id', $idsDelegaciones);
            })
            ->get();

        foreach ($objetos as $objeto) {
            $cantidad = is_numeric($objeto->cantidad) ? (float) $objeto->cantidad : 1;

            $texto = mb_strtoupper(trim(
                ($objeto->tipo_objeto ?? '') . ' ' .
                ($objeto->descripcion ?? '') . ' ' .
                ($objeto->unidad_medida ?? '')
            ));

            if (str_contains($texto, 'AGUACATE')) {
                $datos[1] += $cantidad;
            } elseif (str_contains($texto, 'MADERA')) {
                $datos[2] += $cantidad;
            } elseif (str_contains($texto, 'DINERO') || str_contains($texto, 'EFECTIVO') || str_contains($texto, 'PESO')) {
                $datos[3] += $cantidad;
            } elseif (!$this->esObjetoArmaDroga($texto)) {
                $datos[4] += $cantidad;
            }
        }

        return $datos;
    }

    protected function esObjetoArmaDroga(string $texto): bool
    {
        return str_contains($texto, 'ARMA')
            || str_contains($texto, 'CORTA')
            || str_contains($texto, 'LARGA')
            || str_contains($texto, 'CARGADOR')
            || str_contains($texto, 'CARTUCHO')
            || str_contains($texto, 'GRANADA')
            || str_contains($texto, 'LANZA')
            || str_contains($texto, 'PUNZO')
            || str_contains($texto, 'CUCHILLO')
            || str_contains($texto, 'NAVAJA')
            || str_contains($texto, 'DROGA')
            || str_contains($texto, 'MARIHUANA')
            || str_contains($texto, 'CANNABIS')
            || str_contains($texto, 'CRISTAL')
            || str_contains($texto, 'COCAINA')
            || str_contains($texto, 'COCAÍNA')
            || str_contains($texto, 'PASTILLA')
            || str_contains($texto, 'PLANTIO')
            || str_contains($texto, 'PLANTÍO');
    }

    protected function llenarHechosTransito($sheet, string $fecha, array $idsDelegaciones): void
    {
        $datos = $this->obtenerResumenHechosTransito($fecha, $idsDelegaciones);
        $involucrados = $this->obtenerResumenInvolucradosHechosTransito($fecha, $idsDelegaciones);

        $sheet->setCellValue('B119', 'No.');
        $sheet->setCellValue('C119', 'HECHOS DE TRÁNSITO');
        $sheet->setCellValue('D119', 'CANTIDAD');

        $conceptos = [
            1 => 'RESUELTOS',
            2 => 'PENDIENTES',
            3 => 'TURNADOS',
        ];

        $fila = 120;

        foreach ($conceptos as $numero => $concepto) {
            $sheet->setCellValue('B' . $fila, $numero);
            $sheet->setCellValue('C' . $fila, $concepto);
            $sheet->setCellValue('D' . $fila, $datos[$concepto] ?? 0);

            $fila++;
        }

        $sheet->mergeCells('B123:C123');
        $sheet->setCellValue('B123', 'TOTAL');
        $sheet->setCellValue('D123', '=SUM(D120:D122)');

        $sheet->getStyle('B119:D119')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0070C0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->getStyle('B120:D123')->applyFromArray([
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

        $sheet->getStyle('B120:B122')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $sheet->getStyle('D120:D123')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('B123:D123')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '00B0F0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);








        $sheet->setCellValue('F119', 'No.');
        $sheet->setCellValue('G119', 'HECHOS DE TRÁNSITO');
        $sheet->setCellValue('H119', 'CANTIDAD');

        $sheet->setCellValue('F120', 1);
        $sheet->setCellValue('G120', 'HOMBRES INVOLUCRADOS');
        $sheet->setCellValue('H120', $involucrados['hombres']);

        $sheet->setCellValue('F121', 2);
        $sheet->setCellValue('G121', 'MUJERES INVOLUCRADAS');
        $sheet->setCellValue('H121', $involucrados['mujeres']);

        $sheet->setCellValue('F122', 3);
        $sheet->setCellValue('G122', 'MENORES INVOLUCRADOS');
        $sheet->setCellValue('H122', $involucrados['menores']);

        $sheet->mergeCells('F123:G123');
        $sheet->setCellValue('F123', 'TOTAL');
        $sheet->setCellValue('H123', '=SUM(H120:H122)');

        $sheet->getStyle('F119:H119')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0070C0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->getStyle('F120:H123')->applyFromArray([
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

        $sheet->getStyle('F120:F122')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $sheet->getStyle('H120:H123')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('F123:H123')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '00B0F0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    protected function obtenerResumenHechosTransito(string $fecha, array $idsDelegaciones): array
    {
        [$inicio, $fin] = $this->rangoCorte($fecha);

        $datos = [
            'RESUELTOS' => 0,
            'PENDIENTES' => 0,
            'TURNADOS' => 0,
        ];

        $hechos = DB::table('hechos as h')
            ->select('h.situacion')
            ->whereRaw("TIMESTAMP(h.fecha, h.hora) >= ? AND TIMESTAMP(h.fecha, h.hora) < ?", [$inicio, $fin])
            ->where('h.unidad_org_id', 2)
            ->when(!empty($idsDelegaciones), function ($query) use ($idsDelegaciones) {
                $query->whereIn('h.delegacion_id', $idsDelegaciones);
            })
            ->get();

        foreach ($hechos as $hecho) {
            $situacion = mb_strtoupper(trim($hecho->situacion ?? ''));

            if ($situacion === 'RESUELTO' || $situacion === 'REPORTE') {
                $datos['RESUELTOS']++;
            } elseif ($situacion === 'TURNADO') {
                $datos['TURNADOS']++;
            } else {
                $datos['PENDIENTES']++;
            }
        }

        return $datos;
    }

    protected function obtenerResumenInvolucradosHechosTransito(string $fecha, array $idsDelegaciones): array
    {
        [$inicio, $fin] = $this->rangoCorte($fecha);

        $conductores = DB::table('hechos as h')
            ->join('hecho_vehiculo as hv', 'h.id', '=', 'hv.hecho_id')
            ->join('vehiculo_conductor as vc', 'hv.vehiculo_id', '=', 'vc.vehiculo_id')
            ->join('conductores as c', 'vc.conductor_id', '=', 'c.id')
            ->select([
                'c.id',
                'c.sexo',
                'c.edad',
            ])
            ->whereRaw("TIMESTAMP(h.fecha, h.hora) >= ? AND TIMESTAMP(h.fecha, h.hora) < ?", [$inicio, $fin])
            ->where('h.unidad_org_id', 2)
            ->when(!empty($idsDelegaciones), function ($query) use ($idsDelegaciones) {
                $query->whereIn('h.delegacion_id', $idsDelegaciones);
            })
            ->distinct()
            ->get();

        $datos = [
            'hombres' => 0,
            'mujeres' => 0,
            'menores' => 0,
        ];

        foreach ($conductores as $conductor) {
            $sexo = mb_strtoupper(trim($conductor->sexo ?? ''));
            $edad = is_numeric($conductor->edad) ? (int) $conductor->edad : null;

            if ($sexo === 'MASCULINO' || $sexo === 'HOMBRE') {
                $datos['hombres']++;
            } elseif ($sexo === 'FEMENINO' || $sexo === 'MUJER') {
                $datos['mujeres']++;
            }

            if ($edad !== null && $edad < 18) {
                $datos['menores']++;
            }
        }

        return $datos;
    }

    protected function llenarTiposHechosTransito($sheet, string $fecha, array $idsDelegaciones): void
    {
        $datos = $this->obtenerResumenTiposHechosTransito($fecha, $idsDelegaciones);

        $sheet->setCellValue('B125', 'No.');
        $sheet->setCellValue('C125', 'HECHOS DE TRÁNSITO');
        $sheet->setCellValue('D125', 'CANTIDAD');
        $sheet->setCellValue('E125', 'LESIONADOS');
        $sheet->setCellValue('F125', 'HERIDOS');
        $sheet->setCellValue('G125', 'DEFUNCIONES');
        $sheet->setCellValue('H125', 'FUERO COMÚN');

        $conceptos = [
            1 => 'EXPLOSIÓN',
            2 => 'INCENDIO',
            3 => 'DESBARRANCAMIENTO',
            4 => 'VOLCADURA',
            5 => 'SALIDA DE RODAMIENTO',
            6 => 'SUBIDA A CAMELLÓN',
            7 => 'CAIDA DE MOTOCICLETA',
            8 => 'CHOQUE OBJETO FIJO',
            9 => 'COLISIÓN POR ALCANCE',
            10 => 'COLISIÓN POR NO RESPETAR SEMÁFORO',
            11 => 'COLISIÓN POR INVASIÓN DE CARRIL',
            12 => 'COLISIÓN POR CAMBIO DE CARRIL',
            13 => 'COLISIÓN POR CORTE DE CIRCULACIÓN',
            14 => 'COLISIÓN POR MANIOBRA REVERSA',
            15 => 'CAIDA A CUNETA',
            16 => 'CAIDA ACUÁTICA DE VEHÍCULO',
            17 => 'COLISIÓN CON PEATÓN',
        ];

        $fila = 126;

        foreach ($conceptos as $numero => $concepto) {
            $item = $datos[$concepto] ?? [
                'cantidad' => 0,
                'lesionados' => 0,
                'heridos' => 0,
                'defunciones' => 0,
                'fuero_comun' => 0,
            ];

            $sheet->setCellValue('B' . $fila, $numero);
            $sheet->setCellValue('C' . $fila, $concepto);
            $sheet->setCellValue('D' . $fila, $item['cantidad']);
            $sheet->setCellValue('E' . $fila, $item['lesionados']);
            $sheet->setCellValue('F' . $fila, $item['heridos']);
            $sheet->setCellValue('G' . $fila, $item['defunciones']);
            $sheet->setCellValue('H' . $fila, $item['fuero_comun']);

            $fila++;
        }

        $sheet->mergeCells('B143:C143');
        $sheet->setCellValue('B143', 'TOTAL');
        $sheet->setCellValue('D143', '=SUM(D126:D142)');
        $sheet->setCellValue('E143', '=SUM(E126:E142)');
        $sheet->setCellValue('F143', '=SUM(F126:F142)');
        $sheet->setCellValue('G143', '=SUM(G126:G142)');
        $sheet->setCellValue('H143', '=SUM(H126:H142)');

        $sheet->getStyle('B125:H125')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0070C0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->getStyle('B126:H143')->applyFromArray([
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

        $sheet->getStyle('B126:B142')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $sheet->getStyle('D126:H143')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('B143:H143')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '00B0F0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    protected function obtenerResumenTiposHechosTransito(string $fecha, array $idsDelegaciones): array
    {
        [$inicio, $fin] = $this->rangoCorte($fecha);

        $conceptos = [
            'EXPLOSIÓN',
            'INCENDIO',
            'DESBARRANCAMIENTO',
            'VOLCADURA',
            'SALIDA DE RODAMIENTO',
            'SUBIDA A CAMELLÓN',
            'CAIDA DE MOTOCICLETA',
            'CHOQUE OBJETO FIJO',
            'COLISIÓN POR ALCANCE',
            'COLISIÓN POR NO RESPETAR SEMÁFORO',
            'COLISIÓN POR INVASIÓN DE CARRIL',
            'COLISIÓN POR CAMBIO DE CARRIL',
            'COLISIÓN POR CORTE DE CIRCULACIÓN',
            'COLISIÓN POR MANIOBRA REVERSA',
            'CAIDA A CUNETA',
            'CAIDA ACUÁTICA DE VEHÍCULO',
            'COLISIÓN CON PEATÓN',
        ];

        $datos = [];

        foreach ($conceptos as $concepto) {
            $datos[$concepto] = [
                'cantidad' => 0,
                'lesionados' => 0,
                'heridos' => 0,
                'defunciones' => 0,
                'fuero_comun' => 0,
            ];
        }

        $hechos = DB::table('hechos as h')
            ->select([
                'h.id',
                'h.tipo_hecho',
            ])
            ->whereRaw("TIMESTAMP(h.fecha, h.hora) >= ? AND TIMESTAMP(h.fecha, h.hora) < ?", [$inicio, $fin])
            ->where('h.unidad_org_id', 2)
            ->when(!empty($idsDelegaciones), function ($query) use ($idsDelegaciones) {
                $query->whereIn('h.delegacion_id', $idsDelegaciones);
            })
            ->get();

        $hechoIds = $hechos->pluck('id')->toArray();

        foreach ($hechos as $hecho) {
            $concepto = $this->normalizarTipoHechoDelegaciones($hecho->tipo_hecho);

            if (isset($datos[$concepto])) {
                $datos[$concepto]['cantidad']++;
            }
        }

        if (!empty($hechoIds)) {
            $lesionados = DB::table('lesionados as l')
                ->join('hechos as h', 'l.hecho_id', '=', 'h.id')
                ->select([
                    'h.tipo_hecho',
                    'l.tipo_lesion',
                ])
                ->whereIn('l.hecho_id', $hechoIds)
                ->get();

            foreach ($lesionados as $lesionado) {
                $concepto = $this->normalizarTipoHechoDelegaciones($lesionado->tipo_hecho);

                if (!isset($datos[$concepto])) {
                    continue;
                }

                $tipoLesion = mb_strtoupper(trim($lesionado->tipo_lesion ?? ''));

                if ($tipoLesion === 'FALLECIDO') {
                    $datos[$concepto]['defunciones']++;
                } else {
                    $datos[$concepto]['lesionados']++;
                }

                if ($tipoLesion === 'GRAVE') {
                    $datos[$concepto]['heridos']++;
                }
            }
        }

        return $datos;
    }

    protected function normalizarTipoHechoDelegaciones(?string $tipo): string
    {
        $tipo = mb_strtoupper(trim($tipo ?? ''));

        $mapa = [
            'EXPLOSIÓN' => 'EXPLOSIÓN',
            'INCENDIO' => 'INCENDIO',
            'DESBARRANCAMIENTO' => 'DESBARRANCAMIENTO',
            'VOLCADURA' => 'VOLCADURA',
            'SALIDA DE SUPERFICIE DE RODAMIENTO' => 'SALIDA DE RODAMIENTO',
            'SUBIDA AL CAMELLÓN' => 'SUBIDA A CAMELLÓN',
            'CAIDA DE MOTOCICLETA' => 'CAIDA DE MOTOCICLETA',
            'COLISIÓN CONTRA OBJETO FIJO' => 'CHOQUE OBJETO FIJO',
            'COLISIÓN POR ALCANCE' => 'COLISIÓN POR ALCANCE',
            'COLISIÓN POR NO RESPETAR SEMÁFORO' => 'COLISIÓN POR NO RESPETAR SEMÁFORO',
            'COLISIÓN POR INVASIÓN DE CARRIL' => 'COLISIÓN POR INVASIÓN DE CARRIL',
            'COLISIÓN POR CAMBIO DE CARRIL' => 'COLISIÓN POR CAMBIO DE CARRIL',
            'COLISIÓN POR CORTE DE CIRCULACIÓN' => 'COLISIÓN POR CORTE DE CIRCULACIÓN',
            'COLISIÓN POR MANIOBRA DE REVERSA' => 'COLISIÓN POR MANIOBRA REVERSA',
            'CAIDA A CUNETA' => 'CAIDA A CUNETA',
            'CAIDA ACUATICA DE VEHÍCULO' => 'CAIDA ACUÁTICA DE VEHÍCULO',
            'COLISIÓN CON PEATÓN' => 'COLISIÓN CON PEATÓN',
        ];

        return $mapa[$tipo] ?? $tipo;
    }

    protected function llenarChoquesDanios($sheet, string $fecha, array $idsDelegaciones): void
    {
        $choques = $this->obtenerResumenChoquesDanios($fecha, $idsDelegaciones);

        $sheet->setCellValue('B145', 'No.');
        $sheet->setCellValue('C145', 'HECHOS DE TRÁNSITO');
        $sheet->setCellValue('D145', 'CANTIDAD');

        $conceptos = [
            1 => 'CHOQUE ENTRE CAMIÓN Y MOTOCICLETA',
            2 => 'CHOQUE ENTRE CAMIÓN Y VEHÍCULO',
            3 => 'CHOQUE ENTRE MOTOCICLETAS',
            4 => 'CHOQUE ENTRE VEHÍCULOS',
            5 => 'CHOQUE ENTRE MOTOCICLETA Y VEHÍCULO',
            6 => 'CHOQUE ENTRE VEHÍCULO Y PEATÓN',
            7 => 'CHOQUE DE VEHÍCULO UNICO',
        ];

        $fila = 146;

        foreach ($conceptos as $numero => $concepto) {
            $sheet->setCellValue('B' . $fila, $numero);
            $sheet->setCellValue('C' . $fila, $concepto);
            $sheet->setCellValue('D' . $fila, $choques['tipos'][$concepto] ?? 0);

            $fila++;
        }

        $sheet->mergeCells('B153:C153');
        $sheet->setCellValue('B153', 'TOTAL');
        $sheet->setCellValue('D153', '=SUM(D146:D152)');

        $sheet->setCellValue('F145', 'No.');
        $sheet->setCellValue('G145', 'HECHOS DE TRÁNSITO');
        $sheet->setCellValue('H145', 'CANTIDAD');

        $sheet->setCellValue('F146', 1);
        $sheet->setCellValue('G146', 'MONTO DAÑOS MATERIALES ($)');
        $sheet->setCellValue('H146', $choques['monto_danios']);

        $sheet->setCellValue('F147', 2);
        $sheet->setCellValue('G147', 'MONTO VEHÍCULOS');
        $sheet->setCellValue('H147', 0);

        $sheet->setCellValue('F148', 3);
        $sheet->setCellValue('G148', 'MONTO OTROS');
        $sheet->setCellValue('H148', 0);

        $sheet->mergeCells('F149:G149');
        $sheet->setCellValue('F149', 'TOTAL');
        $sheet->setCellValue('H149', '=SUM(H146:H148)');

        $sheet->getStyle('B145:D145')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0070C0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->getStyle('F145:H145')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0070C0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->getStyle('B146:D153')->applyFromArray([
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

        $sheet->getStyle('F146:H149')->applyFromArray([
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

        $sheet->getStyle('B146:B152')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $sheet->getStyle('F146:F148')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $sheet->getStyle('D146:D153')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H146:H149')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H146:H149')->getNumberFormat()->setFormatCode('$#,##0.00');

        $sheet->getStyle('B153:D153')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '00B0F0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('F149:H149')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '00B0F0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    protected function obtenerResumenChoquesDanios(string $fecha, array $idsDelegaciones): array
    {
        [$inicio, $fin] = $this->rangoCorte($fecha);

        $datos = [
            'tipos' => [
                'CHOQUE ENTRE CAMIÓN Y MOTOCICLETA' => 0,
                'CHOQUE ENTRE CAMIÓN Y VEHÍCULO' => 0,
                'CHOQUE ENTRE MOTOCICLETAS' => 0,
                'CHOQUE ENTRE VEHÍCULOS' => 0,
                'CHOQUE ENTRE MOTOCICLETA Y VEHÍCULO' => 0,
                'CHOQUE ENTRE VEHÍCULO Y PEATÓN' => 0,
                'CHOQUE DE VEHÍCULO UNICO' => 0,
            ],
            'monto_danios' => 0,
        ];

        $hechos = DB::table('hechos as h')
            ->select([
                'h.id',
                'h.tipo_hecho',
                'h.monto_danos_patrimoniales',
            ])
            ->whereRaw("TIMESTAMP(h.fecha, h.hora) >= ? AND TIMESTAMP(h.fecha, h.hora) < ?", [$inicio, $fin])
            ->where('h.unidad_org_id', 2)
            ->when(!empty($idsDelegaciones), function ($query) use ($idsDelegaciones) {
                $query->whereIn('h.delegacion_id', $idsDelegaciones);
            })
            ->get();

        $hechoIds = $hechos->pluck('id')->toArray();

        $vehiculosPorHecho = [];

        if (!empty($hechoIds)) {
            $vehiculos = DB::table('hecho_vehiculo as hv')
                ->join('vehiculos as v', 'hv.vehiculo_id', '=', 'v.id')
                ->select([
                    'hv.hecho_id',
                    'v.tipo',
                    'v.monto_danos',
                ])
                ->whereIn('hv.hecho_id', $hechoIds)
                ->get();

            foreach ($vehiculos as $vehiculo) {
                if (!isset($vehiculosPorHecho[$vehiculo->hecho_id])) {
                    $vehiculosPorHecho[$vehiculo->hecho_id] = [];
                }

                $vehiculosPorHecho[$vehiculo->hecho_id][] = $vehiculo;
                $datos['monto_danios'] += is_numeric($vehiculo->monto_danos) ? (float) $vehiculo->monto_danos : 0;
            }
        }

        foreach ($hechos as $hecho) {
            $datos['monto_danios'] += is_numeric($hecho->monto_danos_patrimoniales) ? (float) $hecho->monto_danos_patrimoniales : 0;

            $tipoHecho = mb_strtoupper(trim($hecho->tipo_hecho ?? ''));

            if ($tipoHecho === 'COLISIÓN CON PEATÓN') {
                $datos['tipos']['CHOQUE ENTRE VEHÍCULO Y PEATÓN']++;
                continue;
            }

            $vehiculos = $vehiculosPorHecho[$hecho->id] ?? [];

            $camiones = 0;
            $motocicletas = 0;
            $vehiculosNormales = 0;

            foreach ($vehiculos as $vehiculo) {
                $tipo = $this->clasificarVehiculoChoque($vehiculo->tipo);

                if ($tipo === 'camion') {
                    $camiones++;
                } elseif ($tipo === 'motocicleta') {
                    $motocicletas++;
                } else {
                    $vehiculosNormales++;
                }
            }

            $totalVehiculos = $camiones + $motocicletas + $vehiculosNormales;

            if ($totalVehiculos <= 1) {
                $datos['tipos']['CHOQUE DE VEHÍCULO UNICO']++;
            } elseif ($camiones > 0 && $motocicletas > 0) {
                $datos['tipos']['CHOQUE ENTRE CAMIÓN Y MOTOCICLETA']++;
            } elseif ($camiones > 0 && $vehiculosNormales > 0) {
                $datos['tipos']['CHOQUE ENTRE CAMIÓN Y VEHÍCULO']++;
            } elseif ($motocicletas >= 2 && $vehiculosNormales === 0 && $camiones === 0) {
                $datos['tipos']['CHOQUE ENTRE MOTOCICLETAS']++;
            } elseif ($vehiculosNormales >= 2 && $motocicletas === 0 && $camiones === 0) {
                $datos['tipos']['CHOQUE ENTRE VEHÍCULOS']++;
            } elseif ($motocicletas > 0 && $vehiculosNormales > 0) {
                $datos['tipos']['CHOQUE ENTRE MOTOCICLETA Y VEHÍCULO']++;
            } else {
                $datos['tipos']['CHOQUE DE VEHÍCULO UNICO']++;
            }
        }

        return $datos;
    }

    protected function clasificarVehiculoChoque(?string $tipo): string
    {
        $tipo = mb_strtoupper(trim($tipo ?? ''));

        $camiones = [
            'CAJA SECA',
            'CAJA CERRADA',
            'CAJA ABIERTA',
            'PLATAFORMA',
            'VOLTEO',
            'REFRIGERADO',
            'CISTERNA',
            'PIPA',
            'GRÚA',
            'GRUA',
            'TORTON',
            'RABÓN',
            'RABON',
            'TRACTO',
            'TRACTOCAMION',
            'TRACTOCAMIÓN',
            'REDILAS',
        ];

        $motocicletas = [
            'TRABAJO',
            'CRUISER',
            'DOBLE PROPÓSITO',
            'DOBLE PROPOSITO',
            'SCOOTER',
            'ENDURO',
            'NAKED',
            'PISTA',
            'CHOPPER',
            'CUATRIMOTO',
            'MOTOCICLETA',
            'MOTO',
        ];

        foreach ($camiones as $item) {
            if (str_contains($tipo, $item)) {
                return 'camion';
            }
        }

        foreach ($motocicletas as $item) {
            if (str_contains($tipo, $item)) {
                return 'motocicleta';
            }
        }

        return 'vehiculo';
    }

    protected function llenarClasificacionVehiculos($sheet, string $fecha, array $idsDelegaciones): void
    {
        $datos = $this->obtenerClasificacionVehiculos($fecha, $idsDelegaciones);

        $sheet->setCellValue('B155', 'No.');
        $sheet->setCellValue('C155', 'HECHOS DE TRÁNSITO');
        $sheet->setCellValue('D155', 'CANTIDAD');

        $conceptos = [
            1 => 'SERVICIO PÚBLICO FED',
            2 => 'TRANSPORTE PÚBLICO',
            3 => 'AUTOMÓVIL',
            4 => 'CAMIONETA',
            5 => 'MICROBUS',
            6 => 'CAMIÓN URBANO DE PASAJEROS',
            7 => 'OMNIBUS',
            8 => 'CAMIONETA DE CARGA',
            9 => 'CAMION DE CARGA',
            10 => 'TRACTOR',
            11 => 'FERROCARRIL',
            12 => 'MOTOCICLETA',
            13 => 'BICICLETA',
            14 => 'OTRO',
            15 => 'SEMOVIENTE',
        ];

        $fila = 156;

        foreach ($conceptos as $num => $nombre) {
            $sheet->setCellValue('B' . $fila, $num);
            $sheet->setCellValue('C' . $fila, $nombre);
            $sheet->setCellValue('D' . $fila, $datos['clasificacion'][$nombre] ?? 0);
            $fila++;
        }

        $sheet->setCellValue('F155', 'No.');
        $sheet->setCellValue('G155', 'HECHOS DE TRÁNSITO');
        $sheet->setCellValue('H155', 'CANTIDAD');

        $sheet->setCellValue('F156', 1);
        $sheet->setCellValue('G156', 'VEHÍCULOS PARTICULARES INVOL.');
        $sheet->setCellValue('H156', $datos['resumen']['particulares']);

        $sheet->setCellValue('F157', 2);
        $sheet->setCellValue('G157', 'VEHÍCULOS SERV. PÚBLIC. INVOL.');
        $sheet->setCellValue('H157', $datos['resumen']['publicos']);

        $sheet->setCellValue('F158', 3);
        $sheet->setCellValue('G158', 'MOTOS INVOLUCRADAS');
        $sheet->setCellValue('H158', $datos['resumen']['motos']);

        $sheet->setCellValue('F159', 4);
        $sheet->setCellValue('G159', 'VEHÍCULOS OFICIALES INVOL');
        $sheet->setCellValue('H159', $datos['resumen']['oficiales']);

        $sheet->setCellValue('F161', 'No.');
        $sheet->setCellValue('G161', 'LIBERACIONES');
        $sheet->setCellValue('H161', 'CANTIDAD');

        $sheet->setCellValue('F162', 1);
        $sheet->setCellValue('G162', 'LIBERACIÓN MOTOCICLETAS');
        $sheet->setCellValue('H162', $datos['liberaciones']['motos']);

        $sheet->setCellValue('F163', 2);
        $sheet->setCellValue('G163', 'LIBERACIÓN VEHÍCULOS');
        $sheet->setCellValue('H163', $datos['liberaciones']['vehiculos']);

        $sheet->setCellValue('F164', 3);
        $sheet->setCellValue('G164', 'LIBERACIÓN CAMIONES');
        $sheet->setCellValue('H164', $datos['liberaciones']['camiones']);

        $sheet->setCellValue('F165', 4);
        $sheet->setCellValue('G165', 'LIBERACIÓN REMOLQUES');
        $sheet->setCellValue('H165', $datos['liberaciones']['remolques']);

        $sheet->mergeCells('F166:G166');
        $sheet->setCellValue('F166', 'TOTAL');
        $sheet->setCellValue('H166', '=SUM(H162:H165)');

        $sheet->setCellValue('F168', 'No.');
        $sheet->setCellValue('G168', 'ÁREAS AUXILIARES');
        $sheet->setCellValue('H168', 'CANTIDAD');

        $sheet->setCellValue('F169', 1);
        $sheet->setCellValue('G169', 'EXÁMEN TEÓRICO');
        $sheet->setCellValue('H169', 0);

        $sheet->getStyle('B155:D155')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0070C0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->getStyle('F155:H155')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0070C0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->getStyle('F161:H161')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '00B0F0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->getStyle('F168:H168')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '00B0F0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->getStyle('B156:D170')->applyFromArray([
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

        $sheet->getStyle('F156:H159')->applyFromArray([
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

        $sheet->getStyle('F162:H166')->applyFromArray([
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

        $sheet->getStyle('F169:H169')->applyFromArray([
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

        $sheet->getStyle('B156:B170')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $sheet->getStyle('F156:F159')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $sheet->getStyle('F162:F165')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $sheet->getStyle('F169')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $sheet->getStyle('D156:D170')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H156:H159')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H162:H166')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H169')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('F166:H166')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '00B0F0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    protected function obtenerClasificacionVehiculos(string $fecha, array $idsDelegaciones): array
    {
        [$inicio, $fin] = $this->rangoCorte($fecha);

        $datos = [
            'clasificacion' => [],
            'resumen' => [
                'particulares' => 0,
                'publicos' => 0,
                'motos' => 0,
                'oficiales' => 0,
            ],
            'liberaciones' => [
                'motos' => 0,
                'vehiculos' => 0,
                'camiones' => 0,
                'remolques' => 0,
            ],
        ];

        $vehiculos = DB::table('hechos as h')
            ->join('hecho_vehiculo as hv', 'h.id', '=', 'hv.hecho_id')
            ->join('vehiculos as v', 'hv.vehiculo_id', '=', 'v.id')
            ->select([
                'v.tipo',
                'v.tipo_servicio',
            ])
            ->whereRaw("TIMESTAMP(h.fecha, h.hora) >= ? AND TIMESTAMP(h.fecha, h.hora) < ?", [$inicio, $fin])
            ->where('h.unidad_org_id', 2)
            ->when(!empty($idsDelegaciones), function ($q) use ($idsDelegaciones) {
                $q->whereIn('h.delegacion_id', $idsDelegaciones);
            })
            ->get();

        foreach ($vehiculos as $v) {
            $tipo = mb_strtoupper($v->tipo ?? '');
            $servicio = mb_strtoupper($v->tipo_servicio ?? '');

            // CLASIFICACIÓN GRANDE
            $clave = $this->mapearTipoVehiculoExcel($tipo);
            $datos['clasificacion'][$clave] = ($datos['clasificacion'][$clave] ?? 0) + 1;

            // RESUMEN
            if (str_contains($servicio, 'PUBLIC')) {
                $datos['resumen']['publicos']++;
            } else {
                $datos['resumen']['particulares']++;
            }

            if (str_contains($tipo, 'MOTO')) {
                $datos['resumen']['motos']++;
            }

            if (str_contains($servicio, 'OFICIAL')) {
                $datos['resumen']['oficiales']++;
            }
        }

        // LIBERACIONES
        $liberaciones = DB::table('liberaciones as l')
            ->join('vehiculos as v', 'l.vehiculo_id', '=', 'v.id')
            ->select('v.tipo')
            ->whereDate('l.fecha_liberacion', $fecha)
            ->get();

        foreach ($liberaciones as $l) {
            $tipo = mb_strtoupper($l->tipo ?? '');

            if (str_contains($tipo, 'MOTO')) {
                $datos['liberaciones']['motos']++;
            } elseif (str_contains($tipo, 'CAJA') || str_contains($tipo, 'TRACTO')) {
                $datos['liberaciones']['camiones']++;
            } elseif (str_contains($tipo, 'REMOLQUE')) {
                $datos['liberaciones']['remolques']++;
            } else {
                $datos['liberaciones']['vehiculos']++;
            }
        }

        return $datos;
    }

    protected function mapearTipoVehiculoExcel(string $tipo): string
    {
        if (str_contains($tipo, 'MOTO')) return 'MOTOCICLETA';
        if (str_contains($tipo, 'BICICLETA')) return 'BICICLETA';
        if (str_contains($tipo, 'TRACTO')) return 'TRACTOR';
        if (str_contains($tipo, 'CAJA') || str_contains($tipo, 'PLATAFORMA')) return 'CAMION DE CARGA';
        if (str_contains($tipo, 'PICK') || str_contains($tipo, 'VAN')) return 'CAMIONETA';
        if (str_contains($tipo, 'SEDAN') || str_contains($tipo, 'SUV')) return 'AUTOMÓVIL';

        return 'OTRO';
    }

    protected function rangoCorte(string $fecha): array
    {
        $fin = \Carbon\Carbon::parse($fecha . ' 18:00:00', 'America/Mexico_City');
        $inicio = $fin->copy()->subDay();

        return [$inicio->format('Y-m-d H:i:s'), $fin->format('Y-m-d H:i:s')];
    }
}
