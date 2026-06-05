<?php

namespace App\Services\VialidadesUrbanas\Hojas;

use App\Models\Personal;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EstadoFuerzaSheetService
{
    private const UNIDAD_VIALIDADES_URBANAS_ID = 5;
    private const TZ = 'America/Mexico_City';
    private const TURNOS_VIALIDADES_URBANAS = [
        'A',
        'B',
        'ADMINISTRATIVO L-V',
        'LICENCIAS L-V',
        'JORNADA ACUMULADA S-D',
        'SUBDIRECTOR',
        'INSTRUCTOR',
    ];

    public function generar(Worksheet $sheet, string $fecha, Carbon $inicio, Carbon $fin): void
    {
        $navy = '002060';
        $lightBlue = 'BDD7EE';
        $outsideGray = 'A6A6A6';
        $doubleBorder = [
            'borderStyle' => Border::BORDER_DOUBLE,
            'color' => ['rgb' => '000000'],
        ];

        $styleTitle = [
            'font' => [
                'bold' => false,
                'size' => 20,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $navy],
            ],
            'borders' => [
                'allBorders' => $doubleBorder,
            ],
        ];

        $styleFechaBar = [
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $navy],
            ],
            'borders' => [
                'allBorders' => $doubleBorder,
            ],
        ];

        $styleHeader = [
            'font' => [
                'italic' => true,
                'bold' => true,
                'size' => 10,
                'color' => ['rgb' => '000000'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFFFF'],
            ],
            'borders' => [
                'allBorders' => $doubleBorder,
            ],
        ];

        $styleBody = [
            'font' => [
                'bold' => false,
                'size' => 11,
                'color' => ['rgb' => '000000'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $lightBlue],
            ],
            'borders' => [
                'allBorders' => $doubleBorder,
            ],
        ];

        $styleTotalCell = $styleBody;
        $styleTotalCell['font']['bold'] = true;
        $styleTotalCell['font']['size'] = 16;

        $sheet->getStyle('A1:N7')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $outsideGray],
            ],
        ]);

        $sheet->setCellValue('B2', 'FECHA');
        $sheet->setCellValue('C2', Carbon::parse($fecha)->format('d/m/Y'));
        $sheet->mergeCells('D2:M2');
        $sheet->setCellValue('D2', 'ESTADO DE FUERZA DE PERSONAL');

        $sheet->getStyle('B2:C2')->applyFromArray($styleFechaBar);
        $sheet->getStyle('D2:M2')->applyFromArray($styleTitle);
        $sheet->getRowDimension(2)->setRowHeight(34);

        $headers = [
            'B' => 'UNIDAD',
            'C' => 'CATEGORIA',
            'D' => 'PRESENTES',
            'E' => 'FRANCOS',
            'F' => 'FALTANDO',
            'G' => 'CURSOS',
            'H' => 'VACACIONES',
            'I' => 'COMISIONADOS',
            'J' => 'INCAPACIDAD',
            'K' => 'PERMISO',
            'L' => 'OTROS',
            'M' => "TOTAL, POR\nAGRUPAMIENTO",
        ];

        foreach ($headers as $column => $label) {
            $sheet->setCellValue($column . '3', $label);
        }

        $sheet->getStyle('B3:M3')->applyFromArray($styleHeader);
        $sheet->getStyle('D3:L3')->getAlignment()->setTextRotation(90);
        $sheet->getStyle('B3')->getFont()->setItalic(false);
        $sheet->getRowDimension(3)->setRowHeight(96);

        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(16);
        $sheet->getColumnDimension('C')->setWidth(18);

        foreach (range('D', 'L') as $column) {
            $sheet->getColumnDimension($column)->setWidth(12);
        }

        $sheet->getColumnDimension('M')->setWidth(20);

        $conteos = $this->conteosPorCategoriaFija($this->personalVialidadesUrbanas($fin), $fin);
        $filas = [
            4 => ['label' => 'OPERATIVOS', 'conteos' => $conteos['OPERATIVOS']],
            5 => ['label' => 'ADMINISTRATIVOS', 'conteos' => $conteos['ADMINISTRATIVOS']],
        ];

        $sheet->mergeCells('B4:B5');
        $sheet->setCellValue('B4', "VIALIDADES\nURBANAS");

        foreach ($filas as $row => $fila) {
            $sheet->setCellValue("C{$row}", $fila['label']);
            $sheet->setCellValue("D{$row}", $fila['conteos']['PRESENTES']);
            $sheet->setCellValue("E{$row}", $fila['conteos']['FRANCOS']);
            $sheet->setCellValue("F{$row}", $fila['conteos']['FALTANDO']);
            $sheet->setCellValue("G{$row}", $fila['conteos']['CURSOS']);
            $sheet->setCellValue("H{$row}", $fila['conteos']['VACACIONES']);
            $sheet->setCellValue("I{$row}", $fila['conteos']['COMISIONADOS']);
            $sheet->setCellValue("J{$row}", $fila['conteos']['INCAPACIDAD']);
            $sheet->setCellValue("K{$row}", $fila['conteos']['PERMISO']);
            $sheet->setCellValue("L{$row}", $fila['conteos']['OTROS']);
            $sheet->setCellValue("M{$row}", array_sum($fila['conteos']));
            $sheet->getRowDimension($row)->setRowHeight(24);
        }

        $sheet->getStyle('B4:M5')->applyFromArray($styleBody);
        $sheet->getStyle('B4')->getFont()->setBold(true);
        $sheet->getStyle('M4:M5')->applyFromArray($styleTotalCell);
        $sheet->getStyle('B2:M5')->applyFromArray([
            'borders' => [
                'outline' => $doubleBorder,
            ],
        ]);
        $sheet->getStyle('B2:M5')
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);
    }

    private function personalVialidadesUrbanas(Carbon $fin): Collection
    {
        return Personal::query()
            ->with(['unidad', 'turno', 'patrulla', 'incidencias.tipo'])
            ->where('unidad_id', self::UNIDAD_VIALIDADES_URBANAS_ID)
            ->whereRaw("UPPER(TRIM(COALESCE(estatus, ''))) = ?", ['ACTIVO'])
            ->where(function ($query) use ($fin) {
                $query->whereNull('fecha_baja')
                    ->orWhereDate('fecha_baja', '>', $fin->toDateString());
            })
            ->whereHas('turno', function ($query) {
                $query->whereRaw(
                    'UPPER(TRIM(nombre)) IN (' . implode(',', array_fill(0, count(self::TURNOS_VIALIDADES_URBANAS), '?')) . ')',
                    self::TURNOS_VIALIDADES_URBANAS
                );
            })
            ->orderBy('grado')
            ->orderBy('ap_paterno')
            ->orderBy('ap_materno')
            ->orderBy('nombre')
            ->get();
    }

    private function conteosPorCategoriaFija(Collection $personal, Carbon $corte): array
    {
        $conteos = [
            'OPERATIVOS' => $this->conteosVacios(),
            'ADMINISTRATIVOS' => $this->conteosVacios(),
        ];

        foreach ($personal as $elemento) {
            $categoria = $this->categoriaEstadoFuerza($elemento->categoria ?? null);
            $estado = $this->estadoVialidadesUrbanas($elemento, $corte);

            switch ($estado) {
                case 'EN_SERVICIO':
                    $conteos[$categoria]['PRESENTES']++;
                    break;
                case 'FRANCO':
                    $conteos[$categoria]['FRANCOS']++;
                    break;
                case 'FALTANDO':
                    $conteos[$categoria]['FALTANDO']++;
                    break;
                case 'CURSOS':
                    $conteos[$categoria]['CURSOS']++;
                    break;
                case 'VACACIONES':
                    $conteos[$categoria]['VACACIONES']++;
                    break;
                case 'COMISIONADOS':
                    $conteos[$categoria]['COMISIONADOS']++;
                    break;
                case 'INCAPACIDAD':
                    $conteos[$categoria]['INCAPACIDAD']++;
                    break;
                case 'PERMISO':
                    $conteos[$categoria]['PERMISO']++;
                    break;
                default:
                    $conteos[$categoria]['OTROS']++;
                    break;
            }
        }

        return $conteos;
    }

    private function conteosVacios(): array
    {
        return [
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

    private function categoriaEstadoFuerza($categoria): string
    {
        $categoria = mb_strtoupper(trim((string) $categoria));

        return mb_strpos($categoria, 'ADMIN') !== false ? 'ADMINISTRATIVOS' : 'OPERATIVOS';
    }

    private function estadoVialidadesUrbanas(Personal $personal, Carbon $corte): string
    {
        $estadoIncidencia = $this->estadoPorIncidencia($personal, $corte);

        if ($estadoIncidencia !== null) {
            return $estadoIncidencia;
        }

        $turno = $personal->turno;

        if (!$turno) {
            return 'OTROS';
        }

        $nombreTurno = $this->normalizar($turno->nombre ?? '');
        $tipoRol = $this->normalizar($turno->tipo_rol ?? '');
        $slugTurno = $this->normalizar($turno->slug ?? '');

        if ($nombreTurno === 'A' || $slugTurno === 'A') {
            return $this->turnoActivo24x24($corte) === 'A' ? 'EN_SERVICIO' : 'FRANCO';
        }

        if ($nombreTurno === 'B' || $slugTurno === 'B') {
            return $this->turnoActivo24x24($corte) === 'B' ? 'EN_SERVICIO' : 'FRANCO';
        }

        if ($tipoRol === 'SAB_DOM' || str_contains($nombreTurno, 'JORNADA ACUMULADA')) {
            $diaSemana = (int) $corte->copy()->timezone(self::TZ)->dayOfWeekIso;

            return ($diaSemana === 6 || $diaSemana === 7) ? 'EN_SERVICIO' : 'FRANCO';
        }

        if ($tipoRol === 'LUN_VIE') {
            $diaSemana = (int) $corte->copy()->timezone(self::TZ)->dayOfWeekIso;

            return ($diaSemana >= 1 && $diaSemana <= 5) ? 'EN_SERVICIO' : 'FRANCO';
        }

        if ($tipoRol === 'SIEMPRE' || str_contains($nombreTurno, 'SUBDIRECTOR')) {
            return 'EN_SERVICIO';
        }

        return 'OTROS';
    }

    private function estadoPorIncidencia(Personal $personal, Carbon $corte): ?string
    {
        $incidencias = $personal->incidencias ?? collect();
        $corteDia = $corte->copy()->timezone(self::TZ);

        $incidencia = $incidencias->first(function ($incidencia) use ($corteDia) {
            if ((bool) ($incidencia->activo ?? true) === false || !$incidencia->fecha_inicio) {
                return false;
            }

            $inicio = Carbon::parse($incidencia->fecha_inicio, self::TZ)->startOfDay();
            $fin = $incidencia->fecha_fin
                ? Carbon::parse($incidencia->fecha_fin, self::TZ)->endOfDay()
                : null;

            if ($fin) {
                return $corteDia->between($inicio, $fin);
            }

            return $corteDia->greaterThanOrEqualTo($inicio);
        });

        if (!$incidencia) {
            return null;
        }

        $tipo = $incidencia->tipo ?? null;
        $tipoNombre = is_object($tipo)
            ? $this->normalizar($tipo->clave ?? $tipo->nombre ?? '')
            : $this->normalizar($tipo ?? '');

        if ($tipoNombre === 'SERVICIO') {
            return 'EN_SERVICIO';
        }

        if ($tipoNombre === 'COMISION' || $tipoNombre === 'COMISIÓN') {
            return 'COMISIONADOS';
        }

        if ($tipoNombre === 'VACACIONES') {
            return 'VACACIONES';
        }

        if ($tipoNombre === 'INCAPACIDAD') {
            return 'INCAPACIDAD';
        }

        if ($tipoNombre === 'PERMISO') {
            return 'PERMISO';
        }

        if ($tipoNombre === 'FALTA') {
            return 'FALTANDO';
        }

        return 'OTROS';
    }

    private function turnoActivo24x24(Carbon $corte): string
    {
        $fechaReferencia = (string) config('cortes.vialidades_urbanas_turno_b_fecha_referencia', '2026-06-04');
        $referenciaB = Carbon::parse($fechaReferencia, self::TZ)->startOfDay();
        $fechaCorte = $corte->copy()->timezone(self::TZ)->startOfDay();
        $dias = $referenciaB->diffInDays($fechaCorte, false);

        return $dias % 2 === 0 ? 'B' : 'A';
    }

    private function normalizar($valor): string
    {
        return mb_strtoupper(trim((string) $valor), 'UTF-8');
    }
}
