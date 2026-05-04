<?php

namespace App\Services;

use App\Models\Hechos;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;

class BitacoraTurnoGenerator
{
    private const UNIDAD_SINIESTROS_ID = 1;

    public function generar(string $fecha, $turnoRef): string
    {
        $tz = 'America/Mexico_City';

        $turnoId = $this->resolveTurnoId($turnoRef);
        if (!$turnoId) {
            throw new \RuntimeException('No se pudo resolver el turno.');
        }

        [$inicio, $fin] = $this->rangoPorTurno($fecha, $tz);

        $userIdsTurno = DB::table('users')
            ->where('turno_id', $turnoId)
            ->where('unidad_id', self::UNIDAD_SINIESTROS_ID)
            ->pluck('id')
            ->map(fn ($v) => (int) $v)
            ->values()
            ->all();

        $hechos = collect();

        if (!empty($userIdsTurno)) {
            $hechos = Hechos::with(['vehiculos', 'lesionados'])
                ->whereBetween('created_at', [$inicio, $fin])
                ->where('unidad_org_id', self::UNIDAD_SINIESTROS_ID)
                ->whereIn('created_by', $userIdsTurno)
                ->orderByRaw("COALESCE(fecha, DATE(created_at)) asc")
                ->orderByRaw("COALESCE(hora, TIME(created_at)) asc")
                ->orderBy('created_at', 'asc')
                ->get();
        }

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(10);

        $section = $phpWord->addSection([
            'pageSizeW'    => 18720,
            'pageSizeH'    => 12240,
            'marginTop'    => 250,
            'marginRight'  => 600,
            'marginBottom' => 250,
            'marginLeft'   => 600,
        ]);

        $pCenter0 = ['alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0, 'lineHeight' => 1.0];
        $pLeft0   = ['alignment' => Jc::LEFT,   'spaceBefore' => 0, 'spaceAfter' => 0, 'lineHeight' => 1.0];

        $phpWord->addTableStyle('EncabezadoTablaBitacora', [
            'borderSize'  => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin'  => 0,
            'alignment'   => JcTable::CENTER,
        ]);

        $tEnc = $section->addTable('EncabezadoTablaBitacora');

        $tEnc->addRow(420);
        $tEnc->addCell(9000, ['valign' => 'center'])->addImage(public_path('ssp.jpg'), [
            'width'     => 140,
            'alignment' => Jc::LEFT
        ]);
        $tEnc->addCell(9000, ['valign' => 'center'])->addImage(public_path('vialidad.png'), [
            'width'     => 70,
            'alignment' => Jc::RIGHT
        ]);

        $tEnc->addRow(420);
        $cTitulo = $tEnc->addCell(18000, ['gridSpan' => 2, 'valign' => 'center']);
        $trTitulo = $cTitulo->addTextRun($pCenter0);
        $trTitulo->addText('BITÁCORA POR TURNO', ['bold' => true, 'size' => 12]);
        $trTitulo->addText('    ', ['size' => 12]);
        $trTitulo->addText('UNIDAD DE ATENCIÓN A SINIESTROS', ['bold' => true, 'size' => 12]);

        $turnoLetra = $this->turnoLetraDesdeId($turnoId);
        $trTitulo2 = $cTitulo->addTextRun($pCenter0);
        $trTitulo2->addText('TURNO ' . $turnoLetra . '   ', ['bold' => true, 'size' => 11]);
        $trTitulo2->addText('(' . $inicio->format('d/m/Y H:i') . ' a ' . $fin->format('d/m/Y H:i') . ')', ['size' => 10]);

        $dia  = Carbon::parse($fecha, $tz)->format('d');
        $mes  = strtoupper(Carbon::parse($fecha, $tz)->translatedFormat('F'));
        $anio = Carbon::parse($fecha, $tz)->format('Y');

        $phpWord->addTableStyle('FechaDerechaTabla', [
            'borderSize'  => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin'  => 0,
            'alignment'   => JcTable::CENTER,
        ]);

        $tFecha = $section->addTable('FechaDerechaTabla');

        $tFecha->addRow(260);
        $tFecha->addCell(12000, ['valign' => 'center'])->addText('', ['size' => 10], $pCenter0);
        $tFecha->addCell(1500,  ['valign' => 'center'])->addText($dia,  ['bold' => true, 'size' => 10], $pCenter0);
        $tFecha->addCell(2500,  ['valign' => 'center'])->addText($mes,  ['bold' => true, 'size' => 10], $pCenter0);
        $tFecha->addCell(1500,  ['valign' => 'center'])->addText($anio, ['bold' => true, 'size' => 10], $pCenter0);

        $phpWord->addTableStyle('BitacoraTabla', [
            'borderSize'  => 8,
            'borderColor' => '000000',
            'cellMargin'  => 20,
            'alignment'   => JcTable::CENTER,
        ]);

        $table = $section->addTable('BitacoraTabla');

        $headerCell = ['bgColor' => 'D9D9D9', 'valign' => 'center'];
        $cell       = ['valign' => 'center'];

        $wNo     = 650;
        $wHora   = 1200;
        $wUnidad = 1150;
        $wPerito = 3600;
        $wLugar  = 5200;
        $wGrua   = 1500;
        $wLes    = 2000;
        $wTipo   = 2400;
        $wObs    = 2500;

        $table->addRow(320);
        $table->addCell($wNo,     $headerCell)->addText('N°', ['bold' => true, 'size' => 10], $pCenter0);
        $table->addCell($wHora,   $headerCell)->addText('HORA DE SALIDA', ['bold' => true, 'size' => 10], $pCenter0);
        $table->addCell($wUnidad, $headerCell)->addText('UNIDAD', ['bold' => true, 'size' => 10], $pCenter0);
        $table->addCell($wPerito, $headerCell)->addText('PERITO(S) NOMBRE', ['bold' => true, 'size' => 10], $pCenter0);
        $table->addCell($wLugar,  $headerCell)->addText('LUGAR DE LOS HECHOS', ['bold' => true, 'size' => 10], $pCenter0);
        $table->addCell($wGrua,   $headerCell)->addText('GRUA', ['bold' => true, 'size' => 10], $pCenter0);
        $table->addCell($wLes,    $headerCell)->addText('PERSONAS LESIONADAS', ['bold' => true, 'size' => 10], $pCenter0);
        $table->addCell($wTipo,   $headerCell)->addText('TIPO DE HECHO', ['bold' => true, 'size' => 10], $pCenter0);
        $table->addCell($wObs,    $headerCell)->addText('OBSERVACIÓN / ESTATUS', ['bold' => true, 'size' => 10], $pCenter0);

        if ($hechos->count() === 0) {
            $table->addRow(300);
            $table->addCell($wNo,     $cell)->addText('-', ['size' => 10], $pCenter0);
            $table->addCell($wHora,   $cell)->addText('-', ['size' => 10], $pCenter0);
            $table->addCell($wUnidad, $cell)->addText('-', ['size' => 10], $pCenter0);
            $table->addCell($wPerito, $cell)->addText('-', ['size' => 10], $pLeft0);
            $table->addCell($wLugar,  $cell)->addText('SIN NOVEDADES EN EL TURNO', ['bold' => true, 'size' => 10], $pLeft0);
            $table->addCell($wGrua,   $cell)->addText('-', ['size' => 10], $pCenter0);
            $table->addCell($wLes,    $cell)->addText('-', ['size' => 10], $pCenter0);
            $table->addCell($wTipo,   $cell)->addText('-', ['size' => 10], $pCenter0);
            $table->addCell($wObs,    $cell)->addText('-', ['size' => 10], $pCenter0);
        } else {
            $n = 1;
            foreach ($hechos as $hecho) {
                $hora = '';
                if (!empty($hecho->hora)) {
                    $hora = Carbon::parse($hecho->hora, $tz)->format('H:i');
                } else {
                    $hora = Carbon::parse($hecho->created_at, $tz)->format('H:i');
                }

                $unidad = (string)($hecho->unidad ?? '');
                $perito = strtoupper((string)($hecho->perito ?? ''));

                $lugar = trim((string)($hecho->calle ?? ''));
                if (!empty($hecho->colonia))   $lugar .= ($lugar !== '' ? ', ' : '') . 'COL. ' . $hecho->colonia;
                if (!empty($hecho->municipio)) $lugar .= ($lugar !== '' ? ', ' : '') . $hecho->municipio;

                $grua = 'NO';
                if ($hecho->vehiculos && $hecho->vehiculos->count() > 0) {
                    $vConGrua = $hecho->vehiculos->first(function ($v) {
                        return $v->grua !== null && trim((string)$v->grua) !== '' && strtolower(trim((string)$v->grua)) !== 'n/a';
                    });
                    if ($vConGrua) $grua = strtoupper(trim((string)$vConGrua->grua));
                }

                $personasLes = ($hecho->lesionados && $hecho->lesionados->count() > 0)
                    ? ($hecho->lesionados->count() . ' PERSONA(S)')
                    : 'NO';

                $tipoHecho  = strtoupper((string)($hecho->tipo_hecho ?? ''));
                $estatus    = strtoupper((string)($hecho->situacion ?? ''));
                $obsEstatus = trim($estatus);

                $table->addRow(300);
                $table->addCell($wNo,     $cell)->addText((string)$n, ['size' => 10], $pCenter0);
                $table->addCell($wHora,   $cell)->addText($hora !== '' ? $hora : '-', ['size' => 10], $pCenter0);
                $table->addCell($wUnidad, $cell)->addText($unidad !== '' ? $unidad : '-', ['size' => 10], $pCenter0);
                $table->addCell($wPerito, $cell)->addText($perito !== '' ? $perito : '-', ['size' => 10], $pLeft0);
                $table->addCell($wLugar,  $cell)->addText($lugar !== '' ? $lugar : '-', ['size' => 10], $pLeft0);
                $table->addCell($wGrua,   $cell)->addText($grua, ['size' => 10], $pCenter0);
                $table->addCell($wLes,    $cell)->addText($personasLes, ['size' => 10], $pCenter0);
                $table->addCell($wTipo,   $cell)->addText($tipoHecho !== '' ? $tipoHecho : '-', ['size' => 10], $pCenter0);
                $table->addCell($wObs,    $cell)->addText($obsEstatus !== '' ? $obsEstatus : '-', ['size' => 10], $pCenter0);

                $n++;
            }
        }

        $nombreFirma = $turnoLetra === 'A'
            ? 'FERNANDO RUBALCAVA RIVERA'
            : 'JORGE ARMANDO MORALES PEREZ';

        $section->addTextBreak(3);

        $section->addText('ATENTAMENTE.', ['bold' => true, 'size' => 10], [
            'alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0
        ]);

        $section->addText('COMANDANTE DE TURNO.', ['bold' => true, 'size' => 10], [
            'alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0
        ]);

        $section->addTextBreak(3);

        $section->addText($nombreFirma, ['bold' => true, 'size' => 10], [
            'alignment' => Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0
        ]);

        $filename = "bitacora_turno_{$turnoLetra}_{$fecha}.docx";
        $tempPath = storage_path("app/tmp/{$filename}");

        if (!is_dir(dirname($tempPath))) {
            @mkdir(dirname($tempPath), 0775, true);
        }

        IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

        return $tempPath;
    }

    private function rangoPorTurno(string $fecha, string $tz): array
    {
        $fin    = Carbon::parse($fecha, $tz)->setTime(7, 0, 0);
        $inicio = $fin->copy()->subDay();
        return [$inicio, $fin];
    }

    private function turnoLetraDesdeId(int $turnoId): string
    {
        $row = DB::table('turnos')->where('id', $turnoId)->first(['nombre', 'slug']);
        if (!$row) return 'B';

        $nombre = strtoupper(trim((string)($row->nombre ?? '')));
        $slug   = strtoupper(trim((string)($row->slug ?? '')));

        if ($nombre === 'A' || str_contains($nombre, ' A') || $slug === 'A' || str_contains($slug, 'A')) {
            return 'A';
        }

        return 'B';
    }

    private function resolveTurnoId($turnoRef): ?int
    {
        if (is_numeric($turnoRef)) {
            return (int) $turnoRef;
        }

        $t = strtoupper(trim((string) $turnoRef));

        if ($t === 'A' || $t === 'TURNO A' || $t === 'TURNO_A') {
            $id = DB::table('turnos')->whereRaw('UPPER(TRIM(slug)) IN (?, ?, ?)', ['A', 'TURNO-A', 'TURNO_A'])->value('id');
            if ($id) return (int) $id;

            $id = DB::table('turnos')->whereRaw('UPPER(TRIM(nombre)) = ?', ['A'])->value('id');
            if ($id) return (int) $id;

            $id = DB::table('turnos')->whereRaw('UPPER(nombre) LIKE ?', ['%A%'])->value('id');
            return $id ? (int) $id : null;
        }

        if ($t === 'B' || $t === 'TURNO B' || $t === 'TURNO_B') {
            $id = DB::table('turnos')->whereRaw('UPPER(TRIM(slug)) IN (?, ?, ?)', ['B', 'TURNO-B', 'TURNO_B'])->value('id');
            if ($id) return (int) $id;

            $id = DB::table('turnos')->whereRaw('UPPER(TRIM(nombre)) = ?', ['B'])->value('id');
            if ($id) return (int) $id;

            $id = DB::table('turnos')->whereRaw('UPPER(nombre) LIKE ?', ['%B%'])->value('id');
            return $id ? (int) $id : null;
        }

        $id = DB::table('turnos')->whereRaw('UPPER(TRIM(slug)) = ?', [$t])->value('id');
        if ($id) return (int) $id;

        $id = DB::table('turnos')->whereRaw('UPPER(TRIM(nombre)) = ?', [$t])->value('id');
        return $id ? (int) $id : null;
    }
}
