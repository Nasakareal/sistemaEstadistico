<?php

namespace App\Services;

use App\Models\Personal;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExcelNovedadesGenerator
{
    protected EstadoFuerzaService $estadoService;

    public function __construct(EstadoFuerzaService $estadoService)
    {
        $this->estadoService = $estadoService;
    }

    public function generar(Carbon $corte): string
    {
        $plantilla = public_path('templates/excel_novedades.xlsx');
        $directorioSalida = storage_path('app/cortes/excel_novedades');
        $nombreArchivo = 'excel_novedades_' . $corte->format('Y-m-d') . '.xlsx';
        $rutaSalida = $directorioSalida . DIRECTORY_SEPARATOR . $nombreArchivo;

        if (!file_exists($plantilla)) {
            throw new \RuntimeException('No existe la plantilla excel_novedades.xlsx en public/templates.');
        }

        if (!File::exists($directorioSalida)) {
            File::makeDirectory($directorioSalida, 0755, true);
        }

        $spreadsheet = IOFactory::load($plantilla);

        $this->llenarEstadoFuerza($spreadsheet->getSheetByName('EST. FUR') ?: $spreadsheet->getSheet(0), $corte);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($rutaSalida);

        return $rutaSalida;
    }

    protected function llenarEstadoFuerza(Worksheet $sheet, Carbon $corte): void
    {
        $sheet->setCellValue('C2', $corte->format('d/m/Y'));

        $colUnidad       = 'B';
        $colCategoria    = 'C';
        $colPresentes    = 'D';
        $colFrancos      = 'E';
        $colFaltando     = 'F';
        $colCursos       = 'G';
        $colVacaciones   = 'H';
        $colComisionados = 'I';
        $colIncapacidad  = 'J';
        $colPermiso      = 'K';
        $colOtros        = 'L';
        $colTotal        = 'M';

        $filaInicioDatos = 4;
        $filasPlantillaDatos = 2;

        $personales = Personal::with(['turno', 'incidencias', 'unidad'])
            ->where('estatus', 'ACTIVO')
            ->get();

        $agrupado = [];

        foreach ($personales as $personal) {
            $estado = $this->estadoService->estado($personal, $corte);

            $unidad = 'SIN_UNIDAD';
            if ($personal->unidad) {
                $unidad = (string)($personal->unidad->nombre ?? $personal->unidad->name ?? 'SIN_UNIDAD');
            }

            $categoria = (string)($personal->categoria ?? 'SIN_CATEGORIA');

            if (!isset($agrupado[$unidad][$categoria])) {
                $agrupado[$unidad][$categoria] = [
                    'PRESENTES' => 0,
                    'FRANCOS' => 0,
                    'FALTANDO' => 0,
                    'CURSOS' => 0,
                    'VACACIONES' => 0,
                    'COMISIONADOS' => 0,
                    'INCAPACIDAD' => 0,
                    'PERMISO' => 0,
                    'OTROS' => 0,
                ];
            }

            switch ($estado) {
                case 'EN_SERVICIO':
                    $agrupado[$unidad][$categoria]['PRESENTES']++;
                    break;
                case 'FRANCO':
                    $agrupado[$unidad][$categoria]['FRANCOS']++;
                    break;
                case 'FALTANDO':
                    $agrupado[$unidad][$categoria]['FALTANDO']++;
                    break;
                case 'CURSOS':
                    $agrupado[$unidad][$categoria]['CURSOS']++;
                    break;
                case 'VACACIONES':
                    $agrupado[$unidad][$categoria]['VACACIONES']++;
                    break;
                case 'COMISIONADOS':
                    $agrupado[$unidad][$categoria]['COMISIONADOS']++;
                    break;
                case 'INCAPACIDAD':
                    $agrupado[$unidad][$categoria]['INCAPACIDAD']++;
                    break;
                case 'PERMISO':
                    $agrupado[$unidad][$categoria]['PERMISO']++;
                    break;
                default:
                    $agrupado[$unidad][$categoria]['OTROS']++;
                    break;
            }
        }

        ksort($agrupado);
        foreach ($agrupado as $unidad => $categorias) {
            ksort($agrupado[$unidad]);
        }

        $totalFilasNecesarias = 0;
        foreach ($agrupado as $categorias) {
            $totalFilasNecesarias += count($categorias);
        }

        if ($totalFilasNecesarias < 1) {
            $totalFilasNecesarias = 1;
        }

        if ($totalFilasNecesarias > $filasPlantillaDatos) {
            $filasExtra = $totalFilasNecesarias - $filasPlantillaDatos;
            $sheet->insertNewRowBefore($filaInicioDatos + $filasPlantillaDatos, $filasExtra);

            for ($i = 0; $i < $filasExtra; $i++) {
                $filaOrigen = $filaInicioDatos + 1;
                $filaDestino = $filaInicioDatos + $filasPlantillaDatos + $i;

                foreach (range('B', 'M') as $columna) {
                    $sheet->duplicateStyle($sheet->getStyle($columna . $filaOrigen), $columna . $filaDestino);
                    $sheet->setCellValue($columna . $filaDestino, null);
                }

                $sheet->getRowDimension($filaDestino)->setRowHeight($sheet->getRowDimension($filaOrigen)->getRowHeight());
            }
        }

        $ultimaFilaPlantilla = $filaInicioDatos + max($filasPlantillaDatos, $totalFilasNecesarias) - 1;
        for ($fila = $filaInicioDatos; $fila <= $ultimaFilaPlantilla; $fila++) {
            foreach (range('B', 'M') as $columna) {
                $sheet->setCellValue($columna . $fila, null);
            }
        }

        $filaActual = $filaInicioDatos;

        foreach ($agrupado as $unidad => $categorias) {
            $filaUnidadInicio = $filaActual;
            $filaUnidadFin = $filaActual + count($categorias) - 1;

            if ($filaUnidadFin > $filaUnidadInicio) {
                $sheet->mergeCells("{$colUnidad}{$filaUnidadInicio}:{$colUnidad}{$filaUnidadFin}");
            }

            $sheet->setCellValue("{$colUnidad}{$filaUnidadInicio}", $unidad);

            foreach ($categorias as $categoria => $conteos) {
                $sheet->setCellValue("{$colCategoria}{$filaActual}", $categoria);
                $sheet->setCellValue("{$colPresentes}{$filaActual}", $conteos['PRESENTES']);
                $sheet->setCellValue("{$colFrancos}{$filaActual}", $conteos['FRANCOS']);
                $sheet->setCellValue("{$colFaltando}{$filaActual}", $conteos['FALTANDO']);
                $sheet->setCellValue("{$colCursos}{$filaActual}", $conteos['CURSOS']);
                $sheet->setCellValue("{$colVacaciones}{$filaActual}", $conteos['VACACIONES']);
                $sheet->setCellValue("{$colComisionados}{$filaActual}", $conteos['COMISIONADOS']);
                $sheet->setCellValue("{$colIncapacidad}{$filaActual}", $conteos['INCAPACIDAD']);
                $sheet->setCellValue("{$colPermiso}{$filaActual}", $conteos['PERMISO']);
                $sheet->setCellValue("{$colOtros}{$filaActual}", $conteos['OTROS']);
                $sheet->setCellValue("{$colTotal}{$filaActual}", array_sum($conteos));

                $filaActual++;
            }
        }

        if ($totalFilasNecesarias === 1 && empty($agrupado)) {
            $sheet->setCellValue("{$colUnidad}{$filaInicioDatos}", 'SIN DATOS');
            $sheet->setCellValue("{$colCategoria}{$filaInicioDatos}", 'SIN CATEGORIA');
            $sheet->setCellValue("{$colPresentes}{$filaInicioDatos}", 0);
            $sheet->setCellValue("{$colFrancos}{$filaInicioDatos}", 0);
            $sheet->setCellValue("{$colFaltando}{$filaInicioDatos}", 0);
            $sheet->setCellValue("{$colCursos}{$filaInicioDatos}", 0);
            $sheet->setCellValue("{$colVacaciones}{$filaInicioDatos}", 0);
            $sheet->setCellValue("{$colComisionados}{$filaInicioDatos}", 0);
            $sheet->setCellValue("{$colIncapacidad}{$filaInicioDatos}", 0);
            $sheet->setCellValue("{$colPermiso}{$filaInicioDatos}", 0);
            $sheet->setCellValue("{$colOtros}{$filaInicioDatos}", 0);
            $sheet->setCellValue("{$colTotal}{$filaInicioDatos}", 0);
        }

        $ultimaFilaConDatos = max($filaInicioDatos, $filaActual - 1);

        $sheet->getStyle("B{$filaInicioDatos}:M{$ultimaFilaConDatos}")
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
    }
}
