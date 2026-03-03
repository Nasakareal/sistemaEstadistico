<?php

namespace App\Services\Exports\Sheets;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

use App\Models\Dictamen;

class NovRelSheet
{
    public function build(Spreadsheet $spreadsheet, Carbon $corte): void
    {
        $sheet = $this->createOrGetSheet($spreadsheet, 'NOV. REL.');

        $lastCol = 'I';

        $sheet->setCellValue('A1', 'NOVEDADES RELEVANTES');
        $sheet->setCellValue('A2', 'Corte: ' . $corte->format('Y-m-d H:i:s'));

        $sheet->mergeCells('A1:' . $lastCol . '1');
        $sheet->mergeCells('A2:' . $lastCol . '2');

        $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F4E79'],
            ],
        ]);

        $sheet->getStyle('A2:' . $lastCol . '2')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->getRowDimension(2)->setRowHeight(18);

        $headerRow = 4;

        $sheet->setCellValue('A' . $headerRow, 'N°.');
        $sheet->setCellValue('B' . $headerRow, 'HORA');
        $sheet->setCellValue('C' . $headerRow, 'LUGAR');
        $sheet->setCellValue('D' . $headerRow, 'ASUNTO');
        $sheet->setCellValue('E' . $headerRow, 'RESOLUCIÓN');
        $sheet->setCellValue(
            'F' . $headerRow,
            'VEHÍCULOS TURNADOS, PERSONAS DETENIDAS, VEHÍCULOS RECUPERADOS (CANTIDAD Y DATOS GENERALES)'
        );
        $sheet->setCellValue('G' . $headerRow, 'GRAFICA 1');
        $sheet->setCellValue('H' . $headerRow, 'GRAFICA 2');
        $sheet->setCellValue('I' . $headerRow, 'GRAFICA 3');

        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(9);
        $sheet->getColumnDimension('C')->setWidth(28);
        $sheet->getColumnDimension('D')->setWidth(55);
        $sheet->getColumnDimension('E')->setWidth(45);
        $sheet->getColumnDimension('F')->setWidth(55);
        $sheet->getColumnDimension('G')->setWidth(18);
        $sheet->getColumnDimension('H')->setWidth(18);
        $sheet->getColumnDimension('I')->setWidth(18);

        $sheet->getRowDimension($headerRow)->setRowHeight(42);

        $sheet->getStyle('A' . $headerRow . ':' . $lastCol . $headerRow)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F4E79'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '1F4E79'],
                ],
            ],
        ]);

        // ===== Dictámenes del día (según $corte) =====
        $dayStart = $corte->copy()->startOfDay();
        $dayEnd   = $corte->copy()->endOfDay();

        // IMPORTANTe: aquí ya cargamos TODO lo que ocupa la sheet, sin relación "conductor"
        $dictamenes = Dictamen::query()
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->whereNotNull('hecho_id')
            ->with([
                'hecho.vehiculos.conductores', // <- la relación real
            ])
            ->orderBy('created_at', 'asc')
            ->get();

        $row = $headerRow + 1;
        $n = 1;

        if ($dictamenes->isEmpty()) {
            $this->applyRowBaseStyle($sheet, $row, $lastCol, 60);
            $sheet->setCellValue('A' . $row, '—');
            $sheet->setCellValue('D' . $row, 'Sin dictámenes en la fecha solicitada.');
            $sheet->mergeCells('D' . $row . ':' . $lastCol . $row);
            $sheet->freezePane('A5');
            $sheet->getPageSetup()->setFitToWidth(1);
            $sheet->getPageSetup()->setFitToHeight(0);
            return;
        }

        foreach ($dictamenes as $dictamen) {

            $hecho = $dictamen->hecho; // ya viene eager loaded
            if (!$hecho) {
                continue;
            }

            $vehiculos = $hecho->vehiculos ?? collect();

            $hora       = $this->pickHora($hecho);
            $lugar      = $this->buildLugar($hecho);
            $asunto     = $this->buildAsunto($hecho, $vehiculos);
            $resolucion = $this->buildResolucion($hecho, $dictamen);
            $turnados   = $this->buildTurnados($vehiculos);

            $this->applyRowBaseStyle($sheet, $row, $lastCol, 150);

            $sheet->setCellValue('A' . $row, (string)$n);
            $sheet->setCellValue('B' . $row, $hora);
            $sheet->setCellValue('C' . $row, $lugar);
            $sheet->setCellValue('D' . $row, $asunto);
            $sheet->setCellValue('E' . $row, $resolucion);
            $sheet->setCellValue('F' . $row, $turnados);

            // Grafica 1: intento con archivo_dictamen si es imagen (normalmente PDF)
            $this->insertImageIfExists($sheet, $dictamen->archivo_dictamen ?? null, 'G', $row);

            $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_TOP,
                    'wrapText'   => true,
                ],
            ]);

            $row++;
            $n++;
        }

        $sheet->freezePane('A5');
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
    }

    protected function applyRowBaseStyle(Worksheet $sheet, int $row, string $lastCol, int $rowHeight): void
    {
        $sheet->getRowDimension($row)->setRowHeight($rowHeight);

        $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical'   => Alignment::VERTICAL_TOP,
                'wrapText'   => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'A6A6A6'],
                ],
            ],
        ]);
    }

    protected function pickHora($hecho): string
    {
        if (!empty($hecho->hora)) {
            return (string)$hecho->hora;
        }

        if (!empty($hecho->created_at)) {
            try {
                return Carbon::parse($hecho->created_at)->format('H:i');
            } catch (\Throwable $e) {
            }
        }

        return '';
    }

    protected function buildLugar($hecho): string
    {
        // Primero lo más “bonito” si existe
        foreach (['ubicacion_formateada', 'lugar', 'ubicacion', 'direccion', 'location'] as $k) {
            if (!empty($hecho->{$k})) {
                return trim((string)$hecho->{$k});
            }
        }

        $parts = [];

        foreach (['calle', 'colonia', 'entre_calles', 'municipio'] as $k) {
            if (!empty($hecho->{$k})) {
                $parts[] = trim((string)$hecho->{$k});
            }
        }

        $out = trim(implode(', ', array_filter($parts)));
        return $out !== '' ? $out : '';
    }

    protected function buildAsunto($hecho, $vehiculos): string
    {
        $tipo = '';
        foreach (['tipo_hecho', 'clasificacion', 'tipo'] as $k) {
            if (!empty($hecho->{$k})) {
                $tipo = (string)$hecho->{$k};
                break;
            }
        }

        $head = 'Se informa que, que se atiende hecho de transito';
        if ($tipo !== '') {
            $head .= ' clasificado como ' . strtoupper($tipo);
        }

        // En tu modelo no hay "lesionados" boolean; lo dejamos como fallback “sin lesionados”
        $les = ' sin personas lesionadas';

        $lugar = $this->buildLugar($hecho);
        if ($lugar !== '') {
            $head .= $les . ', sobre ' . $lugar . ', donde participan:' . "\n";
        } else {
            $head .= $les . ', donde participan:' . "\n";
        }

        $body = [];
        $idx = 0;

        foreach ($vehiculos as $v) {
            $idx++;
            $letter = chr(64 + $idx); // A, B, C...

            $marca  = $v->marca ?? '';
            $modelo = $v->modelo ?? '';
            $tipoV  = $v->tipo ?? '';
            $linea  = $v->linea ?? '';
            $color  = $v->color ?? '';
            $placas = $v->placas ?? '';
            $serie  = $v->serie ?? '';

            $txt = "VEHÍCULO ({$letter})";
            if ($marca !== '')  $txt .= ' Marca ' . $marca . ',';
            if ($modelo !== '') $txt .= ' Modelo ' . $modelo . ',';
            if ($tipoV !== '')  $txt .= ' Tipo ' . $tipoV . ',';
            if ($linea !== '')  $txt .= ' Línea ' . $linea . ',';
            if ($color !== '')  $txt .= ' Color ' . $color . ',';
            if ($placas !== '') $txt .= ' Placas ' . $placas . ',';
            if ($serie !== '')  $txt .= ' Serie ' . $serie . ',';

            $txt = rtrim($txt, ',');

            // ✅ TU RELACIÓN REAL: conductores()
            // Tomamos el primero si hay (puedes cambiarlo si quieres listar todos)
            $c = null;
            if (isset($v->conductores) && $v->conductores && $v->conductores->count() > 0) {
                $c = $v->conductores->first();
            }

            if ($c) {
                // No me diste el modelo Conductor, así que lo hago tolerante:
                $nombre = $c->nombre_completo
                    ?? $c->nombre
                    ?? trim(implode(' ', array_filter([
                        $c->nombre ?? null,
                        $c->apellido_paterno ?? null,
                        $c->apellido_materno ?? null,
                    ])))
                    ?? '';

                $edad = $c->edad ?? '';
                $dom  = $c->domicilio ?? $c->direccion ?? '';
                $lic  = $c->licencia ?? $c->numero_licencia ?? '';

                if ($nombre !== '') $txt .= ', conducido por ' . $nombre;
                if ($edad !== '')   $txt .= ' de ' . $edad . ' años';
                if ($dom !== '')    $txt .= ', con domicilio en ' . $dom;
                if ($lic !== '')    $txt .= ', licencia ' . $lic;
            }

            $body[] = $txt;
        }

        return $head . implode("; \n", $body);
    }

    protected function buildResolucion($hecho, $dictamen): string
    {
        foreach (['resolucion', 'resultado', 'observaciones_resolucion'] as $k) {
            if (!empty($hecho->{$k})) {
                return trim((string)$hecho->{$k});
            }
        }

        $area = $dictamen->area ?? '';
        if ($area !== '') {
            return 'Dictamen generado (' . $area . ').';
        }

        return 'Dictamen generado.';
    }

    protected function buildTurnados($vehiculos): string
    {
        $items = [];
        foreach ($vehiculos as $v) {
            $marca  = $v->marca ?? '';
            $modelo = $v->modelo ?? '';
            $tipoV  = $v->tipo ?? '';
            $linea  = $v->linea ?? '';
            $color  = $v->color ?? '';
            $placas = $v->placas ?? '';
            $serie  = $v->serie ?? '';

            $txt = '';
            if ($marca !== '')  $txt .= 'Marca ' . $marca . ', ';
            if ($modelo !== '') $txt .= 'Modelo ' . $modelo . ', ';
            if ($tipoV !== '')  $txt .= 'Tipo ' . $tipoV . ', ';
            if ($linea !== '')  $txt .= 'Línea ' . $linea . ', ';
            if ($color !== '')  $txt .= 'Color ' . $color . ', ';
            if ($placas !== '') $txt .= 'Placas ' . $placas . ', ';
            if ($serie !== '')  $txt .= 'Serie ' . $serie . ', ';

            $txt = rtrim(trim($txt), ',');
            if ($txt !== '') {
                $items[] = $txt;
            }
        }

        $count = count($items);
        if ($count === 0) {
            return '';
        }

        return $count . ' vehiculos turnados: ' . implode('; ', $items);
    }

    protected function insertImageIfExists(Worksheet $sheet, ?string $relativePath, string $col, int $row): void
    {
        if (!$relativePath) {
            return;
        }

        $path = $this->resolveStoragePath($relativePath);
        if (!$path || !is_file($path)) {
            return;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return;
        }

        $drawing = new Drawing();
        $drawing->setPath($path);
        $drawing->setCoordinates($col . $row);
        $drawing->setOffsetX(5);
        $drawing->setOffsetY(5);
        $drawing->setWidth(120);
        $drawing->setHeight(120);
        $drawing->setWorksheet($sheet);
    }

    protected function resolveStoragePath(string $relativePath): ?string
    {
        $relativePath = ltrim($relativePath, '/');

        $p1 = storage_path('app/public/' . $relativePath);
        if (is_file($p1)) return $p1;

        $p2 = storage_path('app/' . $relativePath);
        if (is_file($p2)) return $p2;

        $p3 = public_path($relativePath);
        if (is_file($p3)) return $p3;

        return null;
    }

    protected function createOrGetSheet(Spreadsheet $spreadsheet, string $title): Worksheet
    {
        foreach ($spreadsheet->getAllSheets() as $s) {
            if ($s->getTitle() === $title) {
                return $s;
            }
        }

        $sheet = new Worksheet($spreadsheet, $title);
        $spreadsheet->addSheet($sheet);

        return $sheet;
    }
}
