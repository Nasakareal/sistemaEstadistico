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
    public function generar(string $fecha, string|int $turnoRef): string
    {
        $tz = 'America/Mexico_City';

        $turnoId = $this->resolveTurnoId($turnoRef);
        if (!$turnoId) {
            throw new \RuntimeException('No se pudo resolver el turno.');
        }

        [$inicio, $fin] = $this->rangoPorTurno($fecha, $turnoId, $tz);

        // 1) Usuarios (IDs) del turno
        $userIdsTurno = DB::table('users')
            ->where('turno_id', $turnoId)
            ->whereNotNull('id')
            ->pluck('id')
            ->map(fn ($v) => (int) $v)
            ->values()
            ->all();

        // 2) Mapa de nombres (fallback) por si created_by viene null pero perito trae el nombre
        $peritos = $this->peritosPorTurno($turnoId);

        // 3) Traemos hechos del rango.
        //    - Principal: created_by IN (users del turno)
        //    - Fallback: created_by NULL pero perito coincide con algún user del turno
        $hechos = Hechos::with(['vehiculos', 'lesionados'])
            ->whereBetween('created_at', [$inicio, $fin])
            ->where(function ($q) use ($userIdsTurno) {
                if (!empty($userIdsTurno)) {
                    $q->whereIn('created_by', $userIdsTurno);
                } else {
                    // Si por alguna razón no hay users en ese turno, evitamos romper:
                    $q->whereRaw('1=0');
                }
            })
            ->orWhere(function ($q) use ($inicio, $fin, $peritos) {
                // Fallback: created_by null pero perito coincide (solo dentro del mismo rango)
                $q->whereBetween('created_at', [$inicio, $fin])
                  ->whereNull('created_by')
                  ->whereNotNull('perito');

                // Si no hay peritos, que no meta nada
                if (empty($peritos)) {
                    $q->whereRaw('1=0');
                }
            })
            ->orderByRaw("COALESCE(fecha, DATE(created_at)) asc")
            ->orderByRaw("COALESCE(hora, TIME(created_at)) asc")
            ->orderBy('created_at', 'asc')
            ->get();

        // Aplicamos el fallback de perito (solo a los que entraron por OR y a cualquiera con perito)
        if (!empty($peritos)) {
            $hechos = $hechos->filter(function ($h) use ($peritos) {
                // Si trae created_by válido, ya pasó.
                if (!empty($h->created_by)) return true;

                // Si no trae created_by, intentamos por nombre
                $p = $this->norm((string)($h->perito ?? ''));
                return $p !== '' && isset($peritos[$p]);
            })->values();
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

        // Si no hay hechos, dejamos evidencia en el documento (no “vacío”)
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

    private function peritosPorTurno(int $turnoId): array
    {
        $rows = DB::table('users')
            ->where('turno_id', $turnoId)
            ->whereNotNull('name')
            ->get(['name']);

        $map = [];
        foreach ($rows as $r) {
            $k = $this->norm((string) $r->name);
            if ($k !== '') $map[$k] = true;
        }

        return $map;
    }

    private function rangoPorTurno(string $fecha, int $turnoId, string $tz): array
    {
        $letra = $this->turnoLetraDesdeId($turnoId);

        $d = Carbon::parse($fecha, $tz);

        // TURNO A: 06:00 a 18:00 del MISMO día
        if ($letra === 'A') {
            $inicio = $d->copy()->setTime(6, 0, 0);
            $fin    = $d->copy()->setTime(18, 0, 0);
            return [$inicio, $fin];
        }

        // TURNO B: 18:00 del día anterior a 06:00 del día indicado
        $inicio = $d->copy()->setTime(18, 0, 0)->subDay();
        $fin    = $d->copy()->setTime(6, 0, 0);

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

    private function resolveTurnoId(string|int $turnoRef): ?int
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

    private function norm(string $s): string
    {
        $s = mb_strtoupper(trim($s), 'UTF-8');
        $s = preg_replace('/\s+/u', ' ', $s);
        return trim((string) $s);
    }
}
