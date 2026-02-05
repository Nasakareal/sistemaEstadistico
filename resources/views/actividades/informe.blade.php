@php
    use Carbon\Carbon;

    $tz = $tz ?? 'America/Mexico_City';
    $fecha = Carbon::parse($fechaSeleccionada, $tz);

    $logoPath = public_path('vialidad.png');
    $logoSrc = null;
    if (is_file($logoPath)) {
        $logoSrc = 'file:///' . str_replace('\\', '/', $logoPath);
    }

    $conteos = [];
    foreach ($actividades as $a) {
        $key = $a->subcategoria ? trim((string) $a->subcategoria->nombre) : 'Sin subcategoría';
        $conteos[$key] = ($conteos[$key] ?? 0) + 1;
    }
    ksort($conteos, SORT_NATURAL | SORT_FLAG_CASE);

    $total = (int) $actividades->count();

    $meses = [
        1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',
        7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'
    ];
    $fechaTexto = $fecha->format('d') . ' de ' . ($meses[(int)$fecha->format('n')] ?? $fecha->format('m')) . ' de ' . $fecha->format('Y');
@endphp

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28px 36px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        .header { position: relative; min-height: 86px; }
        .logo { position: absolute; top: 0; right: 0; width: 96px; height: auto; }
        .titulo { font-weight: 700; font-size: 13px; margin: 0; }
        .fecha { text-align: right; font-size: 12px; margin: 0; margin-top: 4px; }
        .parrafo { line-height: 1.55; text-align: justify; white-space: pre-line; margin-top: 10px; }
        .section-title { margin-top: 18px; font-weight: 700; }
        table.resumen { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.resumen td { padding: 5px 0; vertical-align: bottom; }
        td.lbl { width: 78%; }
        td.dots { width: 12%; border-bottom: 1px dotted #333; }
        td.val { width: 10%; text-align: right; font-variant-numeric: tabular-nums; }
        .total-row td { padding-top: 10px; font-weight: 700; }
        .nota { margin-top: 14px; }
        .hr { margin-top: 18px; border-top: 1px solid #ddd; }
        .photos-title { margin-top: 16px; font-weight: 700; }
        .grid { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .cell { width: 50%; padding: 8px; vertical-align: top; }
        .photo-box { border: 1px solid #e2e2e2; padding: 8px; border-radius: 6px; }
        .photo-img { width: 100%; height: 220px; object-fit: cover; display: block; }
        .cap { margin-top: 6px; font-size: 11px; }
        .small { font-size: 10px; color: #444; margin-top: 3px; }
    </style>
</head>
<body>

    <div class="header">
        @if($logoSrc)
            <img class="logo" src="{{ $logoSrc }}" alt="logo">
        @endif
        <p class="titulo">TARJETA INFORMATIVA</p>
        <p class="fecha">Morelia, Michoacán, a {{ $fechaTexto }}.</p>
    </div>

    <div class="parrafo">Por este conducto se hace de su conocimiento el plan de actividades correspondientes al día de la fecha, en esta
Unidad de Atención a Siniestros, se apoya en la entrada y salida de estudiantes en los planteles educativos,
seguridad en instituciones bancarias, comercios, Proximidad así como apoyo vialidad en las horas con mayor
afluencia vehicular y horas pico y críticas, en las diferentes entradas y salidas de esta ciudad capital; donde existe
congestionamiento vial en horas de entrada y salida, todo esto con la finalidad de lograr una mejor atención y
seguridad a la ciudadanía.</div>

    <div class="section-title">Apoyos:</div>

    <table class="resumen">
        @foreach($conteos as $subcategoriaNombre => $cantidadFotos)
            <tr>
                <td class="lbl">{{ $subcategoriaNombre }}</td>
                <td class="dots"></td>
                <td class="val">{{ str_pad((string)$cantidadFotos, 2, '0', STR_PAD_LEFT) }}</td>
            </tr>
        @endforeach

        <tr class="total-row">
            <td class="lbl">TOTAL</td>
            <td class="dots"></td>
            <td class="val">{{ str_pad((string)$total, 2, '0', STR_PAD_LEFT) }}</td>
        </tr>
    </table>

    <div class="nota">Se anexan gráficas ilustrativas de la operatividad realizada.</div>

    <div class="hr"></div>

    <div class="photos-title">Anexos fotográficos ({{ $fecha->format('d/m/Y') }})</div>

    @php
        $items = $actividades->values();
        $chunks = $items->chunk(2);
    @endphp

    <table class="grid">
        @foreach($chunks as $pair)
            <tr>
                @foreach($pair as $a)
                    @php
                        $sub = $a->subcategoria ? (string)$a->subcategoria->nombre : 'Sin subcategoría';
                        $cat = $a->categoria ? (string)$a->categoria->nombre : 'Sin categoría';

                        $rel = null;
                        if (isset($a->foto_pdf_path) && $a->foto_pdf_path) {
                            $rel = $a->foto_pdf_path;
                        } else {
                            $rel = $a->foto_path;
                        }

                        $abs = $rel ? public_path('storage/' . ltrim($rel, '/')) : null;
                        $src = ($abs && is_file($abs)) ? ('file:///' . str_replace('\\', '/', $abs)) : null;

                        $hora = optional($a->created_at)->timezone($tz)->format('H:i');
                    @endphp
                    <td class="cell">
                        <div class="photo-box">
                            @if($src)
                                <img class="photo-img" src="{{ $src }}" alt="foto">
                            @else
                                <div style="width:100%;height:220px;border:1px dashed #bbb;display:flex;align-items:center;justify-content:center;color:#666;">
                                    Sin imagen disponible
                                </div>
                            @endif
                            <div class="cap"><b>Subcategoría:</b> {{ $sub }}</div>
                            <div class="small"><b>Categoría:</b> {{ $cat }} &nbsp; | &nbsp; <b>Hora:</b> {{ $hora }} &nbsp; | &nbsp; <b>ID:</b> {{ $a->id }}</div>
                        </div>
                    </td>
                @endforeach
                @if($pair->count() === 1)
                    <td class="cell"></td>
                @endif
            </tr>
        @endforeach
    </table>

    <div style="margin-top:14px;font-weight:700;">
        TOTAL DE FOTOS: {{ str_pad((string)$total, 2, '0', STR_PAD_LEFT) }}
    </div>

</body>
</html>
