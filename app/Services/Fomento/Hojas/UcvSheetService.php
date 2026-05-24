<?php

namespace App\Services\Fomento\Hojas;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UcvSheetService extends BaseFomentoSheetService
{
    public function generar(Worksheet $sheet, Collection $actividades, string $fecha, Carbon $inicio, Carbon $fin): void
    {
        $sheet->getSheetView()->setZoomScale(85);
        $this->aplicarFormatoBase($sheet, $fecha);

        $resumen = $this->resumenPorPrograma($actividades);
        $fila = 3;

        foreach ($this->gruposUcv() as $index => $grupo) {
            $programas = $this->programasDelGrupo($grupo);
            $filaInicio = $fila;
            $filaFin = $fila + count($programas) - 1;
            $relleno = $index % 2 === 1 ? '9DC3E6' : 'FFFFFF';

            if ($filaFin > $filaInicio) {
                $sheet->mergeCells('A' . $filaInicio . ':A' . $filaFin);
            }

            $sheet->setCellValue('A' . $filaInicio, $grupo['titulo']);

            foreach ($programas as $programa) {
                $item = $resumen[$this->keyPrograma($programa['id'], $programa['nombre'])] ?? $this->resumenVacio();

                $sheet->setCellValue('B' . $fila, $programa['nombre']);
                $sheet->setCellValue('C' . $fila, $item['municipio']);
                $sheet->setCellValue('D' . $fila, $item['nivel_educativo']);
                $sheet->setCellValue('E' . $fila, $item['sector']);
                $sheet->setCellValue('F' . $fila, $this->numeroVisible($item['realizados']));
                $sheet->setCellValue('G' . $fila, $this->numeroVisible($item['ninas']));
                $sheet->setCellValue('H' . $fila, $this->numeroVisible($item['ninos']));
                $sheet->setCellValue('I' . $fila, $this->numeroVisible($item['adolescentes_mujeres']));
                $sheet->setCellValue('J' . $fila, $this->numeroVisible($item['adolescentes_hombres']));
                $sheet->setCellValue('K' . $fila, $this->numeroVisible($item['mujeres']));
                $sheet->setCellValue('L' . $fila, $this->numeroVisible($item['hombres']));
                $sheet->setCellValue('M' . $fila, (int) $item['personas']);

                $sheet->getRowDimension($fila)->setRowHeight(22);
                $fila++;
            }

            $sheet->getStyle('A' . $filaInicio . ':M' . $filaFin)->applyFromArray($this->estiloBloque($relleno));
            $sheet->getStyle('A' . $filaInicio . ':A' . $filaFin)->getFont()->setBold(true);
            $sheet->getStyle('A' . $filaInicio . ':A' . $filaFin)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('B' . $filaInicio . ':B' . $filaFin)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('F' . $filaInicio . ':M' . $filaFin)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $this->llenarTotales($sheet, $fila);
        $sheet->freezePane('C3');
    }

    private function aplicarFormatoBase(Worksheet $sheet, string $fecha): void
    {
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(66);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(16);
        $sheet->getColumnDimension('F')->setWidth(12);

        foreach (range('G', 'M') as $column) {
            $sheet->getColumnDimension($column)->setWidth(14);
        }

        $sheet->getRowDimension(1)->setRowHeight(18);
        $sheet->getRowDimension(2)->setRowHeight(36);

        $sheet->setCellValue('A1', 'FECHA');
        $sheet->setCellValue('B1', Carbon::parse($fecha)->format('d/m/Y'));
        $sheet->mergeCells('A2:B2');
        $sheet->setCellValue('A2', 'UNIDAD DE FOMENTO A LA CULTURA VIAL');
        $sheet->setCellValue('C2', 'MUNICIPIO');
        $sheet->setCellValue('D2', 'NIVEL EDUCATIVO');
        $sheet->setCellValue('E2', 'SECTOR');
        $sheet->setCellValue('F2', 'REALIZADOS');
        $sheet->setCellValue('G2', 'NIÑAS');
        $sheet->setCellValue('H2', 'NIÑOS');
        $sheet->setCellValue('I2', "ADOLESCENTES\nMUJERES");
        $sheet->setCellValue('J2', "ADOLESCENTES\nHOMBRES");
        $sheet->setCellValue('K2', 'MUJERES');
        $sheet->setCellValue('L2', 'HOMBRES');
        $sheet->setCellValue('M2', "PERSONAS\nALCANZADAS");

        $sheet->getStyle('A1:B1')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A2:M2')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0070C0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->getStyle('A2:M200')->getAlignment()
            ->setWrapText(true)
            ->setShrinkToFit(false);
        $sheet->getStyle('A3:M200')->getFont()->setSize(9);
    }

    private function gruposUcv(): array
    {
        return [
            [
                'titulo' => 'TALLERES',
                'subcategoria' => 'TALLER EDUCACIÓN SEGURIDAD VIAL',
                'fallback' => [
                    'Taller Educación Vial',
                    'Taller de Manejo Defensivo',
                    'Taller de Gestion de Emociones en la Conducción',
                    'Taller de Violencia de Genero',
                    'Taller de movilidad segura en la vía pública',
                    'Taller de Ley de Movilidad y Seguridad Vial del Estado de Michoacán',
                    'Taller de alcohol y conducción',
                    'Taller de proximidad social',
                    'Taller de Promotores Escolares',
                    'Taller de Seguridad Vial Laboral',
                ],
            ],
            [
                'titulo' => 'CAMPAÑAS',
                'subcategoria' => 'CAMPAÑA EDUCACIÓN SEGURIDAD VIAL',
                'fallback' => [
                    'Campaña de Sensibilización',
                    'Prevención de violencia de género en el transporte y en la vía pública',
                    'Infancias seguras en la vía pública',
                    'Primero el Peatón',
                    'Uso del cinturon de Seguridad',
                    'Uso del Casco',
                ],
            ],
            [
                'titulo' => 'CAPACITACIONES',
                'subcategoria' => 'CAPACITACIONES EDUCACIÓN SEGURIDAD VIAL',
                'fallback' => [
                    'Lic. Seguridad Publica, (En UMSNH)',
                    'Capacitaciones para elementos de nuevo ingreso',
                    'Actualizacion para elementos de la Coordinación de Seguridad Vial',
                ],
            ],
            [
                'titulo' => 'Módulos y Stand',
                'subcategoria' => 'MÓDULOS EDUCACIÓN SEGURIDAD VIAL',
                'fallback' => [
                    'Modulo de Lúdico',
                    'Simulacro de hecho de tránsito',
                ],
            ],
        ];
    }

    private function programasDelGrupo(array $grupo): array
    {
        $programas = DB::table('fomento_cultura_vial_programas as p')
            ->join('actividad_subcategorias as s', 's.id', '=', 'p.actividad_subcategoria_id')
            ->where('s.nombre', $grupo['subcategoria'])
            ->where('p.activo', true)
            ->orderBy('p.orden')
            ->orderBy('p.id')
            ->get(['p.id', 'p.nombre'])
            ->map(function ($programa) {
                return [
                    'id' => (int) $programa->id,
                    'nombre' => $programa->nombre,
                ];
            })
            ->values()
            ->all();

        if (empty($programas)) {
            foreach ($grupo['fallback'] as $nombre) {
                $programas[] = [
                    'id' => null,
                    'nombre' => $nombre,
                ];
            }
        }

        return $programas;
    }

    private function resumenPorPrograma(Collection $actividades): array
    {
        $resumen = [];

        foreach ($actividades as $actividad) {
            $key = $this->keyPrograma(
                $actividad->fomento_cultura_vial_programa_id ?? null,
                $actividad->programa_nombre ?? null
            );

            if (!isset($resumen[$key])) {
                $resumen[$key] = $this->resumenVacio();
            }

            $resumen[$key]['realizados']++;
            $resumen[$key]['municipios'][] = $actividad->municipio ?? null;
            $resumen[$key]['niveles'][] = $actividad->nivel_educativo ?? null;
            $resumen[$key]['sectores'][] = $actividad->sector ?? null;
            $resumen[$key]['ninas'] += (int) ($actividad->ninas ?? 0);
            $resumen[$key]['ninos'] += (int) ($actividad->ninos ?? 0);
            $resumen[$key]['adolescentes_mujeres'] += (int) ($actividad->adolescentes_mujeres ?? 0);
            $resumen[$key]['adolescentes_hombres'] += (int) ($actividad->adolescentes_hombres ?? 0);
            $resumen[$key]['mujeres'] += (int) ($actividad->mujeres ?? 0);
            $resumen[$key]['hombres'] += (int) ($actividad->hombres ?? 0);
            $resumen[$key]['personas'] += (int) ($actividad->total_poblacion_atendida ?? 0);
        }

        foreach ($resumen as $key => $item) {
            $resumen[$key]['municipio'] = $this->textoUnico($item['municipios']);
            $resumen[$key]['nivel_educativo'] = $this->textoUnico($item['niveles']);
            $resumen[$key]['sector'] = $this->textoUnico($item['sectores']);
        }

        return $resumen;
    }

    private function resumenVacio(): array
    {
        return [
            'realizados' => 0,
            'municipio' => '',
            'nivel_educativo' => '',
            'sector' => '',
            'municipios' => [],
            'niveles' => [],
            'sectores' => [],
            'ninas' => 0,
            'ninos' => 0,
            'adolescentes_mujeres' => 0,
            'adolescentes_hombres' => 0,
            'mujeres' => 0,
            'hombres' => 0,
            'personas' => 0,
        ];
    }

    private function llenarTotales(Worksheet $sheet, int $fila): void
    {
        $ultimaFilaDatos = max(3, $fila - 1);

        $sheet->mergeCells('A' . $fila . ':E' . $fila);
        $sheet->setCellValue('A' . $fila, 'TOTAL');
        $sheet->setCellValue('F' . $fila, '=SUM(F3:F' . $ultimaFilaDatos . ')');
        $sheet->setCellValue('G' . $fila, '=SUM(G3:G' . $ultimaFilaDatos . ')');
        $sheet->setCellValue('H' . $fila, '=SUM(H3:H' . $ultimaFilaDatos . ')');
        $sheet->setCellValue('I' . $fila, '=SUM(I3:I' . $ultimaFilaDatos . ')');
        $sheet->setCellValue('J' . $fila, '=SUM(J3:J' . $ultimaFilaDatos . ')');
        $sheet->setCellValue('K' . $fila, '=SUM(K3:K' . $ultimaFilaDatos . ')');
        $sheet->setCellValue('L' . $fila, '=SUM(L3:L' . $ultimaFilaDatos . ')');
        $sheet->setCellValue('M' . $fila, '=SUM(M3:M' . $ultimaFilaDatos . ')');

        $sheet->getRowDimension($fila)->setRowHeight(22);
        $sheet->getStyle('A' . $fila . ':M' . $fila)->applyFromArray([
            'font' => ['bold' => true],
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
    }

    private function estiloBloque(string $relleno): array
    {
        return [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $relleno],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
    }

    private function keyPrograma($id, $nombre): string
    {
        $id = (int) $id;

        if ($id > 0) {
            return 'id:' . $id;
        }

        return 'nombre:' . $this->normalizar((string) $nombre);
    }

    private function textoUnico(array $valores): string
    {
        $unicos = [];

        foreach ($valores as $valor) {
            $valor = trim((string) $valor);

            if ($valor === '') {
                continue;
            }

            $unicos[$this->normalizar($valor)] = $valor;
        }

        return implode("\n", array_values($unicos));
    }

    private function normalizar(string $texto): string
    {
        return mb_strtoupper(trim($texto));
    }

    private function numeroVisible($valor)
    {
        return (int) $valor > 0 ? (int) $valor : null;
    }
}
