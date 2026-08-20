<?php

namespace App\Services\Siniestros\Hojas;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TotalSheetService extends BaseSiniestrosSheetService
{
    private Carbon $inicioCorte;
    private Carbon $finCorte;
    private string $fechaCorte;
    public function generar(Worksheet $sheet, Collection $personal, Collection $actividades, string $fecha, Carbon $inicio, Carbon $fin): void
    {
        $this->fechaCorte = $fecha;
        $this->inicioCorte = $inicio->copy();
        $this->finCorte = $fin->copy();

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

                if ($this->normalizarTextoComparacion($subcategoria['nombre'] ?? '') === 'SINIESTROS') {
                    $item = $this->resumenHechosPrincipal();
                }

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
        $sheet->setCellValue('C1', 'UNIDAD DE ATENCIÓN A SINIESTROS');
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
            $resumen[$subcategoriaId]['unidades'] += $this->contarUnidades($actividad->patrullas_participantes_texto ?? null);
            $resumen[$subcategoriaId]['kilometros'] += is_numeric($actividad->km_recorridos ?? null)
                ? (float) $actividad->km_recorridos
                : 0;
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

        $datos = $this->obtenerResumenControlVehicular();

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
            $item = $datos[$numero] ?? [
                'vehiculos' => 0,
                'motocicletas' => 0,
                'camiones' => 0,
                'otros' => 0,
            ];

            $sheet->setCellValue('C' . $fila, $concepto);
            $sheet->setCellValue('D' . $fila, $this->numeroVisible($item['vehiculos']));
            $sheet->setCellValue('E' . $fila, $this->numeroVisible($item['motocicletas']));
            $sheet->setCellValue('F' . $fila, $this->numeroVisible($item['camiones']));
            $sheet->setCellValue('G' . $fila, $this->numeroVisible($item['otros']));
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

        $datos = $this->obtenerResumenControlAseguramientos();

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
            $sheet->setCellValue('D' . $fila, $this->numeroVisible($datos['personas'][$numero] ?? 0));
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
            $sheet->setCellValue('F' . $row, $this->numeroVisible($datos['armas'][$index + 1] ?? 0));
        }

        foreach ($drogas as $index => $concepto) {
            $row = $headerRow + 1 + $index;
            $sheet->setCellValue('G' . $row, $concepto);
            $sheet->setCellValue('H' . $row, $this->numeroVisible($datos['drogas'][$index + 1] ?? 0));
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

        $datos = $this->obtenerResumenOtrosAseguramientos();

        $sheet->setCellValue('B' . $filaInicio, 'No.');
        $sheet->setCellValue('C' . $filaInicio, 'OTROS ASEGURAMIENTOS');
        $sheet->setCellValue('D' . $filaInicio, 'TOTAL');
        $sheet->getRowDimension($filaInicio)->setRowHeight(22);
        $sheet->getStyle('B' . $filaInicio . ':D' . $filaInicio)->applyFromArray($this->estiloEncabezado('0070C0'));

        $fila = $filaInicio + 1;

        foreach ($conceptos as $numero => $concepto) {
            $sheet->setCellValue('B' . $fila, $numero);
            $sheet->setCellValue('C' . $fila, $concepto);
            $sheet->setCellValue('D' . $fila, $this->numeroVisible($datos[$numero] ?? 0));
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
        $datos = $this->obtenerResumenHechosTransito();
        $involucrados = $this->obtenerResumenInvolucradosHechosTransito();

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

        $sheet->setCellValue('D' . ($filaInicio + 1), $this->numeroVisible($datos['RESUELTOS']));
        $sheet->setCellValue('D' . ($filaInicio + 2), $this->numeroVisible($datos['PENDIENTES']));
        $sheet->setCellValue('D' . ($filaInicio + 3), $this->numeroVisible($datos['TURNADOS']));

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

        $sheet->setCellValue('H' . ($filaInicio + 1), $this->numeroVisible($involucrados['hombres']));
        $sheet->setCellValue('H' . ($filaInicio + 2), $this->numeroVisible($involucrados['mujeres']));
        $sheet->setCellValue('H' . ($filaInicio + 3), $this->numeroVisible($involucrados['menores']));
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
        $datos = $this->obtenerResumenTiposHechosTransito();

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
            $item = $datos[$concepto] ?? [
                'cantidad' => 0,
                'lesionados' => 0,
                'heridos' => 0,
                'defunciones' => 0,
                'fuero_comun' => 0,
            ];

            $sheet->setCellValue('C' . $fila, $concepto);
            $sheet->setCellValue('D' . $fila, $this->numeroVisible($item['cantidad']));
            $sheet->setCellValue('E' . $fila, $this->numeroVisible($item['lesionados']));
            $sheet->setCellValue('F' . $fila, $this->numeroVisible($item['heridos']));
            $sheet->setCellValue('G' . $fila, $this->numeroVisible($item['defunciones']));
            $sheet->setCellValue('H' . $fila, $this->numeroVisible($item['fuero_comun']));
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
        $datos = $this->obtenerResumenChoquesDanios();

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

        foreach ($choques as $index => $concepto) {
            $sheet->setCellValue(
                'D' . ($filaInicio + $index),
                $this->numeroVisible($datos['tipos'][$concepto] ?? 0)
            );
        }

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

        $sheet->setCellValue('H' . ($filaInicio + 1), $datos['monto_total']);
        $sheet->setCellValue('H' . ($filaInicio + 2), $datos['monto_vehiculos']);
        $sheet->setCellValue('H' . ($filaInicio + 3), $datos['monto_otros']);
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
        $datos = $this->obtenerClasificacionVehiculos();

        $clasificacionVehiculos = [
            1 => 'AUTOMÓVIL',
            2 => 'CAMIONETA',
            3 => 'CAMIÓN',
            4 => 'MOTOCICLETA',
            5 => 'BICICLETA',
            6 => 'REMOLQUE',
            7 => 'MAQUINARIA',
            8 => 'TREN',
            9 => 'SEMOVIENTE',
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

        foreach ($clasificacionVehiculos as $index => $concepto) {
            $sheet->setCellValue(
                'D' . ($filaInicio + $index),
                $this->numeroVisible($datos['clasificacion'][$concepto] ?? 0)
            );
        }

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

        $sheet->setCellValue(
            'H' . ($filaInicio + 1),
            $this->numeroVisible($datos['resumen']['particulares'])
        );

        $sheet->setCellValue(
            'H' . ($filaInicio + 2),
            $this->numeroVisible($datos['resumen']['publicos'])
        );

        $sheet->setCellValue(
            'H' . ($filaInicio + 3),
            $this->numeroVisible($datos['resumen']['motos'])
        );

        $sheet->setCellValue(
            'H' . ($filaInicio + 4),
            $this->numeroVisible($datos['resumen']['oficiales'])
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

        $sheet->setCellValue(
            'H' . ($filaInicio + 7),
            $this->numeroVisible($datos['liberaciones']['motos'])
        );

        $sheet->setCellValue(
            'H' . ($filaInicio + 8),
            $this->numeroVisible($datos['liberaciones']['vehiculos'])
        );

        $sheet->setCellValue(
            'H' . ($filaInicio + 9),
            $this->numeroVisible($datos['liberaciones']['camiones'])
        );

        $sheet->setCellValue(
            'H' . ($filaInicio + 10),
            $this->numeroVisible($datos['liberaciones']['remolques'])
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

    private function contarUnidades(?string $texto): int
    {
        if (!$texto) {
            return 0;
        }

        $texto = str_replace(["\r\n", "\r", "\n", ';'], ',', $texto);
        $partes = array_filter(array_map('trim', explode(',', $texto)));

        return count($partes);
    }

    private function resumenHechosPrincipal(): array
    {
        $hechos = $this->hechosBase()
            ->select([
                'h.id',
                'h.unidad',
                'h.km_recorridos',
            ])
            ->get();

        $hechoIds = $hechos->pluck('id')->all();
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
            'kilometros' => $hechos->sum(function ($hecho) {
                return is_numeric($hecho->km_recorridos ?? null)
                    ? (float) $hecho->km_recorridos
                    : 0;
            }),
            'personas' => $personas,
            'recomendaciones' => 0,
        ];
    }

    private function hechosBase()
    {
        return DB::table('hechos as h')
            ->where('h.unidad_org_id', 1)
            ->whereRaw(
                "TIMESTAMP(
                    DATE(h.fecha),
                    COALESCE(h.hora, '00:00:00')
                ) >= ?
                AND
                TIMESTAMP(
                    DATE(h.fecha),
                    COALESCE(h.hora, '00:00:00')
                ) < ?",
                [
                    $this->inicioCorte->toDateTimeString(),
                    $this->finCorte->toDateTimeString(),
                ]
            );
    }

    private function obtenerResumenControlVehicular(): array
    {
        $datos = [];

        for ($i = 1; $i <= 13; $i++) {
            $datos[$i] = [
                'vehiculos' => 0,
                'motocicletas' => 0,
                'camiones' => 0,
                'otros' => 0,
            ];
        }

        $hechos = $this->hechosBase()
            ->select([
                'h.id',
                'h.checaron_antecedentes',
                'h.oficio_mp',
                'h.vehiculos_mp',
            ])
            ->get();

        $hechoIds = $hechos->pluck('id')->all();
        $vehiculosPorHecho = [];

        if (!empty($hechoIds)) {
            $vehiculos = DB::table('hecho_vehiculo as hv')
                ->join('vehiculos as v', 'hv.vehiculo_id', '=', 'v.id')
                ->select([
                    'hv.hecho_id',
                    'v.tipo',
                    'v.capacidad_personas',
                    'v.corralon',
                    'v.grua_id',
                ])
                ->whereIn('hv.hecho_id', $hechoIds)
                ->orderBy('hv.hecho_id')
                ->orderBy('v.id')
                ->get();

            foreach ($vehiculos as $vehiculo) {
                $vehiculosPorHecho[$vehiculo->hecho_id][] = $vehiculo;

                if ($this->vehiculoTieneCorralon($vehiculo)) {
                    $this->incrementarControlVehicular(
                        $datos,
                        5,
                        $vehiculo->tipo,
                        1,
                        $vehiculo->capacidad_personas
                    );
                }
            }
        }

        foreach ($hechos as $hecho) {
            if ((int) ($hecho->checaron_antecedentes ?? 0) === 1) {
                $datos[1]['vehiculos']++;
            }

            $cantidadMp = (int) ($hecho->vehiculos_mp ?? 0);

            if ($cantidadMp > 0 || !empty($hecho->oficio_mp)) {
                $cantidadMp = $cantidadMp > 0 ? $cantidadMp : 1;
                $clasificados = 0;

                foreach (($vehiculosPorHecho[$hecho->id] ?? []) as $vehiculo) {
                    if ($clasificados >= $cantidadMp) {
                        break;
                    }

                    $this->incrementarControlVehicular(
                        $datos,
                        6,
                        $vehiculo->tipo,
                        1,
                        $vehiculo->capacidad_personas
                    );

                    $clasificados++;
                }

                if ($clasificados < $cantidadMp) {
                    $datos[6]['vehiculos'] += ($cantidadMp - $clasificados);
                }
            }
        }

        $vehiculosActividad = DB::table('actividades as a')
            ->join('actividad_vehiculo as av', 'a.id', '=', 'av.actividad_id')
            ->join('vehiculos as v', 'av.vehiculo_id', '=', 'v.id')
            ->leftJoin(
                'actividad_subcategorias as s',
                'a.actividad_subcategoria_id',
                '=',
                's.id'
            )
            ->select([
                'v.tipo',
                'v.capacidad_personas',
                'v.corralon',
                'v.grua_id',
                's.nombre as subcategoria',
            ])
            ->where('a.unidad_org_id', 1)
            ->whereRaw(
                "TIMESTAMP(
                    DATE(a.fecha),
                    COALESCE(a.hora, '00:00:00')
                ) >= ?
                AND TIMESTAMP(
                    DATE(a.fecha),
                    COALESCE(a.hora, '00:00:00')
                ) < ?",
                [
                    $this->inicioCorte->toDateTimeString(),
                    $this->finCorte->toDateTimeString(),
                ]
            )
            ->get();

        foreach ($vehiculosActividad as $vehiculo) {
            if (!$this->vehiculoTieneCorralon($vehiculo)) {
                continue;
            }

            $fila = $this->filaControlVehicularPorActividad(
                $vehiculo->subcategoria
            );

            $this->incrementarControlVehicular(
                $datos,
                $fila,
                $vehiculo->tipo,
                1,
                $vehiculo->capacidad_personas
            );
        }

        $puestas = DB::table('puestas_disposicion as p')
            ->join(
                'puestas_disposicion_vehiculos as pv',
                'p.id',
                '=',
                'pv.puesta_disposicion_id'
            )
            ->select([
                'p.motivo',
                'pv.tipo',
                'pv.calidad',
                'pv.con_reporte_robo',
            ])
            ->where('p.unidad_id', 1)
            ->whereRaw(
                "TIMESTAMP(
                    DATE(p.fecha_puesta),
                    COALESCE(p.hora_puesta, '00:00:00')
                ) >= ?
                AND TIMESTAMP(
                    DATE(p.fecha_puesta),
                    COALESCE(p.hora_puesta, '00:00:00')
                ) < ?",
                [
                    $this->inicioCorte->toDateTimeString(),
                    $this->finCorte->toDateTimeString(),
                ]
            )
            ->get();

        foreach ($puestas as $puesta) {
            $columna = $this->clasificarTipoVehiculoControl(
                $puesta->tipo
            );

            $motivo = $this->normalizarTextoComparacion(
                $puesta->motivo ?? ''
            );

            $calidad = $this->normalizarTextoComparacion(
                $puesta->calidad ?? ''
            );

            if ($this->contieneAlguno($motivo, ['HECHO DE TRANSITO'])) {
                $datos[6][$columna]++;
            } elseif ($this->contieneAlguno($motivo, ['ABANDONO'])) {
                $datos[8][$columna]++;
            } elseif ($this->contieneAlguno($motivo, ['HECHO DELICTIVO'])) {
                $datos[9][$columna]++;
            } elseif ($this->contieneAlguno($motivo, ['ALTERACION'])) {
                $datos[10][$columna]++;
            } elseif (
                $this->contieneAlguno($motivo, ['REPORTE DE ROBO'])
                || (int) ($puesta->con_reporte_robo ?? 0) === 1
                || $calidad === 'ROBADO'
            ) {
                $datos[11][$columna]++;
            } else {
                $datos[13][$columna]++;
            }
        }

        return $datos;
    }

    private function incrementarControlVehicular(
        array &$datos,
        int $fila,
        ?string $tipo,
        int $cantidad = 1,
        $capacidadPersonas = null
    ): void {
        $columna = $this->clasificarTipoVehiculoControl(
            $tipo,
            $capacidadPersonas
        );

        $datos[$fila][$columna] += $cantidad;
    }

    private function vehiculoTieneCorralon($vehiculo): bool
    {
        if (!empty($vehiculo->grua_id)) {
            return true;
        }

        $corralon = $this->normalizarTextoComparacion($vehiculo->corralon ?? '');

        if ($corralon === '') {
            return false;
        }

        return !in_array($corralon, [
            'N/A',
            'NA',
            'NO',
            'NO APLICA',
            'NO SE UTILIZA',
            'NO SE UTILIZO',
            'NINGUNO',
            'NULL',
            'O',
            'SIN CORRALON',
            'SIN DATO',
        ], true);
    }

    private function filaControlVehicularPorActividad(?string $subcategoria): int
    {
        $texto = $this->normalizarTextoComparacion($subcategoria);

        if ($this->contieneAlguno($texto, ['HECHO DE TRANSITO', 'SINIESTRO', 'ACCIDENTE'])) {
            return 5;
        }

        if ($this->contieneAlguno($texto, ['ABANDONO'])) {
            return 8;
        }

        if ($this->contieneAlguno($texto, ['DELICTIVO'])) {
            return 9;
        }

        if ($this->contieneAlguno($texto, ['ROBO'])) {
            return 11;
        }

        return 4;
    }

    private function tipoGeneralDesdeCarroceria(?string $tipo, $capacidadPersonas = null): ?string
    {
        $tipo = $this->normalizarTextoComparacion($tipo);

        if ($this->tipoVehiculoNoEspecificado($tipo)) {
            return null;
        }

        $directos = [
            'AUTOMOVIL' => 'automovil',
            'CAMIONETA' => 'camioneta',
            'CAMION' => 'camion',
            'MOTOCICLETA' => 'motocicleta',
            'BICICLETA' => 'bicicleta',
            'REMOLQUE' => 'remolque',
            'MAQUINARIA' => 'maquinaria',
            'TREN' => 'tren',
            'SEMOVIENTE' => 'semoviente',
        ];

        if (isset($directos[$tipo])) {
            return $directos[$tipo];
        }

        if (in_array($tipo, [
            'SEDAN',
            'HATCHBACK',
            'COUPE',
            'SUV',
            'CONVERTIBLE',
            'WAGON',
            'CROSSOVER',
        ], true)) {
            return 'automovil';
        }

        if (in_array($tipo, [
            'PICK-UP',
            'PICKUP',
            'PANEL',
            'VAGONETA',
            'FURGONETA',
            'VAN',
            'MINIVAN',
            'DOBLE CABINA',
            'CABINA SENCILLA',
        ], true)) {
            return 'camioneta';
        }

        if (in_array($tipo, [
            'TRABAJO',
            'CRUISER',
            'CRUISIER',
            'DOBLE PROPOSITO',
            'SCOOTER',
            'ENDURO',
            'NAKED',
            'PISTA',
            'CHOPPER',
            'CUATRIMOTO',
            'MOTOCICLISTA',
        ], true)) {
            return 'motocicleta';
        }

        if (in_array($tipo, [
            'MONTANA',
            'RUTA',
            'BMX',
            'URBANA',
            'PLEGABLE',
        ], true)) {
            return 'bicicleta';
        }

        if (in_array($tipo, [
            'CAJA ABIERTA',
            'CISTERNA',
            'PIPA',
            'GRUA',
            'TORTON',
            'RABON',
            'TRACTO',
            'TRACTOCAMION',
            'TRACTOCAMIÓN',
            'REDILAS',
            'AUTOBUS',
        ], true)) {
            return 'camion';
        }

        if (in_array($tipo, [
            'CAMA BAJA',
            'GONDOLA',
            'DOLLY',
            'PORTACONTENEDOR',
        ], true)) {
            return 'remolque';
        }

        if (in_array($tipo, [
            'PLATAFORMA',
            'CAJA CERRADA',
            'CAJA SECA',
            'REFRIGERADO',
            'VOLTEO',
        ], true)) {
            if (is_numeric($capacidadPersonas) && (int) $capacidadPersonas > 0) {
                return 'camion';
            }

            return 'remolque';
        }

        if (in_array($tipo, [
            'RETROEXCAVADORA',
            'EXCAVADORA',
            'CARGADOR FRONTAL',
            'MOTOCONFORMADORA',
            'BULLDOZER',
            'RODILLO COMPACTADOR',
            'GRUA INDUSTRIAL',
            'MONTACARGAS',
            'TRACTOR AGRICOLA',
            'PAVIMENTADORA',
            'COMPACTADORA',
        ], true)) {
            return 'maquinaria';
        }

        if (in_array($tipo, [
            'LOCOMOTORA',
            'VAGON',
            'TREN DE CARGA',
            'TREN DE PASAJEROS',
            'TRANVIA',
            'METRO',
            'FERROCARRIL',
        ], true)) {
            return 'tren';
        }

        if (in_array($tipo, [
            'CABALLO',
            'BURRO',
            'VACA',
            'MULA',
            'OTRO ANIMAL DE TIRO',
        ], true)) {
            return 'semoviente';
        }

        return null;
    }

    private function clasificarTipoVehiculoControl(
        ?string $tipo,
        $capacidadPersonas = null
    ): string {
        $tipoGeneral = $this->tipoGeneralDesdeCarroceria(
            $tipo,
            $capacidadPersonas
        );

        return match ($tipoGeneral) {
            'automovil',
            'camioneta' => 'vehiculos',

            'motocicleta' => 'motocicletas',

            'camion' => 'camiones',

            default => 'otros',
        };
    }

    private function obtenerResumenControlAseguramientos(): array
    {
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

        $hechos = $this->hechosBase()
            ->select([
                'h.personas_mp',
                'h.checaron_antecedentes',
            ])
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
            ->where('p.unidad_id', 1)
            ->whereRaw(
                "TIMESTAMP(DATE(p.fecha_puesta), COALESCE(p.hora_puesta, '00:00:00')) >= ? AND TIMESTAMP(DATE(p.fecha_puesta), COALESCE(p.hora_puesta, '00:00:00')) < ?",
                [
                    $this->inicioCorte->toDateTimeString(),
                    $this->finCorte->toDateTimeString(),
                ]
            )
            ->get();

        foreach ($personasPuestas as $persona) {
            $texto = $this->normalizarTextoComparacion(
                trim(
                    ($persona->motivo ?? '') . ' '
                    . ($persona->tipo_puesta ?? '') . ' '
                    . ($persona->delito_o_motivo ?? '') . ' '
                    . ($persona->calidad ?? '')
                )
            );

            $datos['personas'][4]++;

            if ($this->contieneAlguno($texto, ['BARANDILLA'])) {
                $datos['personas'][2]++;
            } elseif ($this->contieneAlguno($texto, ['ALCOHOL'])) {
                $datos['personas'][3]++;
            } elseif (
                $this->contieneAlguno($texto, ['ROBO'])
                && $this->contieneAlguno($texto, ['VEHIC', 'MOTO', 'CAMION'])
            ) {
                $datos['personas'][9]++;
            } elseif ($this->contieneAlguno($texto, ['ROBO'])) {
                $datos['personas'][5]++;
            } elseif ($this->contieneAlguno($texto, ['LESION'])) {
                $datos['personas'][6]++;
            } elseif ($this->contieneAlguno($texto, ['HOMICIDIO CULPOSO'])) {
                $datos['personas'][7]++;
            } elseif ($this->contieneAlguno($texto, ['HOMICIDIO DOLOSO'])) {
                $datos['personas'][8]++;
            } elseif ($this->contieneAlguno($texto, ['ARMA'])) {
                $datos['personas'][10]++;
            } elseif ($this->contieneAlguno($texto, ['DROGA', 'MARIHUANA', 'CRISTAL', 'COCAINA'])) {
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
            ->where('p.unidad_id', 1)
            ->whereRaw(
                "TIMESTAMP(DATE(p.fecha_puesta), COALESCE(p.hora_puesta, '00:00:00')) >= ? AND TIMESTAMP(DATE(p.fecha_puesta), COALESCE(p.hora_puesta, '00:00:00')) < ?",
                [
                    $this->inicioCorte->toDateTimeString(),
                    $this->finCorte->toDateTimeString(),
                ]
            )
            ->get();

        foreach ($objetos as $objeto) {
            $cantidad = is_numeric($objeto->cantidad ?? null)
                ? (float) $objeto->cantidad
                : 1;

            $texto = $this->normalizarTextoComparacion(
                trim(
                    ($objeto->tipo_objeto ?? '') . ' '
                    . ($objeto->descripcion ?? '') . ' '
                    . ($objeto->unidad_medida ?? '')
                )
            );

            if ($this->contieneAlguno($texto, ['CORTA'])) {
                $datos['armas'][2] += $cantidad;
            } elseif ($this->contieneAlguno($texto, ['LARGA'])) {
                $datos['armas'][3] += $cantidad;
            } elseif ($this->contieneAlguno($texto, ['CARGADOR'])) {
                $datos['armas'][4] += $cantidad;
            } elseif ($this->contieneAlguno($texto, ['CARTUCHO'])) {
                $datos['armas'][5] += $cantidad;
            } elseif (
                $this->contieneAlguno($texto, ['GRANADA'])
                && !$this->contieneAlguno($texto, ['LANZA'])
            ) {
                $datos['armas'][6] += $cantidad;
            } elseif ($this->contieneAlguno($texto, ['LANZA'])) {
                $datos['armas'][7] += $cantidad;
            } elseif ($this->contieneAlguno($texto, ['PUNZO', 'CUCHILLO', 'NAVAJA'])) {
                $datos['armas'][8] += $cantidad;
            } elseif ($this->contieneAlguno($texto, ['ARMA'])) {
                $datos['armas'][1] += $cantidad;
            }

            if ($this->contieneAlguno($texto, ['MARIHUANA']) && $this->contieneAlguno($texto, ['PLANTA'])) {
                $datos['drogas'][7] += $cantidad;
            } elseif ($this->contieneAlguno($texto, ['MARIHUANA', 'CANNABIS'])) {
                $datos['drogas'][2] += $cantidad;
            } elseif ($this->contieneAlguno($texto, ['CRISTAL'])) {
                $datos['drogas'][3] += $cantidad;
            } elseif ($this->contieneAlguno($texto, ['COCAINA'])) {
                $datos['drogas'][4] += $cantidad;
            } elseif ($this->contieneAlguno($texto, ['PASTILLA'])) {
                $datos['drogas'][5] += $cantidad;
            } elseif ($this->contieneAlguno($texto, ['PLANTIO'])) {
                $datos['drogas'][6] += $cantidad;
            } elseif ($this->contieneAlguno($texto, ['DROGA'])) {
                $datos['drogas'][1] += $cantidad;
            }
        }

        return $datos;
    }

    private function obtenerResumenOtrosAseguramientos(): array
    {
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
            ->where('p.unidad_id', 1)
            ->whereRaw(
                "TIMESTAMP(DATE(p.fecha_puesta), COALESCE(p.hora_puesta, '00:00:00')) >= ? AND TIMESTAMP(DATE(p.fecha_puesta), COALESCE(p.hora_puesta, '00:00:00')) < ?",
                [
                    $this->inicioCorte->toDateTimeString(),
                    $this->finCorte->toDateTimeString(),
                ]
            )
            ->get();

        foreach ($objetos as $objeto) {
            $cantidad = is_numeric($objeto->cantidad ?? null)
                ? (float) $objeto->cantidad
                : 1;

            $texto = $this->normalizarTextoComparacion(
                trim(
                    ($objeto->tipo_objeto ?? '') . ' '
                    . ($objeto->descripcion ?? '') . ' '
                    . ($objeto->unidad_medida ?? '')
                )
            );

            if ($this->contieneAlguno($texto, ['AGUACATE'])) {
                $datos[1] += $cantidad;
            } elseif ($this->contieneAlguno($texto, ['MADERA'])) {
                $datos[2] += $cantidad;
            } elseif ($this->contieneAlguno($texto, ['DINERO', 'EFECTIVO', 'PESO'])) {
                $datos[3] += $cantidad;
            } elseif (!$this->esObjetoArmaDroga($texto)) {
                $datos[4] += $cantidad;
            }
        }

        return $datos;
    }

    private function esObjetoArmaDroga(string $texto): bool
    {
        return $this->contieneAlguno($texto, [
            'ARMA',
            'CORTA',
            'LARGA',
            'CARGADOR',
            'CARTUCHO',
            'GRANADA',
            'LANZA',
            'PUNZO',
            'CUCHILLO',
            'NAVAJA',
            'DROGA',
            'MARIHUANA',
            'CANNABIS',
            'CRISTAL',
            'COCAINA',
            'PASTILLA',
            'PLANTIO',
        ]);
    }

    private function obtenerResumenHechosTransito(): array
    {
        $datos = [
            'RESUELTOS' => 0,
            'PENDIENTES' => 0,
            'TURNADOS' => 0,
        ];

        $hechos = $this->hechosBase()
            ->select('h.situacion')
            ->get();

        foreach ($hechos as $hecho) {
            $situacion = $this->normalizarTextoComparacion($hecho->situacion ?? '');

            if (in_array($situacion, ['RESUELTO', 'REPORTE'], true)) {
                $datos['RESUELTOS']++;
            } elseif ($situacion === 'TURNADO') {
                $datos['TURNADOS']++;
            } else {
                $datos['PENDIENTES']++;
            }
        }

        return $datos;
    }

    private function obtenerResumenInvolucradosHechosTransito(): array
    {
        $conductores = $this->hechosBase()
            ->join('hecho_vehiculo as hv', 'h.id', '=', 'hv.hecho_id')
            ->join('vehiculo_conductor as vc', 'hv.vehiculo_id', '=', 'vc.vehiculo_id')
            ->join('conductores as c', 'vc.conductor_id', '=', 'c.id')
            ->select([
                'c.id',
                'c.sexo',
                'c.edad',
            ])
            ->distinct()
            ->get();

        $datos = [
            'hombres' => 0,
            'mujeres' => 0,
            'menores' => 0,
        ];

        foreach ($conductores as $conductor) {
            $sexo = $this->normalizarTextoComparacion($conductor->sexo ?? '');
            $edad = is_numeric($conductor->edad ?? null)
                ? (int) $conductor->edad
                : null;

            if (in_array($sexo, ['MASCULINO', 'HOMBRE'], true)) {
                $datos['hombres']++;
            } elseif (in_array($sexo, ['FEMENINO', 'MUJER'], true)) {
                $datos['mujeres']++;
            }

            if ($edad !== null && $edad < 18) {
                $datos['menores']++;
            }
        }

        return $datos;
    }

    private function obtenerResumenTiposHechosTransito(): array
    {
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

        $hechos = $this->hechosBase()
            ->select([
                'h.id',
                'h.tipo_hecho',
            ])
            ->get();

        $hechoIds = $hechos->pluck('id')->all();

        foreach ($hechos as $hecho) {
            $concepto = $this->normalizarTipoHechoSiniestros($hecho->tipo_hecho);

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
                $concepto = $this->normalizarTipoHechoSiniestros($lesionado->tipo_hecho);

                if (!isset($datos[$concepto])) {
                    continue;
                }

                $tipoLesion = $this->normalizarTextoComparacion($lesionado->tipo_lesion ?? '');

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

    private function normalizarTipoHechoSiniestros(?string $tipo): string
    {
        $tipo = $this->normalizarTextoComparacion($tipo);

        $mapa = [
            'EXPLOSION' => 'EXPLOSIÓN',
            'INCENDIO' => 'INCENDIO',
            'DESBARRANCAMIENTO' => 'DESBARRANCAMIENTO',
            'VOLCADURA' => 'VOLCADURA',
            'SALIDA DE SUPERFICIE DE RODAMIENTO' => 'SALIDA DE RODAMIENTO',
            'SALIDA DE RODAMIENTO' => 'SALIDA DE RODAMIENTO',
            'SUBIDA AL CAMELLON' => 'SUBIDA A CAMELLÓN',
            'SUBIDA A CAMELLON' => 'SUBIDA A CAMELLÓN',
            'CAIDA DE MOTOCICLETA' => 'CAIDA DE MOTOCICLETA',
            'COLISION CONTRA OBJETO FIJO' => 'CHOQUE OBJETO FIJO',
            'CHOQUE OBJETO FIJO' => 'CHOQUE OBJETO FIJO',
            'COLISION POR ALCANCE' => 'COLISIÓN POR ALCANCE',
            'COLISION POR NO RESPETAR SEMAFORO' => 'COLISIÓN POR NO RESPETAR SEMÁFORO',
            'COLISION POR INVASION DE CARRIL' => 'COLISIÓN POR INVASIÓN DE CARRIL',
            'COLISION POR CAMBIO DE CARRIL' => 'COLISIÓN POR CAMBIO DE CARRIL',
            'COLISION POR CORTE DE CIRCULACION' => 'COLISIÓN POR CORTE DE CIRCULACIÓN',
            'COLISION POR MANIOBRA DE REVERSA' => 'COLISIÓN POR MANIOBRA REVERSA',
            'COLISION POR MANIOBRA REVERSA' => 'COLISIÓN POR MANIOBRA REVERSA',
            'CAIDA A LA CUNETA' => 'CAIDA A CUNETA',
            'CAIDA A CUNETA' => 'CAIDA A CUNETA',
            'CAIDA ACUATICA DE VEHICULO' => 'CAIDA ACUÁTICA DE VEHÍCULO',
            'COLISION CON PEATON' => 'COLISIÓN CON PEATÓN',
            'ATROPELLO' => 'COLISIÓN CON PEATÓN',
            'ATROPELLAMIENTO' => 'COLISIÓN CON PEATÓN',
        ];

        return $mapa[$tipo] ?? $tipo;
    }

    private function obtenerResumenChoquesDanios(): array
    {
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
            'monto_vehiculos' => 0,
            'monto_otros' => 0,
            'monto_total' => 0,
        ];

        $hechos = $this->hechosBase()
            ->select([
                'h.id',
                'h.tipo_hecho',
                'h.monto_danos_patrimoniales',
            ])
            ->get();

        $hechoIds = $hechos->pluck('id')->all();
        $vehiculosPorHecho = [];

        if (!empty($hechoIds)) {
            $vehiculos = DB::table('hecho_vehiculo as hv')
                ->join('vehiculos as v', 'hv.vehiculo_id', '=', 'v.id')
                ->select([
                    'hv.hecho_id',
                    'v.tipo',
                    'v.capacidad_personas',
                    'v.monto_danos',
                ])
                ->whereIn('hv.hecho_id', $hechoIds)
                ->get();

            foreach ($vehiculos as $vehiculo) {
                $vehiculosPorHecho[$vehiculo->hecho_id][] = $vehiculo;

                if (is_numeric($vehiculo->monto_danos ?? null)) {
                    $datos['monto_vehiculos'] +=
                        (float) $vehiculo->monto_danos;
                }
            }
        }

        foreach ($hechos as $hecho) {
            if (is_numeric($hecho->monto_danos_patrimoniales ?? null)) {
                $datos['monto_otros'] +=
                    (float) $hecho->monto_danos_patrimoniales;
            }

            $tipoHecho = $this->normalizarTextoComparacion(
                $hecho->tipo_hecho ?? ''
            );

            if ($this->contieneAlguno($tipoHecho, ['PEATON', 'ATROPELLO'])) {
                $datos['tipos']['CHOQUE ENTRE VEHÍCULO Y PEATÓN']++;
                continue;
            }

            if (!$this->contieneAlguno($tipoHecho, ['COLISION', 'CHOQUE'])) {
                continue;
            }

            $vehiculos = $vehiculosPorHecho[$hecho->id] ?? [];

            $camiones = 0;
            $motocicletas = 0;
            $vehiculosNormales = 0;

            foreach ($vehiculos as $vehiculo) {
                $tipo = $this->clasificarVehiculoChoque(
                    $vehiculo->tipo,
                    $vehiculo->capacidad_personas
                );

                if ($tipo === 'camion') {
                    $camiones++;
                } elseif ($tipo === 'motocicleta') {
                    $motocicletas++;
                } else {
                    $vehiculosNormales++;
                }
            }

            $totalVehiculos =
                $camiones
                + $motocicletas
                + $vehiculosNormales;

            if ($totalVehiculos <= 1) {
                $datos['tipos']['CHOQUE DE VEHÍCULO UNICO']++;
            } elseif ($camiones > 0 && $motocicletas > 0) {
                $datos['tipos']['CHOQUE ENTRE CAMIÓN Y MOTOCICLETA']++;
            } elseif ($camiones > 0 && $vehiculosNormales > 0) {
                $datos['tipos']['CHOQUE ENTRE CAMIÓN Y VEHÍCULO']++;
            } elseif (
                $motocicletas >= 2
                && $vehiculosNormales === 0
                && $camiones === 0
            ) {
                $datos['tipos']['CHOQUE ENTRE MOTOCICLETAS']++;
            } elseif (
                $vehiculosNormales >= 2
                && $motocicletas === 0
                && $camiones === 0
            ) {
                $datos['tipos']['CHOQUE ENTRE VEHÍCULOS']++;
            } elseif (
                $motocicletas > 0
                && $vehiculosNormales > 0
            ) {
                $datos['tipos']['CHOQUE ENTRE MOTOCICLETA Y VEHÍCULO']++;
            } else {
                $datos['tipos']['CHOQUE DE VEHÍCULO UNICO']++;
            }
        }

        $datos['monto_total'] =
            $datos['monto_vehiculos']
            + $datos['monto_otros'];

        return $datos;
    }

    private function clasificarVehiculoChoque(
        ?string $tipo,
        $capacidadPersonas = null
    ): string {
        $tipoGeneral = $this->tipoGeneralDesdeCarroceria(
            $tipo,
            $capacidadPersonas
        );

        return match ($tipoGeneral) {
            'camion' => 'camion',
            'motocicleta' => 'motocicleta',
            default => 'vehiculo',
        };
    }

    private function obtenerClasificacionVehiculos(): array
    {
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

        $vehiculos = $this->hechosBase()
            ->join(
                'hecho_vehiculo as hv',
                'h.id',
                '=',
                'hv.hecho_id'
            )
            ->join(
                'vehiculos as v',
                'hv.vehiculo_id',
                '=',
                'v.id'
            )
            ->select([
                'v.tipo',
                'v.capacidad_personas',
                'v.tipo_servicio',
            ])
            ->get();

        foreach ($vehiculos as $vehiculo) {
            $tipo = $vehiculo->tipo ?? '';

            $servicio = $this->normalizarTextoComparacion(
                $vehiculo->tipo_servicio ?? ''
            );

            $clave = $this->mapearTipoVehiculoExcel(
                $tipo,
                $vehiculo->capacidad_personas
            );

            if ($clave !== null) {
                $datos['clasificacion'][$clave] =
                    ($datos['clasificacion'][$clave] ?? 0) + 1;
            }

            $this->sumarResumenVehiculo(
                $datos['resumen'],
                $tipo,
                $servicio,
                $vehiculo->capacidad_personas
            );
        }

        $liberaciones = DB::table('liberaciones as l')
            ->join(
                'vehiculos as v',
                'l.vehiculo_id',
                '=',
                'v.id'
            )
            ->join(
                'hecho_vehiculo as hv',
                'v.id',
                '=',
                'hv.vehiculo_id'
            )
            ->join(
                'hechos as h',
                'hv.hecho_id',
                '=',
                'h.id'
            )
            ->select([
                'l.id',
                'v.tipo',
                'v.capacidad_personas',
            ])
            ->where('h.unidad_org_id', 1)
            ->whereDate(
                'l.fecha_liberacion',
                $this->fechaCorte
            )
            ->distinct()
            ->get();

        foreach ($liberaciones as $liberacion) {
            $tipoGeneral = $this->tipoGeneralDesdeCarroceria(
                $liberacion->tipo,
                $liberacion->capacidad_personas
            );

            switch ($tipoGeneral) {
                case 'motocicleta':
                    $datos['liberaciones']['motos']++;
                    break;

                case 'camion':
                    $datos['liberaciones']['camiones']++;
                    break;

                case 'remolque':
                    $datos['liberaciones']['remolques']++;
                    break;

                case 'automovil':
                case 'camioneta':
                case 'bicicleta':
                case 'maquinaria':
                case 'tren':
                case 'semoviente':
                    $datos['liberaciones']['vehiculos']++;
                    break;
            }
        }

        return $datos;
    }

    private function sumarResumenVehiculo(
        array &$resumen,
        string $tipo,
        string $servicio,
        $capacidadPersonas = null
    ): void {
        $tipoGeneral = $this->tipoGeneralDesdeCarroceria(
            $tipo,
            $capacidadPersonas
        );

        if ($tipoGeneral === 'motocicleta') {
            $resumen['motos']++;
            return;
        }

        if ($this->contieneAlguno($servicio, ['OFICIAL'])) {
            $resumen['oficiales']++;
            return;
        }

        if ($this->contieneAlguno($servicio, ['PUBLIC'])) {
            $resumen['publicos']++;
            return;
        }

        $resumen['particulares']++;
    }

    private function mapearTipoVehiculoExcel(
        string $tipo,
        $capacidadPersonas = null
    ): ?string {
        $tipoGeneral = $this->tipoGeneralDesdeCarroceria(
            $tipo,
            $capacidadPersonas
        );

        return match ($tipoGeneral) {
            'automovil' => 'AUTOMÓVIL',
            'camioneta' => 'CAMIONETA',
            'camion' => 'CAMIÓN',
            'motocicleta' => 'MOTOCICLETA',
            'bicicleta' => 'BICICLETA',
            'remolque' => 'REMOLQUE',
            'maquinaria' => 'MAQUINARIA',
            'tren' => 'TREN',
            'semoviente' => 'SEMOVIENTE',
            default => null,
        };
    }

    private function esMotocicletaTipo(string $tipo): bool
    {
        return $this->contieneAlguno($tipo, [
            'MOTO',
            'MOTOCIC',
            'SCOOTER',
            'ENDURO',
            'NAKED',
            'PISTA',
            'CHOPPER',
            'CUATRIMOTO',
            'DOBLE PROPOSITO',
            'CRUISER',
            'CRUISIER',
            'TRABAJO',
        ]);
    }

    private function esBicicletaTipo(string $tipo): bool
    {
        return $this->contieneAlguno($tipo, [
            'BICICLETA',
            'BICI',
            'BMX',
            'MONTANA',
            'RUTA',
            'URBANA',
            'PLEGABLE',
        ]);
    }

    private function esCamionTipo(string $tipo): bool
    {
        return $this->contieneAlguno($tipo, [
            'CAMION',
            'AUTOBUS',
            'MICROBUS',
            'OMNIBUS',
            'TRACTO',
            'TORTON',
            'RABON',
            'CAJA',
            'PLATAFORMA',
            'VOLTEO',
            'PIPA',
            'CISTERNA',
            'REDILAS',
            'REFRIGERADO',
            'GRUA',
            'GONDOLA',
        ]);
    }

    private function esRemolqueTipo(string $tipo): bool
    {
        return $this->contieneAlguno($tipo, [
            'REMOLQUE',
            'DOLLY',
            'PORTACONTENEDOR',
            'CAMA BAJA',
        ]);
    }

    private function esOtroTipoVehiculo(string $tipo): bool
    {
        if ($tipo === 'VAGON') {
            return true;
        }

        return $this->contieneAlguno($tipo, [
            'TREN',
            'LOCOMOTORA',
            'FERROCARRIL',
            'VAGON DE TREN',
            'TRANVIA',
            'METRO',
            'SEMOVIENTE',
            'CABALLO',
            'BURRO',
            'VACA',
            'MULA',
            'ANIMAL',
            'RETROEXCAVADORA',
            'EXCAVADORA',
            'CARGADOR FRONTAL',
            'MOTOCONFORMADORA',
            'BULLDOZER',
            'RODILLO',
            'MONTACARGAS',
            'TRACTOR AGRICOLA',
            'PAVIMENTADORA',
            'COMPACTADORA',
        ]) || $this->esRemolqueTipo($tipo);
    }

    private function tipoVehiculoNoEspecificado(string $tipo): bool
    {
        return $tipo === '' || in_array($tipo, [
            'N/A',
            'NA',
            'NO',
            'NO APLICA',
            'NULL',
            'SIN DATO',
            'SIN TIPO',
        ], true);
    }

    private function contieneAlguno(string $texto, array $agujas): bool
    {
        foreach ($agujas as $aguja) {
            if ($aguja !== '' && strpos($texto, $aguja) !== false) {
                return true;
            }
        }

        return false;
    }

    private function normalizarTextoComparacion($texto): string
    {
        $texto = mb_strtoupper(trim((string) $texto), 'UTF-8');

        return strtr($texto, [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ü' => 'U',
            'Ñ' => 'N',
        ]);
    }

}
