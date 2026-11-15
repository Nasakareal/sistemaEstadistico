<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$locSql = "UPPER(CONCAT_WS(' | ', COALESCE(h.calle,''), COALESCE(h.colonia,''), COALESCE(h.entre_calles,''), COALESCE(h.ubicacion_formateada,'')))";
$tunnelSql = "($locSql LIKE '%TUNEL%' OR $locSql LIKE '%TÚNEL%' OR $locSql LIKE '%TUENL%' OR $locSql LIKE '%TINEL 1%' OR $locSql LIKE '%TUNESL%')";

$rows = DB::table('hechos as h')
    ->leftJoin('lesionados as l', 'l.hecho_id', '=', 'h.id')
    ->whereDate('h.fecha', '>=', '2016-01-01')
    ->where('h.municipio', 'like', '%MORELIA%')
    ->whereRaw($tunnelSql)
    ->select([
        'h.id', 'h.folio_c5i', 'h.fecha', 'h.hora', 'h.calle', 'h.colonia',
        'h.entre_calles', 'h.ubicacion_formateada', 'h.lat', 'h.lng',
        'h.tipo_hecho', 'h.causas', 'h.danos_patrimoniales',
        'h.monto_danos_patrimoniales', 'h.situacion',
    ])
    ->selectRaw("COUNT(DISTINCT CASE WHEN UPPER(COALESCE(l.tipo_lesion,'')) LIKE '%FALLECID%' THEN l.id END) AS fallecidos")
    ->selectRaw("COUNT(DISTINCT CASE WHEN l.id IS NOT NULL AND UPPER(COALESCE(l.tipo_lesion,'')) NOT LIKE '%FALLECID%' THEN l.id END) AS lesionados")
    ->groupBy([
        'h.id', 'h.folio_c5i', 'h.fecha', 'h.hora', 'h.calle', 'h.colonia',
        'h.entre_calles', 'h.ubicacion_formateada', 'h.lat', 'h.lng',
        'h.tipo_hecho', 'h.causas', 'h.danos_patrimoniales',
        'h.monto_danos_patrimoniales', 'h.situacion',
    ])
    ->orderBy('h.fecha')->orderBy('h.hora')->get();

$normalize = static function ($value): string {
    $s = mb_strtoupper((string) $value, 'UTF-8');
    $s = strtr($s, ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N']);
    return preg_replace('/\s+/', ' ', $s) ?? $s;
};

$classify = static function ($r) use ($normalize): string {
    $s = $normalize(implode(' | ', [$r->calle, $r->colonia, $r->entre_calles, $r->ubicacion_formateada]));

    $ramal = str_contains($s, 'RAMAL') || str_contains($s, 'CAMELINAS') || str_contains($s, 'ALTOZANO')
        || str_contains($s, 'OCOLUSEN') || str_contains($s, 'OCOLUCEN') || str_contains($s, 'FLORESTA')
        || str_contains($s, 'ROTARISMO') || str_contains($s, 'MONTANA MONARCA');
    if ($ramal) {
        $t1 = preg_match('/(TUNEL|TUENL|TINEL)\s*(1|I)\b|PRIMER\s+TUNEL|1ER\s+TUNEL/', $s);
        $t2 = preg_match('/(TUNEL|TUENL|TINEL)\s*(2|II)\b|SEGUNDO\s+TUNEL|2O\.?\s+TUNEL|2DO\s+TUNEL/', $s);
        if ($t1 && !$t2) return 'RAMAL CAMELINAS - TUNEL 1';
        if ($t2 && !$t1) return 'RAMAL CAMELINAS - TUNEL 2';
        if ($t1 && $t2) return 'RAMAL CAMELINAS - ENTRE/REFERENCIA AMBOS';
        return 'RAMAL CAMELINAS - TUNEL SIN NUMERO';
    }

    if (str_contains($s, 'QUIROGA') || str_contains($s, 'TZINDURIO') || str_contains($s, 'TINDURIO')
        || str_contains($s, 'LA QUEMADA') || str_contains($s, 'PERIFERICO INDEPENDENCIA')
        || str_contains($s, 'MANANTIALES') || str_contains($s, 'MADERO PONIENTE')
        || str_contains($s, 'MADERO PTE') || str_contains($s, '14 DE FEBRERO')) {
        return 'DISTRIBUIDOR SALIDA A QUIROGA';
    }
    if (str_contains($s, 'SALAMANCA') || str_contains($s, 'ABASTOS') || str_contains($s, 'MORELOS NORTE')
        || str_contains($s, 'SANTIAGUITO') || str_contains($s, 'LOMAS DEL TECNOLOGICO') || str_contains($s, 'MIGUEL SILVA')) {
        return 'DISTRIBUIDOR SALIDA A SALAMANCA / ABASTOS';
    }
    if (str_contains($s, 'CHARO') || str_contains($s, 'MADERO ORIENTE') || str_contains($s, 'MADERO OTE')
        || str_contains($s, 'PERIFERICO REVOLUCION') || str_contains($s, 'ELIAS PEREZ AVALOS')
        || str_contains($s, 'ISAAC ARRIAGA') || str_contains($s, 'ENRIQUE RAMIREZ') || str_contains($s, 'MIL CUMBRES')) {
        return 'DISTRIBUIDOR SALIDA A CHARO / MIL CUMBRES';
    }
    if (str_contains($s, 'LA HUERTA') || str_contains($s, 'HERMANOS LOPEZ RAYON')) {
        return 'PASO INFERIOR LIBRAMIENTO / LA HUERTA';
    }
    return 'TUNEL NO IDENTIFICADO';
};

$rows = $rows->map(function ($r) use ($classify) {
    $r->grupo = $classify($r);
    return $r;
});

$summarize = static fn ($g) => [
    'hechos' => $g->count(),
    'fallecidos' => (int) $g->sum('fallecidos'),
    'lesionados' => (int) $g->sum('lesionados'),
    'solo_danos_materiales' => $g->filter(fn ($r) => (int) $r->fallecidos === 0 && (int) $r->lesionados === 0)->count(),
    'causas' => $g->groupBy(fn ($r) => trim((string) $r->causas) ?: 'SIN DATO')->map->count()->sortDesc(),
    'tipos' => $g->groupBy(fn ($r) => trim((string) $r->tipo_hecho) ?: 'SIN DATO')->map->count()->sortDesc(),
];

$normFolio = static fn ($folio) => preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $folio));
$excludedDuplicateIds = [21202, 56455];
$duplicateRows = $rows->whereIn('id', $excludedDuplicateIds)->values();
$rows = $rows->whereNotIn('id', $excludedDuplicateIds)->values();

$sameTime = $rows->groupBy(fn ($r) => $r->fecha . ' ' . $r->hora)->filter(fn ($g) => $g->count() > 1);
$sameFolio = $rows->filter(fn ($r) => $normFolio($r->folio_c5i) !== '')
    ->groupBy(fn ($r) => $normFolio($r->folio_c5i))->filter(fn ($g) => $g->count() > 1);

$result = [
    'cobertura' => ['desde' => $rows->min('fecha'), 'hasta' => $rows->max('fecha')],
    'duplicados_excluidos_del_calculo' => $duplicateRows->map(fn ($r) => ['id'=>$r->id,'fecha'=>$r->fecha,'hora'=>$r->hora,'grupo'=>$r->grupo,'folio'=>$r->folio_c5i]),
    'total_depurado' => $summarize($rows),
    'por_tunel' => $rows->groupBy('grupo')->map($summarize),
    'mismo_dia_hora' => $sameTime->map(fn ($g) => $g->map(fn ($r) => [
        'id'=>$r->id, 'grupo'=>$r->grupo, 'folio'=>$r->folio_c5i, 'lugar'=>implode(' | ', array_filter([$r->calle,$r->colonia,$r->entre_calles,$r->ubicacion_formateada])), 'tipo'=>$r->tipo_hecho,
    ])),
    'folio_repetido' => $sameFolio->map(fn ($g) => $g->map(fn ($r) => ['id'=>$r->id,'grupo'=>$r->grupo,'fecha'=>$r->fecha,'hora'=>$r->hora,'folio'=>$r->folio_c5i])),
    'no_identificados' => $rows->where('grupo', 'TUNEL NO IDENTIFICADO')->map(fn ($r) => [
        'id'=>$r->id,'fecha'=>$r->fecha,'hora'=>$r->hora,'folio'=>$r->folio_c5i,
        'lugar'=>implode(' | ', array_filter([$r->calle,$r->colonia,$r->entre_calles,$r->ubicacion_formateada])),
        'tipo'=>$r->tipo_hecho,'causa'=>$r->causas,'fallecidos'=>(int)$r->fallecidos,'lesionados'=>(int)$r->lesionados,
    ])->values(),
];

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), PHP_EOL;
