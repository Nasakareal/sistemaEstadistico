<?php

namespace App\Services;

use App\Models\Hechos;
use App\Models\Personal;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExcelNovedadesGenerator
{
    private const UNIDAD_SINIESTROS_ID = 1;

    protected EstadoFuerzaService $estadoService;
    protected HechoNovedadesFormatter $hechoFormatter;

    public function __construct(EstadoFuerzaService $estadoService, HechoNovedadesFormatter $hechoFormatter)
    {
        $this->estadoService = $estadoService;
        $this->hechoFormatter = $hechoFormatter;
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

        $hechos = $this->hechosDelCorte($corte);
        $this->llenarTotalSiniestros($spreadsheet->getSheetByName('TOTAL'), $hechos, $corte);
        $this->llenarNovedadesRelevantes($spreadsheet->getSheetByName('NOV. REL'), $hechos);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->setPreCalculateFormulas(false);
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
            ->where('unidad_id', self::UNIDAD_SINIESTROS_ID)
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

    protected function hechosDelCorte(Carbon $corte)
    {
        $tz = 'America/Mexico_City';
        $fin = $corte->copy()->timezone($tz)->setTime(18, 0, 0);
        $inicio = $fin->copy()->subDay();

        return Hechos::with([
            'vehiculos.conductores',
            'vehiculos.servicios.grua',
            'lesionados',
        ])
            ->where('unidad_org_id', self::UNIDAD_SINIESTROS_ID)
            ->whereBetween('created_at', [$inicio, $fin])
            ->orderBy('fecha', 'asc')
            ->orderBy('hora', 'asc')
            ->get();
    }

    protected function llenarTotalSiniestros(?Worksheet $sheet, $hechos, Carbon $corte): void
    {
        if (!$sheet) {
            return;
        }

        $sheet->setCellValue('C2', $corte->format('d/m/Y'));

        $resueltos = 0;
        $pendientes = 0;
        $turnados = 0;
        $tipoRows = $this->mapaFilasTiposHecho();
        $tipoConteos = [];
        $lesionados = 0;
        $fallecidos = 0;
        $danosVehiculos = 0.0;
        $danosPatrimoniales = 0.0;

        foreach ($tipoRows as $fila) {
            $tipoConteos[$fila] = [
                'cantidad' => 0,
                'lesionados' => 0,
                'fallecidos' => 0,
            ];
        }

        foreach ($hechos as $hecho) {
            $situacion = $this->normalizar((string) ($hecho->situacion ?? ''));

            if ($situacion === 'RESUELTO') {
                $resueltos++;
            } elseif ($situacion === 'PENDIENTE') {
                $pendientes++;
            } elseif ($situacion === 'TURNADO') {
                $turnados++;
            }

            $victimas = $this->hechoFormatter->contarVictimas($hecho);
            $lesionados += $victimas['lesionados'];
            $fallecidos += $victimas['fallecidos'];
            $danosVehiculos += $this->hechoFormatter->montoDanosVehiculos($hecho);
            $danosPatrimoniales += $this->hechoFormatter->montoDanosPatrimoniales($hecho);

            $fila = $this->filaTipoHecho((string) ($hecho->tipo_hecho ?? ''));

            if ($fila) {
                $tipoConteos[$fila]['cantidad']++;
                $tipoConteos[$fila]['lesionados'] += $victimas['lesionados'];
                $tipoConteos[$fila]['fallecidos'] += $victimas['fallecidos'];
            }
        }

        $sheet->setCellValue('D120', $resueltos);
        $sheet->setCellValue('D121', $pendientes);
        $sheet->setCellValue('D122', $turnados);
        $sheet->setCellValue('D123', $hechos->count());

        foreach ($tipoConteos as $fila => $conteos) {
            $sheet->setCellValue("D{$fila}", $conteos['cantidad']);
            $sheet->setCellValue("E{$fila}", $conteos['lesionados']);
            $sheet->setCellValue("G{$fila}", $conteos['fallecidos']);
        }

        $sheet->setCellValue('D143', $hechos->count());
        $sheet->setCellValue('E143', $lesionados);
        $sheet->setCellValue('G143', $fallecidos);

        $totalDanos = $danosVehiculos + $danosPatrimoniales;
        $sheet->setCellValue('H146', $totalDanos);
        $sheet->setCellValue('H147', $danosVehiculos);
        $sheet->setCellValue('H148', $danosPatrimoniales);
        $sheet->setCellValue('H149', $totalDanos);
    }

    protected function llenarNovedadesRelevantes(?Worksheet $sheet, $hechos): void
    {
        if (!$sheet) {
            return;
        }

        $highestRow = max(13, $sheet->getHighestRow());

        for ($row = 2; $row <= $highestRow; $row++) {
            foreach (range('A', 'I') as $column) {
                $sheet->setCellValue($column . $row, null);
            }
        }

        $row = 2;

        foreach ($hechos as $index => $hecho) {
            $sheet->setCellValue("A{$row}", $index + 1);
            $sheet->setCellValue("B{$row}", $this->horaTexto($hecho->hora ?? null));
            $sheet->setCellValue("C{$row}", $this->ubicacionTexto($hecho));
            $sheet->setCellValue("D{$row}", $this->hechoFormatter->descripcionHecho($hecho));
            $sheet->setCellValue("E{$row}", $this->resolucionTexto($hecho));
            $sheet->setCellValue("F{$row}", $this->hechoFormatter->vehiculosTexto($hecho));
            $row++;
        }
    }

    protected function mapaFilasTiposHecho(): array
    {
        return [
            'EXPLOSION' => 126,
            'INCENDIO' => 127,
            'DESBARRANCAMIENTO' => 128,
            'VOLCADURA' => 129,
            'SALIDADESUPERFICIEDERODAMIENTO' => 130,
            'SUBIDAALCAMELLON' => 131,
            'CAIDADEMOTOCICLETA' => 132,
            'COLISIONCONTRAOBJETOFIJO' => 133,
            'COLISIONPORALCANCE' => 134,
            'COLISIONPORNORESPETARSEMAFORO' => 135,
            'COLISIONPORINVASIONDECARRIL' => 136,
            'COLISIONPORCAMBIODECARRIL' => 137,
            'COLISIONPORCORTEDECIRCULACION' => 138,
            'COLISIONPORMANIOBRADEREVERSA' => 139,
            'CAIDAALACUNETA' => 140,
            'CAIDAACUATICADEVEHICULO' => 141,
            'COLISIONCONPEATON' => 142,
        ];
    }

    protected function filaTipoHecho(string $tipo): ?int
    {
        $mapa = $this->mapaFilasTiposHecho();
        $normalizado = $this->normalizar($tipo);

        return $mapa[$normalizado] ?? null;
    }

    protected function resolucionTexto($hecho): string
    {
        $partes = [];
        $situacion = trim((string) ($hecho->situacion ?? ''));

        if ($situacion !== '') {
            $partes[] = mb_strtoupper($situacion, 'UTF-8');
        }

        $monto = $this->hechoFormatter->montoDanos($hecho);

        if ($monto > 0) {
            $partes[] = 'DAÑOS APROXIMADOS $ ' . number_format($monto, 2, '.', ',');
        }

        return implode(' | ', $partes);
    }

    protected function ubicacionTexto($hecho): string
    {
        $partes = array_filter([
            trim((string) ($hecho->calle ?? '')),
            trim((string) ($hecho->colonia ?? '')),
            trim((string) ($hecho->municipio ?? '')),
        ]);

        return mb_strtoupper(implode(', ', $partes), 'UTF-8');
    }

    protected function horaTexto($hora): string
    {
        if ($hora instanceof \DateTimeInterface) {
            return $hora->format('H:i');
        }

        $hora = trim((string) $hora);

        return $hora !== '' ? substr($hora, 0, 5) : '';
    }

    protected function normalizar(string $value): string
    {
        return $this->hechoFormatter->normalizar($value);
    }
}
