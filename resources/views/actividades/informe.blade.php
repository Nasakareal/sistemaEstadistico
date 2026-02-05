@php
    use Carbon\Carbon;

    $tz = $tz ?? 'America/Mexico_City';
    $fecha = Carbon::parse($fechaSeleccionada, $tz);

    // Logo: mejor en ruta relativa dentro de public/ (respeta chroot)
    $logoRel = null;
    $logoAbs = public_path('vialidad.png');
    if (is_file($logoAbs)) {
        $logoRel = 'vialidad.png';
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

    // Agrupar para 2 fotos por página (1 arriba de la otra)
    $items = $actividades->values();
    $pages = $items->chunk(2);
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

        .photo-box { border: 1px solid #e2e2e2; padding: 8px; border-radius: 6px; margin-top: 10px; }
        .photo-img { width: 100%; height: 240px; object-fit: cover; display: block; }

        .cap { margin-top: 6px; font-size: 11px; }
        .small { font-size: 10px; color: #444; margin-top: 3px; }

        .placeholder {
            width: 100%;
            height: 240px;
            border: 1px dashed #bbb;
            display: block;
            text-align: center;
            line-height: 240px;
            color: #666;
            font-size: 12px;
        }

        .page-break { page-break-after: always; }
    </style>
</head>
<body>

    <div class="header">
        @if($logoRel)
            <img class="logo" src="{{ $logoRel }}" alt="logo">
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

    @foreach($pages as $pageIndex => $pair)
        @foreach($pair as $a)
            @php
                $sub = $a->subcategoria ? (string)$a->subcategoria->nombre : 'Sin subcategoría';
                $cat = $a->categoria ? (string)$a->categoria->nombre : 'Sin categoría';
                $hora = optional($a->created_at)->timezone($tz)->format('H:i');

                // Preferir cache si existe
                $rel = $a->foto_pdf_path ?: $a->foto_path;

                // OJO: DomPDF con chroot(public_path()) funciona mejor con ruta RELATIVA a public/
                // nuestras fotos están en public/storage/...
                $srcRel = null;

                if ($rel) {
                    $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));

                    // HEIC/HEIF no soportado por DomPDF
                    if (!in_array($ext, ['heic','heif'], true)) {
                        $candidate = 'storage/' . ltrim($rel, '/'); // <- relativo a public/
                        $abs = public_path($candidate);
                        if (is_file($abs)) {
                            $srcRel = $candidate;
                        }
                    }
                }
            @endphp

            <div class="photo-box">
                @if($srcRel)
                    <img class="photo-img" src="{{ $srcRel }}" alt="foto">
                @else
                    <div class="placeholder">Sin imagen disponible</div>
                @endif

                <div class="cap"><b>Subcategoría:</b> {{ $sub }}</div>
                <div class="small"><b>Categoría:</b> {{ $cat }} &nbsp; | &nbsp; <b>Hora:</b> {{ $hora }} &nbsp; | &nbsp; <b>ID:</b> {{ $a->id }}</div>
            </div>
        @endforeach

        @if($pageIndex < ($pages->count() - 1))
            <div class="page-break"></div>
        @endif
    @endforeach

    <div style="margin-top:14px;font-weight:700;">
        TOTAL DE FOTOS: {{ str_pad((string)$total, 2, '0', STR_PAD_LEFT) }}
    </div>

</body>
</html>
