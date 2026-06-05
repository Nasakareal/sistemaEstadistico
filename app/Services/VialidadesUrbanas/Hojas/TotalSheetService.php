<?php

namespace App\Services\VialidadesUrbanas\Hojas;

use App\Models\Actividad;
use App\Models\Unidad;
use App\Models\VialidadDispositivo;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TotalSheetService
{
    private const UNIDAD_VIALIDADES_URBANAS_ID = 5;

    public function generar(Worksheet $sheet, string $fecha, Carbon $inicio, Carbon $fin): void
    {
        $rows = $this->construirFilas(
            $this->actividadesVialidadesUrbanas($inicio, $fin),
            $this->dispositivosVialidadesUrbanas($inicio, $fin)
        );

        $this->render($sheet, $fecha, $rows);
    }

    private function render(Worksheet $sheet, string $fecha, array $rows): void
    {
        $blue = '0070C0';
        $green = '00B050';
        $cyan = '00B0F0';
        $band = '9BC2E6';

        $thinBorder = [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000'],
        ];

        $sheet->setShowGridlines(false);
        $sheet->getSheetView()->setZoomScale(85);

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(66);
        $sheet->getColumnDimension('D')->setWidth(14);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(18);
        $sheet->getColumnDimension('G')->setWidth(28);
        $sheet->getColumnDimension('H')->setWidth(19);
        $sheet->getColumnDimension('I')->setWidth(19);

        $sheet->setCellValue('C1', 'VIALIDADES URBANAS');
        $sheet->setCellValue('B2', 'FECHA');
        $sheet->setCellValue('C2', Carbon::parse($fecha)->format('d/m/Y'));

        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->getRowDimension(2)->setRowHeight(24);
        $sheet->getStyle('B1:C2')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('B2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers = [
            'A' => 'No.',
            'B' => 'CATEGORÍA',
            'C' => 'ACTIVIDAD',
            'D' => 'CANTIDAD',
            'E' => "ESTADO DE\nFUERZA\nPARTICIPANTE",
            'F' => "UNIDADES\nPARTICIPANTES",
            'G' => 'KILOMETROS RECORRIDOS',
            'H' => "PERSONAS\nALCANZADAS",
            'I' => 'RECOMENDACIONES',
        ];

        foreach ($headers as $column => $header) {
            $sheet->setCellValue("{$column}3", $header);
        }

        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 10,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => $thinBorder,
            ],
        ];

        $sheet->getRowDimension(3)->setRowHeight(50);
        $sheet->getStyle('A3:C3')->applyFromArray($headerStyle);
        $sheet->getStyle('D3:G3')->applyFromArray($headerStyle);
        $sheet->getStyle('H3:I3')->applyFromArray($headerStyle);
        $sheet->getStyle('A3:C3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($blue);
        $sheet->getStyle('D3:G3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($green);
        $sheet->getStyle('H3:I3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($cyan);

        $bodyStyle = [
            'font' => [
                'color' => ['rgb' => '000000'],
                'size' => 10,
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => $thinBorder,
            ],
        ];

        $rowNumber = 4;

        foreach ($rows as $index => $row) {
            $sheet->setCellValue("A{$rowNumber}", $row['no']);
            $sheet->setCellValue("B{$rowNumber}", $row['categoria']);
            $sheet->setCellValue("C{$rowNumber}", $row['actividad']);
            $sheet->setCellValue("D{$rowNumber}", $this->valorVisible($row['cantidad']));
            $sheet->setCellValue("E{$rowNumber}", $this->valorVisible($row['estado_fuerza']));
            $sheet->setCellValue("F{$rowNumber}", $this->valorVisible($row['unidades']));
            $sheet->setCellValue("G{$rowNumber}", $this->valorVisible($row['kilometros']));
            $sheet->setCellValue("H{$rowNumber}", $this->valorVisible($row['personas']));
            $sheet->setCellValue("I{$rowNumber}", $this->valorVisible($row['recomendaciones']));

            $sheet->getStyle("A{$rowNumber}:I{$rowNumber}")->applyFromArray($bodyStyle);
            $sheet->getStyle("A{$rowNumber}:B{$rowNumber}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$rowNumber}:I{$rowNumber}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($row['band']) {
                $sheet->getStyle("A{$rowNumber}:I{$rowNumber}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB($band);
            }

            $sheet->getRowDimension($rowNumber)->setRowHeight(18);
            $rows[$index]['excel_row'] = $rowNumber;
            $rowNumber++;
        }

        $this->mergeBloques($sheet, $rows);

        $totalRow = $rowNumber;
        $sheet->mergeCells("A{$totalRow}:B{$totalRow}");
        $sheet->setCellValue("A{$totalRow}", 'TOTAL');
        $sheet->setCellValue("C{$totalRow}", 'DISPOSITIVOS REALIZADOS');
        $sheet->setCellValue("D{$totalRow}", $this->numeroVisible(array_sum(array_column($rows, 'cantidad'))));
        $sheet->setCellValue("E{$totalRow}", $this->numeroVisible(array_sum(array_column($rows, 'estado_fuerza'))));
        $sheet->setCellValue("F{$totalRow}", $this->numeroVisible(array_sum(array_column($rows, 'unidades'))));
        $sheet->setCellValue("G{$totalRow}", $this->numeroVisible(array_sum(array_column($rows, 'kilometros'))));
        $sheet->setCellValue("H{$totalRow}", $this->numeroVisible(array_sum(array_column($rows, 'personas'))));
        $sheet->setCellValue("I{$totalRow}", $this->numeroVisible(array_sum(array_column($rows, 'recomendaciones'))));

        $sheet->getStyle("A{$totalRow}:I{$totalRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '000000'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $cyan],
            ],
            'borders' => [
                'allBorders' => $thinBorder,
            ],
        ]);
        $sheet->getStyle("C{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $sheet->freezePane('A4');
    }

    private function construirFilas(Collection $actividades, ?Collection $dispositivos = null): array
    {
        $rows = array_map(function (array $row): array {
            return $row + [
                'cantidad' => 0,
                'estado_fuerza' => 0,
                'unidades' => 0,
                'kilometros' => 0,
                'personas' => 0,
                'recomendaciones' => 0,
            ];
        }, $this->templateCompleto());

        foreach ($actividades as $actividad) {
            $index = $this->buscarFilaActividad($rows, $actividad);

            if ($index === null) {
                continue;
            }

            $rows[$index]['cantidad'] += $this->cantidadActividad($actividad);
            $rows[$index]['estado_fuerza'] += $this->contarCantidadTexto($actividad->elementos_participantes_texto ?? '');
            $rows[$index]['unidades'] += $this->contarUnidadesTexto($actividad->patrullas_participantes_texto ?? '');
            $rows[$index]['kilometros'] += (float) ($actividad->km_recorridos ?? 0);
            $rows[$index]['personas'] += $this->personasAlcanzadas($actividad);
            $rows[$index]['recomendaciones'] += $this->contarRecomendaciones($actividad);
        }

        foreach (($dispositivos ?? collect()) as $dispositivo) {
            $index = $this->buscarFilaDispositivo($rows, $dispositivo);

            if ($index === null) {
                continue;
            }

            $rows[$index]['cantidad']++;
            $rows[$index]['estado_fuerza'] += (int) ($dispositivo->elementos ?? 0);
            $rows[$index]['unidades'] += (int) ($dispositivo->crp ?? 0)
                + (int) ($dispositivo->motopatrullas ?? 0)
                + (int) ($dispositivo->fenix ?? 0)
                + (int) ($dispositivo->unidades_motorizadas ?? 0)
                + (int) ($dispositivo->patrullas ?? 0)
                + (int) ($dispositivo->gruas ?? 0)
                + (int) ($dispositivo->otros_apoyos ?? 0);
            $rows[$index]['recomendaciones'] += $this->contarRecomendaciones($dispositivo);
        }

        foreach ($rows as &$row) {
            $row['kilometros'] = $this->numeroVisible($row['kilometros']);
        }
        unset($row);

        return $rows;
    }

    private function actividadesVialidadesUrbanas(Carbon $inicio, Carbon $fin): Collection
    {
        return Actividad::query()
            ->with(['categoria', 'subcategoria', 'fomentoCulturaVialDetalle'])
            ->whereIn('unidad_org_id', $this->unidadVialidadesUrbanasIds())
            ->whereRaw(
                "TIMESTAMP(DATE(fecha), COALESCE(hora, '00:00:00')) >= ? AND TIMESTAMP(DATE(fecha), COALESCE(hora, '00:00:00')) < ?",
                [$inicio->toDateTimeString(), $fin->toDateTimeString()]
            )
            ->get();
    }

    private function dispositivosVialidadesUrbanas(Carbon $inicio, Carbon $fin): Collection
    {
        return VialidadDispositivo::query()
            ->with('catalogo')
            ->whereIn('unidad_id', $this->unidadVialidadesUrbanasIds())
            ->whereRaw(
                "TIMESTAMP(DATE(fecha), COALESCE(hora, '00:00:00')) >= ? AND TIMESTAMP(DATE(fecha), COALESCE(hora, '00:00:00')) < ?",
                [$inicio->toDateTimeString(), $fin->toDateTimeString()]
            )
            ->get();
    }

    private function unidadVialidadesUrbanasIds(): array
    {
        $ids = Unidad::query()
            ->where('id', self::UNIDAD_VIALIDADES_URBANAS_ID)
            ->orWhere(function ($query) {
                $query->where('nombre', 'like', '%VIALIDADES%')
                    ->where('nombre', 'like', '%URBANAS%');
            })
            ->orWhere('slug', 'like', '%vialidades%')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return $ids ?: [self::UNIDAD_VIALIDADES_URBANAS_ID];
    }

    private function buscarFilaActividad(array $rows, $actividad): ?int
    {
        $categoria = $this->normalizar(optional($actividad->categoria)->nombre ?? '');
        $subcategoria = $this->normalizar(optional($actividad->subcategoria)->nombre ?? $actividad->nombre ?? '');

        foreach ($rows as $index => $row) {
            if ($this->normalizar($row['categoria_real']) === $categoria
                && $this->normalizar($row['actividad']) === $subcategoria) {
                return $index;
            }
        }

        return $this->buscarFilaOtros($rows, $categoria);
    }

    private function buscarFilaDispositivo(array $rows, $dispositivo): ?int
    {
        $texto = $this->normalizar(implode(' ', array_filter([
            optional($dispositivo->catalogo)->nombre,
            $dispositivo->asunto ?? null,
            $dispositivo->descripcion ?? null,
            $dispositivo->narrativa ?? null,
            $dispositivo->acciones_realizadas ?? null,
            $dispositivo->observaciones ?? null,
        ])));

        foreach ($rows as $index => $row) {
            if ($this->normalizar($row['categoria_real']) !== 'OPERATIVOS') {
                continue;
            }

            $actividad = $this->normalizar($row['actividad']);

            if ($actividad !== '' && str_contains($texto, $actividad)) {
                return $index;
            }
        }

        return $this->buscarFilaOtros($rows, 'OPERATIVOS');
    }

    private function buscarFilaOtros(array $rows, string $categoria): ?int
    {
        foreach ($rows as $index => $row) {
            if ($this->normalizar($row['categoria_real']) === $categoria
                && str_starts_with($this->normalizar($row['actividad']), 'OTRO')) {
                return $index;
            }
        }

        return null;
    }

    private function mergeBloques(Worksheet $sheet, array $rows): void
    {
        $start = null;
        $lastCategoria = null;

        foreach ($rows as $row) {
            $categoria = $row['categoria_real'];

            if ($categoria !== $lastCategoria) {
                if ($start !== null) {
                    $this->mergeBloque($sheet, $start, ((int) $row['excel_row']) - 1);
                }

                $start = (int) $row['excel_row'];
                $lastCategoria = $categoria;
            }
        }

        if ($start !== null && !empty($rows)) {
            $this->mergeBloque($sheet, $start, (int) end($rows)['excel_row']);
        }
    }

    private function mergeBloque(Worksheet $sheet, int $start, int $end): void
    {
        if ($end <= $start) {
            return;
        }

        $sheet->mergeCells("A{$start}:A{$end}");
        $sheet->mergeCells("B{$start}:B{$end}");
        $sheet->getStyle("A{$start}:B{$end}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A{$start}:B{$start}")->getFont()->setBold(true);
    }

    private function cantidadActividad($actividad): int
    {
        $cantidad = (int) ($actividad->cantidad ?? 0);

        return $cantidad > 0 ? $cantidad : 1;
    }

    private function personasAlcanzadas($actividad): int
    {
        $detalle = $actividad->fomentoCulturaVialDetalle ?? null;
        $detalleTotal = (int) ($detalle->total_poblacion_atendida ?? 0);

        return $detalleTotal > 0
            ? $detalleTotal
            : (int) ($actividad->personas_alcanzadas ?? 0);
    }

    private function valorVisible($value)
    {
        if ($value === null || $value === '' || (float) $value == 0.0) {
            return null;
        }

        return $value;
    }

    private function contarUnidadesTexto($texto): int
    {
        $texto = trim((string) $texto);

        if ($texto === '') {
            return 0;
        }

        if (preg_match('/^\d+$/', $texto)) {
            return (int) $texto;
        }

        $partes = array_filter(array_map('trim', preg_split('/[\n,;|]+/', $texto) ?: []));

        if (count($partes) > 1) {
            return count($partes);
        }

        return 1;
    }

    private function contarCantidadTexto($texto): int
    {
        $texto = trim((string) $texto);

        if ($texto === '') {
            return 0;
        }

        preg_match_all('/\d+/', $texto, $matches);

        if (!empty($matches[0])) {
            return array_sum(array_map('intval', $matches[0]));
        }

        return count(array_filter(preg_split('/[\s,;|]+/', $texto) ?: []));
    }

    private function contarRecomendaciones($registro): int
    {
        $texto = $this->normalizar(implode(' ', array_filter([
            $registro->motivo ?? null,
            $registro->narrativa ?? null,
            $registro->acciones_realizadas ?? null,
            $registro->observaciones ?? null,
        ])));

        if ($texto === '') {
            return 0;
        }

        preg_match_all('/RECOMENDACION(?:ES)?\D{0,20}(\d+)/u', $texto, $matches);

        if (!empty($matches[1])) {
            return array_sum(array_map('intval', $matches[1]));
        }

        return 0;
    }

    private function numeroVisible($numero)
    {
        $numero = (float) $numero;

        if (floor($numero) === $numero) {
            return (int) $numero;
        }

        return round($numero, 2);
    }

    private function normalizar($texto): string
    {
        $texto = mb_strtoupper((string) $texto, 'UTF-8');
        $texto = strtr($texto, [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ü' => 'U',
            'Ñ' => 'N',
        ]);

        return preg_replace('/\s+/', ' ', trim($texto));
    }

    private function templateCompleto(): array
    {
        return [
            ['no' => 1, 'categoria' => 'INSTITUCIONES', 'categoria_real' => 'INSTITUCIONES', 'actividad' => 'APOYO A EVENTOS PÚBLICOS', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'INSTITUCIONES', 'actividad' => 'APOYO A EVENTOS DEPORTIVOS', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'INSTITUCIONES', 'actividad' => 'APOYO A EVENTOS CULTURALES', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'INSTITUCIONES', 'actividad' => 'APOYO A EVENTOS RELIGIOSOS', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'INSTITUCIONES', 'actividad' => 'APOYOS A OTRAS DEPENDENCIAS (Publicas o privadas)', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'INSTITUCIONES', 'actividad' => 'ESCUELAS', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'INSTITUCIONES', 'actividad' => 'DILIGENCIAS', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'INSTITUCIONES', 'actividad' => 'OTROS TIPOS (Especificar en las novedades relevantes)', 'band' => false],

            ['no' => 2, 'categoria' => 'REPORTES C5i', 'categoria_real' => 'REPORTES C5i', 'actividad' => 'OBSTRUCCIÓN DE COCHERAS', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'REPORTES C5i', 'actividad' => 'OTROS TIPOS DE OBSTRUCCIÓN', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'REPORTES C5i', 'actividad' => 'ACTOS DELICTIVOS', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'REPORTES C5i', 'actividad' => 'SINIESTROS', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'REPORTES C5i', 'actividad' => 'HECHOS DE TRÁNSITO', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'REPORTES C5i', 'actividad' => 'CONSENTRACION PERSONAS', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'REPORTES C5i', 'actividad' => 'OTROS REPORTES (Especificar en las novedades relevantes)', 'band' => true],

            ['no' => 3, 'categoria' => 'ABANDERAMIENTOS', 'categoria_real' => 'ABANDERAMIENTOS', 'actividad' => 'CORTES DE CIRCULACIÓN', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'ABANDERAMIENTOS', 'actividad' => 'ACCIDENTES', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'ABANDERAMIENTOS', 'actividad' => 'MARCHAS', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'ABANDERAMIENTOS', 'actividad' => 'MÍTINES', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'ABANDERAMIENTOS', 'actividad' => 'OBRAS PÚBLICAS', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'ABANDERAMIENTOS', 'actividad' => 'ACOMPAÑAMIENTO A CARAVANAS U OTROS', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'ABANDERAMIENTOS', 'actividad' => 'OTROS ABANDERAMIENTOS (Especificar en las novedades relevantes)', 'band' => false],

            ['no' => 4, 'categoria' => 'OPERATIVOS', 'categoria_real' => 'OPERATIVOS', 'actividad' => 'ESCUELA SEGURA', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'OPERATIVOS', 'actividad' => 'CONEXIÓN INSTITUCIONAL', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'OPERATIVOS', 'actividad' => 'RESPUESTA VIAL INMEDIATA', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'OPERATIVOS', 'actividad' => 'ABANDERAMIENTO ACTIVO', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'OPERATIVOS', 'actividad' => 'PASO CONTINUO', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'OPERATIVOS', 'actividad' => '', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'OPERATIVOS', 'actividad' => '', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'OPERATIVOS', 'actividad' => '', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'OPERATIVOS', 'actividad' => '', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'OPERATIVOS', 'actividad' => '', 'band' => true],

            ['no' => 5, 'categoria' => 'PROGRAMAS', 'categoria_real' => 'PROGRAMAS', 'actividad' => 'CONDUCE SIN ALCOHOL (ALCOHOLÍMETRO)', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'PROGRAMAS', 'actividad' => 'OTROS PROGRAMAS (Especificar en las novedades relevantes)', 'band' => false],

            ['no' => 6, 'categoria' => 'MONITOREOS', 'categoria_real' => 'MONITOREOS', 'actividad' => 'VÍAS FÉRREAS', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'MONITOREOS', 'actividad' => 'PERIFÉRICOS', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'MONITOREOS', 'actividad' => 'AVENIDAS', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'MONITOREOS', 'actividad' => 'TIENDAS DEPARTAMENTALES', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'MONITOREOS', 'actividad' => 'BANCOS', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'MONITOREOS', 'actividad' => 'GASOLINERAS', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'MONITOREOS', 'actividad' => 'OFICINAS GUBERNAMENTALES', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'MONITOREOS', 'actividad' => 'MANIFESTACIONES', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'MONITOREOS', 'actividad' => 'OTROS MONITOREOS (Especificar en las novedades relevantes)', 'band' => true],

            ['no' => 7, 'categoria' => 'AUXILIO VIAL A CONDUCTORES', 'categoria_real' => 'AUXILIO VIAL A CONDUCTORES', 'actividad' => 'FALLAS MECÁNICAS', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'AUXILIO VIAL A CONDUCTORES', 'actividad' => 'PEATÓN', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'AUXILIO VIAL A CONDUCTORES', 'actividad' => 'ESCOLTA EN SITUACIONES DE EMERGENCIA', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'AUXILIO VIAL A CONDUCTORES', 'actividad' => 'AGRICOLAS', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'AUXILIO VIAL A CONDUCTORES', 'actividad' => 'OTROS AUXILIOS (Especificar en las novedades relevantes)', 'band' => false],

            ['no' => 8, 'categoria' => 'DISPOSITIVOS DE SEGURIDAD VIAL', 'categoria_real' => 'DISPOSITIVOS DE SEGURIDAD VIAL', 'actividad' => 'APOYO A LA VIALIDAD', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'DISPOSITIVOS DE SEGURIDAD VIAL', 'actividad' => 'PASO LIBRE DE FUNCIONARIOS', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'DISPOSITIVOS DE SEGURIDAD VIAL', 'actividad' => 'ZONAS DE MAYOR PASE DE TRANSEÚNTES', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'DISPOSITIVOS DE SEGURIDAD VIAL', 'actividad' => 'PASOS PEATONALES', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'DISPOSITIVOS DE SEGURIDAD VIAL', 'actividad' => 'MEDIDAS DE PROTECCIÓN', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'DISPOSITIVOS DE SEGURIDAD VIAL', 'actividad' => 'PATRULLAJES', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'DISPOSITIVOS DE SEGURIDAD VIAL', 'actividad' => 'SERVICIOS DE ESCOLTAS', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'DISPOSITIVOS DE SEGURIDAD VIAL', 'actividad' => 'OTROS (Especificar en las novedades relevantes)', 'band' => true],

            ['no' => 9, 'categoria' => 'CAPACITACIONES', 'categoria_real' => 'CAPACITACIONES', 'actividad' => 'TALLER EDUCACIÓN SEGURIDAD VIAL', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'CAPACITACIONES', 'actividad' => 'CAMPAÑA EDUCACIÓN SEGURIDAD VIAL', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'CAPACITACIONES', 'actividad' => 'CAPACITACIONES EDUCACIÓN SEGURIDAD VIAL', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'CAPACITACIONES', 'actividad' => 'MÓDULOS EDUCACIÓN SEGURIDAD VIAL', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'CAPACITACIONES', 'actividad' => 'SSP', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'CAPACITACIONES', 'actividad' => 'CALEA', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'CAPACITACIONES', 'actividad' => 'OTRAS (Especificar en las novedades relevantes)', 'band' => false],

            ['no' => 10, 'categoria' => 'CAMPAÑAS', 'categoria_real' => 'CAMPAÑAS', 'actividad' => 'CONCIENTIZACIÓN Y PREVENCIÓN', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'CAMPAÑAS', 'actividad' => 'REPARTICIÓN DE TRÍPTICOS', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'CAMPAÑAS', 'actividad' => 'ESTACIONALES (SEMANA SANTA, NAVIDAD ETC.)', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'CAMPAÑAS', 'actividad' => 'OTRAS (Especificar en las novedades relevantes)', 'band' => true],

            ['no' => 11, 'categoria' => 'PROXIMIDAD SOCIAL', 'categoria_real' => 'PROXIMIDAD SOCIAL', 'actividad' => 'PREVENCIÓN SOCIAL', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'PROXIMIDAD SOCIAL', 'actividad' => 'RECORRIDOS DE PROXIMIDAD', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'PROXIMIDAD SOCIAL', 'actividad' => 'APOYO A TURISTAS', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'PROXIMIDAD SOCIAL', 'actividad' => 'APOYO A PERSONAS DE LA TERCERA EDAD', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'PROXIMIDAD SOCIAL', 'actividad' => 'APOYO A PERSONAS PERDIDAS', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'PROXIMIDAD SOCIAL', 'actividad' => 'RECUPERACIÓN DE ESPACIOS', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'PROXIMIDAD SOCIAL', 'actividad' => 'OTRAS (Especificar en las novedades relevantes)', 'band' => false],
        ];
    }
}
