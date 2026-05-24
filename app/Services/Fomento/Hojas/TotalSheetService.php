<?php

namespace App\Services\Fomento\Hojas;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TotalSheetService extends BaseFomentoSheetService
{
    public function generar(Worksheet $sheet, Collection $personal, Collection $actividades, string $fecha, Carbon $inicio, Carbon $fin): void
    {
        $sheet->getSheetView()->setZoomScale(85);
        $this->aplicarFormatoBase($sheet, $fecha);

        $catalogo = $this->catalogoActividades();
        $resumen = $this->resumenPorSubcategoria($actividades);

        $fila = 4;
        $numero = 1;

        foreach ($catalogo as $categoria) {
            $subcategorias = $categoria['subcategorias'];

            if (empty($subcategorias)) {
                $subcategorias = [[
                    'id' => null,
                    'nombre' => '',
                ]];
            }

            $filaInicio = $fila;
            $filaFin = $fila + count($subcategorias) - 1;
            $rellenoCategoria = $numero % 2 === 0 ? '9DC3E6' : 'FFFFFF';

            if ($filaFin > $filaInicio) {
                $sheet->mergeCells('A' . $filaInicio . ':A' . $filaFin);
                $sheet->mergeCells('B' . $filaInicio . ':B' . $filaFin);
            }

            $sheet->setCellValue('A' . $filaInicio, $numero);
            $sheet->setCellValue('B' . $filaInicio, $categoria['nombre']);

            foreach ($subcategorias as $subcategoria) {
                $item = $resumen[$subcategoria['id']] ?? $this->resumenVacio();

                $sheet->setCellValue('C' . $fila, $subcategoria['nombre']);
                $sheet->setCellValue('D' . $fila, $this->numeroVisible($item['cantidad']));
                $sheet->setCellValue('E' . $fila, $this->numeroVisible($item['estado_fuerza']));
                $sheet->setCellValue('F' . $fila, $this->numeroVisible($item['unidades']));
                $sheet->setCellValue('G' . $fila, $this->numeroVisible($item['kilometros']));
                $sheet->setCellValue('H' . $fila, $this->numeroVisible($item['personas']));
                $sheet->setCellValue('I' . $fila, $this->numeroVisible($item['recomendaciones']));

                $sheet->getRowDimension($fila)->setRowHeight(20);
                $fila++;
            }

            $sheet->getStyle('A' . $filaInicio . ':I' . $filaFin)->applyFromArray($this->estiloFilaCatalogo($rellenoCategoria));
            $sheet->getStyle('A' . $filaInicio . ':B' . $filaFin)->getFont()->setBold(true);
            $sheet->getStyle('A' . $filaInicio . ':B' . $filaFin)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('C' . $filaInicio . ':C' . $filaFin)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('D' . $filaInicio . ':I' . $filaFin)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $numero++;
        }

        $this->llenarTotales($sheet, $fila);
        $this->llenarControlVehicular($sheet, 80);
        $this->llenarControlAseguramientos($sheet, 96);
        $this->llenarOtrosAseguramientos($sheet, 112);
        $this->llenarHechosTransitoResumen($sheet, 119);
        $this->llenarHechosTransitoTipos($sheet, 125);
        $this->llenarChoquesYMontos($sheet, 145);
        $this->llenarTablasFinales($sheet, 155);
        $sheet->freezePane('A4');
    }

    private function aplicarFormatoBase(Worksheet $sheet, string $fecha): void
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

        $sheet->mergeCells('C1:I1');
        $sheet->setCellValue('C1', 'UNIDAD DE FOMENTO A LA CULTURA VIAL');
        $sheet->setCellValue('B2', 'FECHA');
        $sheet->setCellValue('C2', Carbon::parse($fecha)->format('d/m/Y'));

        $sheet->setCellValue('A3', 'No.');
        $sheet->setCellValue('B3', 'CATEGORÍA');
        $sheet->setCellValue('C3', 'ACTIVIDAD');
        $sheet->setCellValue('D3', 'CANTIDAD');
        $sheet->setCellValue('E3', "ESTADO DE\nFUERZA\nPARTICIPANTE");
        $sheet->setCellValue('F3', "UNIDADES\nPARTICIPANTES");
        $sheet->setCellValue('G3', 'KILOMETROS RECORRIDOS');
        $sheet->setCellValue('H3', "PERSONAS\nALCANZADAS");
        $sheet->setCellValue('I3', 'RECOMENDACIONES');

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

        $sheet->getStyle('A3:C3')->applyFromArray($this->estiloEncabezado('0070C0'));
        $sheet->getStyle('D3:G3')->applyFromArray($this->estiloEncabezado('00B050'));
        $sheet->getStyle('H3')->applyFromArray($this->estiloEncabezado('00A2E8'));
        $sheet->getStyle('I3')->applyFromArray($this->estiloEncabezado('00B0F0'));
    }

    private function catalogoActividades(): array
    {
        $categorias = [
            'INSTITUCIONES' => [
                'APOYO A EVENTOS PÚBLICOS',
                'APOYO A EVENTOS DEPORTIVOS',
                'APOYO A EVENTOS CULTURALES',
                'APOYO A EVENTOS RELIGIOSOS',
                'APOYOS A OTRAS DEPENDENCIAS (Publicas o privadas)',
                'ESCUELAS',
                'DILIGENCIAS',
                'OTROS TIPOS (Especificar en las novedades relevantes)',
            ],
            'REPORTES C5i' => [
                'OBSTRUCCIÓN DE COCHERAS',
                'OTROS TIPOS DE OBSTRUCCIÓN',
                'ACTOS DELICTIVOS',
                'SINIESTROS',
                'HECHOS DE TRÁNSITO',
                'CONSENTRACION PERSONAS',
                'OTROS REPORTES (Especificar en las novedades relevantes)',
            ],
            'ABANDERAMIENTOS' => [
                'CORTES DE CIRCULACIÓN',
                'ACCIDENTES',
                'MARCHAS',
                'MÍTINES',
                'OBRAS PÚBLICAS',
                'ACOMPAÑAMIENTO A CARAVANAS U OTROS',
                'OTROS ABANDERAMIENTOS (Especificar en las novedades relevantes)',
            ],
            'OPERATIVOS' => [
                'RELÁMPAGO',
                'CARRUSEL',
                'BLINDAJE',
                'CONCIENTIZACIÓN USO DE CASCO',
                'PUESTO DE REVISIÓN',
                'PUESTO DE CONTROL',
                'APOYO COCOTRA',
                'BLINDAJE CON ESTADOS COLINDANTES',
                'BASES DE OPERACIONES INTERINSTITUCIONAL',
                'OTROS OPERATIVOS (Especificar en las novedades relevantes)',
            ],
            'PROGRAMAS' => [
                'CONDUCE SIN ALCOHOL (ALCOHOLÍMETRO)',
                'OTROS PROGRAMAS (Especificar en las novedades relevantes)',
            ],
            'MONITOREOS' => [
                'VÍAS FÉRREAS',
                'PERIFÉRICOS',
                'AVENIDAS',
                'TIENDAS DEPARTAMENTALES',
                'BANCOS',
                'GASOLINERAS',
                'OFICINAS GUBERNAMENTALES',
                'MANIFESTACIONES',
                'OTROS MONITOREOS (Especificar en las novedades relevantes)',
            ],
            'AUXILIO VIAL A CONDUCTORES' => [
                'FALLAS MECÁNICAS',
                'PEATÓN',
                'ESCOLTA EN SITUACIONES DE EMERGENCIA',
                'AGRICOLAS',
                'OTROS AUXILIOS (Especificar en las novedades relevantes)',
            ],
            'DISPOSITIVOS DE SEGURIDAD VIAL' => [
                'APOYO A LA VIALIDAD',
                'PASO LIBRE DE FUNCIONARIOS',
                'ZONAS DE MAYOR PASE DE TRANSEÚNTES',
                'PASOS PEATONALES',
                'MEDIDAS DE PROTECCIÓN',
                'PATRULLAJES',
                'SERVICIOS DE ESCOLTAS',
                'OTROS (Especificar en las novedades relevantes)',
            ],
            'CAPACITACIONES' => [
                'TALLER EDUCACIÓN SEGURIDAD VIAL',
                'CAMPAÑA EDUCACIÓN SEGURIDAD VIAL',
                'CAPACITACIONES EDUCACIÓN SEGURIDAD VIAL',
                'MÓDULOS EDUCACIÓN SEGURIDAD VIAL',
                'SSP',
                'CALEA',
                'OTRAS (Especificar en las novedades relevantes)',
            ],
            'CAMPAÑAS' => [
                'CONCIENTIZACIÓN Y PREVENCIÓN',
                'REPARTICIÓN DE TRÍPTICOS',
                'ESTACIONALES (SEMANA SANTA, NAVIDAD ETC.)',
                'OTRAS (Especificar en las novedades relevantes)',
            ],
            'PROXIMIDAD SOCIAL' => [
                'PREVENCIÓN SOCIAL',
                'RECORRIDOS DE PROXIMIDAD',
                'APOYO A TURISTAS',
                'APOYO A PERSONAS DE LA TERCERA EDAD',
                'APOYO A PERSONAS PERDIDAS',
                'RECUPERACIÓN DE ESPACIOS',
                'OTRAS (Especificar en las novedades relevantes)',
            ],
        ];

        $catalogo = [];

        foreach ($categorias as $categoriaNombre => $subcategorias) {
            $items = [];

            foreach ($subcategorias as $subcategoriaNombre) {
                $items[] = [
                    'id' => $this->subcategoriaId($categoriaNombre, $subcategoriaNombre),
                    'nombre' => $subcategoriaNombre,
                ];
            }

            $catalogo[] = [
                'nombre' => $categoriaNombre,
                'subcategorias' => $items,
            ];
        }

        return $catalogo;
    }

    private function subcategoriaId(string $categoriaNombre, string $subcategoriaNombre): ?int
    {
        $id = DB::table('actividad_subcategorias as s')
            ->join('actividad_categorias as c', 'c.id', '=', 's.actividad_categoria_id')
            ->where('c.nombre', $categoriaNombre)
            ->where('s.nombre', $subcategoriaNombre)
            ->value('s.id');

        return $id ? (int) $id : null;
    }

    private function resumenPorSubcategoria(Collection $actividades): array
    {
        $resumen = [];

        foreach ($actividades as $actividad) {
            $subcategoriaId = (int) ($actividad->actividad_subcategoria_id ?? 0);

            if ($subcategoriaId <= 0) {
                continue;
            }

            if (!isset($resumen[$subcategoriaId])) {
                $resumen[$subcategoriaId] = $this->resumenVacio();
            }

            $resumen[$subcategoriaId]['cantidad']++;
            $resumen[$subcategoriaId]['estado_fuerza'] += (int) ($actividad->personas_participantes ?? 0);
            $resumen[$subcategoriaId]['personas'] += $this->poblacionAtendida($actividad);
        }

        return $resumen;
    }

    private function resumenVacio(): array
    {
        return [
            'cantidad' => 0,
            'estado_fuerza' => 0,
            'unidades' => 0,
            'kilometros' => 0,
            'personas' => 0,
            'recomendaciones' => 0,
        ];
    }

    private function llenarTotales(Worksheet $sheet, int $fila): void
    {
        $ultimaFilaDatos = max(4, $fila - 1);

        $sheet->mergeCells('A' . $fila . ':B' . $fila);
        $sheet->setCellValue('A' . $fila, 'TOTAL');
        $sheet->setCellValue('C' . $fila, 'DISPOSITIVOS REALIZADOS');
        $sheet->setCellValue('D' . $fila, '=SUM(D4:D' . $ultimaFilaDatos . ')');
        $sheet->setCellValue('E' . $fila, '=SUM(E4:E' . $ultimaFilaDatos . ')');
        $sheet->setCellValue('F' . $fila, '=SUM(F4:F' . $ultimaFilaDatos . ')');
        $sheet->setCellValue('G' . $fila, '=SUM(G4:G' . $ultimaFilaDatos . ')');
        $sheet->setCellValue('H' . $fila, '=SUM(H4:H' . $ultimaFilaDatos . ')');
        $sheet->setCellValue('I' . $fila, '=SUM(I4:I' . $ultimaFilaDatos . ')');

        $sheet->getRowDimension($fila)->setRowHeight(22);
        $sheet->getStyle('A' . $fila . ':I' . $fila)->applyFromArray([
            'font' => ['bold' => true],
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
                'wrapText' => true,
            ],
        ]);
        $sheet->getStyle('C' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('D' . $fila . ':I' . $fila)->getNumberFormat()->setFormatCode('#,##0');
    }

    private function llenarControlVehicular(Worksheet $sheet, int $filaInicio): void
    {
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

        $sheet->setCellValue('B' . $filaInicio, 'No.');
        $sheet->setCellValue('C' . $filaInicio, 'CONTROL VEHÍCULAR');
        $sheet->setCellValue('D' . $filaInicio, 'VEHÍCULOS');
        $sheet->setCellValue('E' . $filaInicio, 'MOTOCICLETAS');
        $sheet->setCellValue('F' . $filaInicio, 'CAMIONES');
        $sheet->setCellValue('G' . $filaInicio, 'OTROS');

        $sheet->getRowDimension($filaInicio)->setRowHeight(22);
        $sheet->getStyle('B' . $filaInicio . ':G' . $filaInicio)->applyFromArray($this->estiloEncabezado('0070C0'));

        $fila = $filaInicio + 1;

        foreach ($conceptos as $numero => $concepto) {
            $sheet->setCellValue('B' . $fila, $numero);
            $sheet->setCellValue('C' . $fila, $concepto);
            $sheet->getRowDimension($fila)->setRowHeight(20);
            $fila++;
        }

        $totalRow = $fila;
        $sheet->mergeCells('B' . $totalRow . ':C' . $totalRow);
        $sheet->setCellValue('B' . $totalRow, 'TOTAL');
        $sheet->setCellValue('D' . $totalRow, '=SUM(D' . ($filaInicio + 1) . ':D' . ($totalRow - 1) . ')');
        $sheet->setCellValue('E' . $totalRow, '=SUM(E' . ($filaInicio + 1) . ':E' . ($totalRow - 1) . ')');
        $sheet->setCellValue('F' . $totalRow, '=SUM(F' . ($filaInicio + 1) . ':F' . ($totalRow - 1) . ')');
        $sheet->setCellValue('G' . $totalRow, '=SUM(G' . ($filaInicio + 1) . ':G' . ($totalRow - 1) . ')');

        $sheet->getStyle('B' . ($filaInicio + 1) . ':G' . ($totalRow - 1))->applyFromArray($this->estiloTablaControl());
        $sheet->getStyle('B' . ($filaInicio + 1) . ':B' . ($totalRow - 1))->getFont()->setBold(true);
        $sheet->getStyle('B' . ($filaInicio + 1) . ':B' . ($totalRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D' . ($filaInicio + 1) . ':G' . ($totalRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getRowDimension($totalRow)->setRowHeight(22);
        $sheet->getStyle('B' . $totalRow . ':G' . $totalRow)->applyFromArray([
            'font' => ['bold' => true],
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
        $sheet->getStyle('D' . $totalRow . ':G' . $totalRow)->getNumberFormat()->setFormatCode('#,##0');
    }

    private function llenarControlAseguramientos(Worksheet $sheet, int $filaInicio): void
    {
        $personasAseguradas = [
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
            'ARMAS',
            'CORTAS',
            'LARGAS',
            'CARGADORES',
            'CARTUCHOS',
            'GRANADAS',
            'LANZAGRANADAS',
            'PUNZOCORTANTE',
        ];

        $drogas = [
            'DROGA',
            'MARIHUANA GRS',
            'CRISTAL GRS',
            'COCAINA GRS',
            'PASTILLAS',
            'PLANTIOS',
            'PLANTAS DE MARIHUANA',
            'OTRAS DROGAS',
        ];

        $sheet->mergeCells('B' . $filaInicio . ':H' . $filaInicio);
        $sheet->setCellValue('B' . $filaInicio, 'CONTROL DE ASEGURAMIENTOS');
        $sheet->getRowDimension($filaInicio)->setRowHeight(26);
        $sheet->getStyle('B' . $filaInicio . ':H' . $filaInicio)->applyFromArray($this->estiloEncabezado('0070C0'));
        $sheet->getStyle('B' . $filaInicio)->getFont()->setSize(14);

        $headerRow = $filaInicio + 1;
        $sheet->setCellValue('B' . $headerRow, 'No.');
        $sheet->setCellValue('C' . $headerRow, 'PERSONAS ASEGURADAS');
        $sheet->setCellValue('D' . $headerRow, 'TOTAL');
        $sheet->setCellValue('E' . $headerRow, 'ARMAS');
        $sheet->setCellValue('F' . $headerRow, 'TOTAL');
        $sheet->setCellValue('G' . $headerRow, 'DROGA');
        $sheet->setCellValue('H' . $headerRow, 'TOTAL');
        $sheet->getRowDimension($headerRow)->setRowHeight(22);
        $sheet->getStyle('B' . $headerRow . ':H' . $headerRow)->applyFromArray($this->estiloEncabezado('0070C0'));

        $fila = $headerRow + 1;

        foreach ($personasAseguradas as $numero => $concepto) {
            $sheet->setCellValue('B' . $fila, $numero);
            $sheet->setCellValue('C' . $fila, $concepto);
            $sheet->getRowDimension($fila)->setRowHeight(in_array($numero, [7, 8], true) ? 34 : 20);
            $fila++;
        }

        $totalPersonasRow = $fila;
        $sheet->mergeCells('B' . $totalPersonasRow . ':C' . $totalPersonasRow);
        $sheet->setCellValue('B' . $totalPersonasRow, 'TOTAL');
        $sheet->setCellValue('D' . $totalPersonasRow, '=SUM(D' . ($headerRow + 1) . ':D' . ($totalPersonasRow - 1) . ')');

        foreach ($armas as $index => $concepto) {
            $row = $headerRow + 1 + $index;
            $sheet->setCellValue('E' . $row, $concepto);
        }

        foreach ($drogas as $index => $concepto) {
            $row = $headerRow + 1 + $index;
            $sheet->setCellValue('G' . $row, $concepto);
        }

        $totalArmasRow = $headerRow + 1 + count($armas);
        $sheet->setCellValue('E' . $totalArmasRow, 'TOTAL');
        $sheet->setCellValue('F' . $totalArmasRow, '=SUM(F' . ($headerRow + 1) . ':F' . ($totalArmasRow - 1) . ')');
        $sheet->setCellValue('G' . $totalArmasRow, 'TOTAL');
        $sheet->setCellValue('H' . $totalArmasRow, '=SUM(H' . ($headerRow + 1) . ':H' . ($totalArmasRow - 1) . ')');

        $sheet->getStyle('B' . ($headerRow + 1) . ':D' . ($totalPersonasRow - 1))->applyFromArray($this->estiloTablaControl());
        $sheet->getStyle('E' . ($headerRow + 1) . ':H' . ($totalArmasRow - 1))->applyFromArray($this->estiloTablaControl());
        $sheet->getStyle('B' . ($headerRow + 1) . ':B' . ($totalPersonasRow - 1))->getFont()->setBold(true);
        $sheet->getStyle('B' . ($headerRow + 1) . ':B' . ($totalPersonasRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D' . ($headerRow + 1) . ':D' . ($totalPersonasRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F' . ($headerRow + 1) . ':F' . ($totalArmasRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H' . ($headerRow + 1) . ':H' . ($totalArmasRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('B' . $totalPersonasRow . ':D' . $totalPersonasRow)->applyFromArray($this->estiloTotalControl());
        $sheet->getStyle('E' . $totalArmasRow . ':H' . $totalArmasRow)->applyFromArray($this->estiloTotalControl());
        $sheet->getStyle('D' . $totalPersonasRow . ':D' . $totalPersonasRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('F' . $totalArmasRow . ':F' . $totalArmasRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('H' . $totalArmasRow . ':H' . $totalArmasRow)->getNumberFormat()->setFormatCode('#,##0');
    }

    private function llenarOtrosAseguramientos(Worksheet $sheet, int $filaInicio): void
    {
        $conceptos = [
            1 => 'AGUACATE',
            2 => 'MADERA',
            3 => 'DINERO',
            4 => 'OTROS ASEGURAMIENTOS (AGREGARLOS)',
        ];

        $sheet->setCellValue('B' . $filaInicio, 'No.');
        $sheet->setCellValue('C' . $filaInicio, 'OTROS ASEGURAMIENTOS');
        $sheet->setCellValue('D' . $filaInicio, 'TOTAL');
        $sheet->getRowDimension($filaInicio)->setRowHeight(22);
        $sheet->getStyle('B' . $filaInicio . ':D' . $filaInicio)->applyFromArray($this->estiloEncabezado('0070C0'));

        $fila = $filaInicio + 1;

        foreach ($conceptos as $numero => $concepto) {
            $sheet->setCellValue('B' . $fila, $numero);
            $sheet->setCellValue('C' . $fila, $concepto);
            $sheet->getRowDimension($fila)->setRowHeight(20);
            $fila++;
        }

        $totalRow = $fila;
        $sheet->mergeCells('B' . $totalRow . ':C' . $totalRow);
        $sheet->setCellValue('B' . $totalRow, 'TOTAL');
        $sheet->setCellValue('D' . $totalRow, '=SUM(D' . ($filaInicio + 1) . ':D' . ($totalRow - 1) . ')');

        $sheet->getStyle('B' . ($filaInicio + 1) . ':D' . ($totalRow - 1))->applyFromArray($this->estiloTablaControl());
        $sheet->getStyle('B' . ($filaInicio + 1) . ':B' . ($totalRow - 1))->getFont()->setBold(true);
        $sheet->getStyle('B' . ($filaInicio + 1) . ':B' . ($totalRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D' . ($filaInicio + 1) . ':D' . ($totalRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B' . $totalRow . ':D' . $totalRow)->applyFromArray($this->estiloTotalControl());
        $sheet->getStyle('D' . $totalRow)->getNumberFormat()->setFormatCode('#,##0');
    }

    private function llenarHechosTransitoResumen(Worksheet $sheet, int $filaInicio): void
    {
        $this->llenarTablaTresFilas(
            $sheet,
            $filaInicio,
            'B',
            'C',
            'D',
            'HECHOS DE TRÁNSITO',
            'CANTIDAD',
            [
                1 => 'RESUELTOS',
                2 => 'PENDIENTES',
                3 => 'TURNADOS',
            ]
        );

        $this->llenarTablaTresFilas(
            $sheet,
            $filaInicio,
            'F',
            'G',
            'H',
            'HECHOS DE TRÁNSITO',
            'CANTIDAD',
            [
                1 => 'HOMBRES INVOLUCRADOS',
                2 => 'MUJERES INVOLUCRADAS',
                3 => 'MENORES INVOLUCRADOS',
            ]
        );
    }

    private function llenarTablaTresFilas(
        Worksheet $sheet,
        int $filaInicio,
        string $colNo,
        string $colConcepto,
        string $colTotal,
        string $titulo,
        string $totalHeader,
        array $conceptos
    ): void {
        $sheet->setCellValue($colNo . $filaInicio, 'No.');
        $sheet->setCellValue($colConcepto . $filaInicio, $titulo);
        $sheet->setCellValue($colTotal . $filaInicio, $totalHeader);
        $sheet->getRowDimension($filaInicio)->setRowHeight(22);
        $sheet->getStyle($colNo . $filaInicio . ':' . $colTotal . $filaInicio)->applyFromArray($this->estiloEncabezado('0070C0'));

        $fila = $filaInicio + 1;

        foreach ($conceptos as $numero => $concepto) {
            $sheet->setCellValue($colNo . $fila, $numero);
            $sheet->setCellValue($colConcepto . $fila, $concepto);
            $sheet->getRowDimension($fila)->setRowHeight(20);
            $fila++;
        }

        $totalRow = $fila;
        $sheet->mergeCells($colNo . $totalRow . ':' . $colConcepto . $totalRow);
        $sheet->setCellValue($colNo . $totalRow, 'TOTAL');
        $sheet->setCellValue($colTotal . $totalRow, '=SUM(' . $colTotal . ($filaInicio + 1) . ':' . $colTotal . ($totalRow - 1) . ')');

        $sheet->getStyle($colNo . ($filaInicio + 1) . ':' . $colTotal . ($totalRow - 1))->applyFromArray($this->estiloTablaControl());
        $sheet->getStyle($colNo . ($filaInicio + 1) . ':' . $colNo . ($totalRow - 1))->getFont()->setBold(true);
        $sheet->getStyle($colNo . ($filaInicio + 1) . ':' . $colNo . ($totalRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($colTotal . ($filaInicio + 1) . ':' . $colTotal . ($totalRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($colNo . $totalRow . ':' . $colTotal . $totalRow)->applyFromArray($this->estiloTotalControl());
        $sheet->getStyle($colTotal . $totalRow)->getNumberFormat()->setFormatCode('#,##0');
    }

    private function llenarHechosTransitoTipos(Worksheet $sheet, int $filaInicio): void
    {
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

        $headers = [
            'B' => 'No.',
            'C' => 'HECHOS DE TRÁNSITO',
            'D' => 'CANTIDAD',
            'E' => 'LESIONADOS',
            'F' => 'HERIDOS',
            'G' => 'DEFUNCIONES',
            'H' => 'FUERO COMÚN',
        ];

        foreach ($headers as $column => $label) {
            $sheet->setCellValue($column . $filaInicio, $label);
        }

        $sheet->getRowDimension($filaInicio)->setRowHeight(22);
        $sheet->getStyle('B' . $filaInicio . ':H' . $filaInicio)->applyFromArray($this->estiloEncabezado('0070C0'));

        $fila = $filaInicio + 1;

        foreach ($conceptos as $numero => $concepto) {
            $sheet->setCellValue('B' . $fila, $numero);
            $sheet->setCellValue('C' . $fila, $concepto);
            $sheet->getRowDimension($fila)->setRowHeight(20);
            $fila++;
        }

        $totalRow = $fila;
        $sheet->mergeCells('B' . $totalRow . ':C' . $totalRow);
        $sheet->setCellValue('B' . $totalRow, 'TOTAL');

        foreach (range('D', 'H') as $column) {
            $sheet->setCellValue($column . $totalRow, '=SUM(' . $column . ($filaInicio + 1) . ':' . $column . ($totalRow - 1) . ')');
        }

        $sheet->getStyle('B' . ($filaInicio + 1) . ':H' . ($totalRow - 1))->applyFromArray($this->estiloTablaControl());
        $sheet->getStyle('B' . ($filaInicio + 1) . ':B' . ($totalRow - 1))->getFont()->setBold(true);
        $sheet->getStyle('B' . ($filaInicio + 1) . ':B' . ($totalRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D' . ($filaInicio + 1) . ':H' . ($totalRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B' . $totalRow . ':H' . $totalRow)->applyFromArray($this->estiloTotalControl());
        $sheet->getStyle('D' . $totalRow . ':H' . $totalRow)->getNumberFormat()->setFormatCode('#,##0');
    }

    private function llenarChoquesYMontos(Worksheet $sheet, int $filaInicio): void
    {
        $choques = [
            1 => 'CHOQUE ENTRE CAMIÓN Y MOTOCICLETA',
            2 => 'CHOQUE ENTRE CAMIÓN Y VEHÍCULO',
            3 => 'CHOQUE ENTRE MOTOCICLETAS',
            4 => 'CHOQUE ENTRE VEHÍCULOS',
            5 => 'CHOQUE ENTRE MOTOCICLETA Y VEHÍCULO',
            6 => 'CHOQUE ENTRE VEHÍCULO Y PEATÓN',
            7 => 'CHOQUE DE VEHÍCULO UNICO',
        ];

        $this->llenarTablaConceptos(
            $sheet,
            $filaInicio,
            'B',
            'C',
            'D',
            'HECHOS DE TRÁNSITO',
            'CANTIDAD',
            $choques
        );

        $montos = [
            1 => 'MONTO DAÑOS MATERIALES ($)',
            2 => 'MONTO VEHÍCULOS',
            3 => 'MONTO OTROS',
        ];

        $this->llenarTablaConceptos(
            $sheet,
            $filaInicio,
            'F',
            'G',
            'H',
            'HECHOS DE TRÁNSITO',
            'CANTIDAD',
            $montos,
            '$#,##0.00'
        );
    }

    private function llenarTablaConceptos(
        Worksheet $sheet,
        int $filaInicio,
        string $colNo,
        string $colConcepto,
        string $colTotal,
        string $titulo,
        string $totalHeader,
        array $conceptos,
        string $totalFormat = '#,##0',
        string $headerColor = '0070C0'
    ): void {
        $sheet->setCellValue($colNo . $filaInicio, 'No.');
        $sheet->setCellValue($colConcepto . $filaInicio, $titulo);
        $sheet->setCellValue($colTotal . $filaInicio, $totalHeader);
        $sheet->getRowDimension($filaInicio)->setRowHeight(22);
        $sheet->getStyle($colNo . $filaInicio . ':' . $colTotal . $filaInicio)->applyFromArray($this->estiloEncabezado($headerColor));

        $fila = $filaInicio + 1;

        foreach ($conceptos as $numero => $concepto) {
            $sheet->setCellValue($colNo . $fila, $numero);
            $sheet->setCellValue($colConcepto . $fila, $concepto);
            $sheet->getRowDimension($fila)->setRowHeight(20);
            $fila++;
        }

        $totalRow = $fila;
        $sheet->mergeCells($colNo . $totalRow . ':' . $colConcepto . $totalRow);
        $sheet->setCellValue($colNo . $totalRow, 'TOTAL');
        $sheet->setCellValue($colTotal . $totalRow, '=SUM(' . $colTotal . ($filaInicio + 1) . ':' . $colTotal . ($totalRow - 1) . ')');

        $sheet->getStyle($colNo . ($filaInicio + 1) . ':' . $colTotal . ($totalRow - 1))->applyFromArray($this->estiloTablaControl());
        $sheet->getStyle($colNo . ($filaInicio + 1) . ':' . $colNo . ($totalRow - 1))->getFont()->setBold(true);
        $sheet->getStyle($colNo . ($filaInicio + 1) . ':' . $colNo . ($totalRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($colTotal . ($filaInicio + 1) . ':' . $colTotal . ($totalRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($colNo . $totalRow . ':' . $colTotal . $totalRow)->applyFromArray($this->estiloTotalControl());
        $sheet->getStyle($colTotal . $totalRow)->getNumberFormat()->setFormatCode($totalFormat);
    }

    private function llenarTablasFinales(Worksheet $sheet, int $filaInicio): void
    {
        $clasificacionVehiculos = [
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

        $this->llenarTablaSinTotal(
            $sheet,
            $filaInicio,
            'B',
            'C',
            'D',
            'HECHOS DE TRÁNSITO',
            'CANTIDAD',
            $clasificacionVehiculos
        );

        $vehiculosInvolucrados = [
            1 => 'VEHÍCULOS PARTICULARES INVOL.',
            2 => 'VEHÍCULOS SERV. PÚBLIC. INVOL.',
            3 => 'MOTOS INVOLUCRADAS',
            4 => 'VEHÍCULOS OFICIALES INVOL',
        ];

        $this->llenarTablaSinTotal(
            $sheet,
            $filaInicio,
            'F',
            'G',
            'H',
            'HECHOS DE TRÁNSITO',
            'CANTIDAD',
            $vehiculosInvolucrados
        );

        $liberaciones = [
            1 => 'LIBERACIÓN MOTOCICLETAS',
            2 => 'LIBERACIÓN VEHÍCULOS',
            3 => 'LIBERACIÓN CAMIONES',
            4 => 'LIBERACIÓN REMOLQUES',
        ];

        $this->llenarTablaConceptos(
            $sheet,
            $filaInicio + 6,
            'F',
            'G',
            'H',
            'LIBERACIONES',
            'CANTIDAD',
            $liberaciones,
            '#,##0',
            '00B0F0'
        );

        $this->llenarTablaSinTotal(
            $sheet,
            $filaInicio + 13,
            'F',
            'G',
            'H',
            'ÁREAS AUXILIARES',
            'CANTIDAD',
            [
                1 => 'EXÁMEN TEÓRICO',
            ],
            '00B0F0'
        );
    }

    private function llenarTablaSinTotal(
        Worksheet $sheet,
        int $filaInicio,
        string $colNo,
        string $colConcepto,
        string $colTotal,
        string $titulo,
        string $totalHeader,
        array $conceptos,
        string $headerColor = '0070C0'
    ): void {
        $sheet->setCellValue($colNo . $filaInicio, 'No.');
        $sheet->setCellValue($colConcepto . $filaInicio, $titulo);
        $sheet->setCellValue($colTotal . $filaInicio, $totalHeader);
        $sheet->getRowDimension($filaInicio)->setRowHeight(22);
        $sheet->getStyle($colNo . $filaInicio . ':' . $colTotal . $filaInicio)->applyFromArray($this->estiloEncabezado($headerColor));

        $fila = $filaInicio + 1;

        foreach ($conceptos as $numero => $concepto) {
            $sheet->setCellValue($colNo . $fila, $numero);
            $sheet->setCellValue($colConcepto . $fila, $concepto);
            $sheet->getRowDimension($fila)->setRowHeight(20);
            $fila++;
        }

        $sheet->getStyle($colNo . ($filaInicio + 1) . ':' . $colTotal . ($fila - 1))->applyFromArray($this->estiloTablaControl());
        $sheet->getStyle($colNo . ($filaInicio + 1) . ':' . $colNo . ($fila - 1))->getFont()->setBold(true);
        $sheet->getStyle($colNo . ($filaInicio + 1) . ':' . $colNo . ($fila - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($colTotal . ($filaInicio + 1) . ':' . $colTotal . ($fila - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function estiloEncabezado(string $color): array
    {
        return [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $color],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
    }

    private function estiloFilaCatalogo(string $relleno): array
    {
        return [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $relleno],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ];
    }

    private function estiloTablaControl(): array
    {
        return [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ];
    }

    private function estiloTotalControl(): array
    {
        return [
            'font' => ['bold' => true],
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
                'wrapText' => true,
            ],
        ];
    }

    private function numeroVisible($valor)
    {
        if (is_float($valor)) {
            $valor = round($valor, 2);
        }

        return (float) $valor > 0 ? $valor : null;
    }
}
