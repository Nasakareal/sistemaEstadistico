<?php

namespace App\Services;

use App\Models\Personal;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VialidadesUrbanasDiarioWhatsAppService
{
    private const UNIDAD_VIALIDADES_URBANAS_ID = 5;
    private const CATEGORY_ORDER = [
        'INSTITUCIONES',
        'REPORTES C5I',
        'ABANDERAMIENTOS',
        'MONITOREOS',
        'AUXILIO VIAL A CONDUCTORES',
        'DISPOSITIVOS DE SEGURIDAD VIAL',
        'CAMPANAS',
        'PROXIMIDAD SOCIAL',
        'OPERATIVOS',
        'PROGRAMAS',
        'CAPACITACIONES',
    ];

    private const CATEGORY_LABELS = [
        'INSTITUCIONES' => 'Apoyo a instituciones',
        'REPORTES C5I' => 'Atención a reportes de C5i',
        'ABANDERAMIENTOS' => 'Abanderamientos',
        'MONITOREOS' => 'Monitoreos',
        'AUXILIO VIAL A CONDUCTORES' => 'Auxilio vial a Conductores',
        'DISPOSITIVOS DE SEGURIDAD VIAL' => 'Dispositivos de Vialidad',
        'CAMPANAS' => 'Campañas',
        'PROXIMIDAD SOCIAL' => 'Proximidad social',
        'OPERATIVOS' => 'Operativos',
    ];

    private const SUBCATEGORIES_AS_OTHER = [
        'CARRETERAS',
        'CASETAS',
        'BLOQUEO CARRETERO',
        'RESGUARDO DE VEHICULO POR OBSTRUCCION O ABANDONO',
        'ALCOHOLIMETRIA',
        'CONDUCE CON LEGALIDAD',
    ];

    private const SUBCATEGORY_LABELS = [
        'ESCUELAS' => 'Escuelas',
        'ACTOS DELICTIVOS' => 'Actos delictivos',
        'SINIESTROS' => 'Siniestros viales',
        'HECHOS DE TRANSITO' => 'Siniestros viales',
        'ACCIDENTES' => 'Siniestros viales',
        'CORTES DE CIRCULACION' => 'Cortes a la circulación',
        'OBRAS PUBLICAS' => 'Construcción de obras públicas',
        'VIAS FERREAS' => 'Vías férreas',
        'PERIFERICOS' => 'Periférico',
        'AVENIDAS' => 'Avenidas principales',
        'TIENDAS DEPARTAMENTALES' => 'Tiendas departamentales',
        'BANCOS' => 'Bancos',
        'OFICINAS GUBERNAMENTALES' => 'Oficinas Gubernamentales',
        'FALLAS MECANICAS' => 'Fallas mecánicas',
        'APOYO A LA VIALIDAD' => 'Apoyo a la vialidad',
        'PASO LIBRE DE FUNCIONARIOS' => 'Paso libre a funcionarios',
        'ZONAS DE MAYOR PASE DE TRANSEUNTES' => 'Zonas de mayor paso de personas peatonas',
        'PATRULLAJES' => 'Patrullajes',
        'CONCIENTIZACION Y PREVENCION' => 'Concientización y prevención',
        'RECORRIDOS DE PROXIMIDAD' => 'Recorridos de proximidad.',
        'APOYO A PERSONAS DE LA TERCERA EDAD' => 'Apoyo a personas con movilidad limitada.',
        'APOYO A TURISTAS' => 'Apoyo a turistas',
    ];

    public function generar(?Carbon $corte = null): array
    {
        [$inicio, $fin] = $this->rango($corte);
        $totales = $this->totales($inicio, $fin);
        $novedades = $this->incluirNovedades() ? $this->novedades($inicio, $fin) : [];
        $firmaNombre = $this->firmaNombre();
        $mensaje = $this->mensaje($inicio, $fin, $totales, $novedades, $firmaNombre);

        return [
            'inicio' => $inicio,
            'fin' => $fin,
            'totales' => $totales,
            'novedades' => $novedades,
            'mensaje' => $mensaje,
            'firma_nombre' => $firmaNombre,
        ];
    }

    public function generarDemo(?Carbon $corte = null): array
    {
        [$inicio, $fin] = $this->rango($corte);
        $totales = [
            ['nombre' => 'INSTITUCIONES', 'total' => 6, 'subcategorias' => ['ESCUELAS']],
            ['nombre' => 'REPORTES C5i', 'total' => 2, 'subcategorias' => ['ACTOS DELICTIVOS', 'Otros (quema pastizal)']],
            ['nombre' => 'ABANDERAMIENTOS', 'total' => 5, 'subcategorias' => ['CORTES DE CIRCULACIÓN', 'ACCIDENTES', 'OBRAS PÚBLICAS']],
            ['nombre' => 'MONITOREOS', 'total' => 36, 'subcategorias' => ['VÍAS FÉRREAS', 'PERIFÉRICOS', 'AVENIDAS', 'TIENDAS DEPARTAMENTALES', 'BANCOS', 'OFICINAS GUBERNAMENTALES']],
            ['nombre' => 'AUXILIO VIAL A CONDUCTORES', 'total' => 4, 'subcategorias' => ['FALLAS MECÁNICAS']],
            ['nombre' => 'DISPOSITIVOS DE SEGURIDAD VIAL', 'total' => 86, 'subcategorias' => ['APOYO A LA VIALIDAD', 'PASO LIBRE DE FUNCIONARIOS', 'ZONAS DE MAYOR PASE DE TRANSEÚNTES', 'PATRULLAJES']],
            ['nombre' => 'CAMPAÑAS', 'total' => 1, 'subcategorias' => ['CONCIENTIZACIÓN Y PREVENCIÓN']],
            ['nombre' => 'PROXIMIDAD SOCIAL', 'total' => 45, 'subcategorias' => ['RECORRIDOS DE PROXIMIDAD', 'APOYO A PERSONAS DE LA TERCERA EDAD', 'APOYO A TURISTAS']],
        ];
        $novedades = [
            'Atención a reporte de C5i quema de pastizal, se apoya a H. Ayuntamiento abanderando el lugar para agilizar la vialidad y evitar algún siniestro.',
            'Se realizan recomendaciones a operadores del transporte público para una movilidad segura y accesible.',
            'Apoyo a guardia de seguridad privada en Soriana Híper con ciudadano que se negaba a pagar artículos que consumió. Se checan antecedentes, sin novedad; accede a pagar.',
            'Auxilios viales en diferentes puntos de la Ciudad a conductores que presentaron fallas mecánicas en sus vehículos.',
            'Recorridos de prevención y vigilancia en diferentes puntos de la ciudad, detectando riesgos y protegiendo a la ciudadanía mediante la vigilancia constante.',
            'Dispositivo de seguridad Escuela Segura para el cruce de calles y avenidas de gran afluencia vehicular, cuidando a las infancias, padres de familia y docentes.',
            'Dispositivos de vialidad en Avenidas y Periférico con el objetivo de agilizar la vialidad y proteger la seguridad de los sujetos activos de la movilidad.',
        ];
        $mensaje = $this->mensaje(
            $inicio,
            $fin,
            $totales,
            $this->incluirNovedades() ? $novedades : [],
            'MTRO. LUGO ORDORICA LUIS EDUARDO.'
        );

        return [
            'inicio' => $inicio,
            'fin' => $fin,
            'totales' => $totales,
            'novedades' => $novedades,
            'mensaje' => $mensaje,
            'firma_nombre' => 'MTRO. LUGO ORDORICA LUIS EDUARDO.',
        ];
    }

    public function rango(?Carbon $corte = null): array
    {
        $timezone = $this->timezone();
        $base = $corte ? $corte->copy()->timezone($timezone) : Carbon::now($timezone);
        [$hora, $minuto, $segundo] = $this->horaCorte();
        $corteHoy = $base->copy()->setTime($hora, $minuto, $segundo);

        $fin = $base->greaterThanOrEqualTo($corteHoy)
            ? $corteHoy
            : $corteHoy->copy()->subDay();

        return [$fin->copy()->subDay(), $fin];
    }

    public function templateChunks(string $mensaje, int $maxBodyChars = 850): array
    {
        $chunks = $this->splitMessage($mensaje, $maxBodyChars);
        $total = count($chunks);
        $out = [];

        foreach ($chunks as $index => $chunk) {
            $part = $index + 1;

            $out[] = [
                'part' => $part,
                'total' => $total,
                'body' => $chunk,
                'parameters' => [
                    (string) $part,
                    (string) $total,
                    $this->templateParameterText($chunk),
                ],
            ];
        }

        return $out;
    }

    public function dailyTemplateMessages(array $resumen): array
    {
        $params = $this->dailyTemplateParams($resumen);
        $firmaNombre = trim((string) ($resumen['firma_nombre'] ?? $this->firmaNombre()));

        return [[
            'part' => 1,
            'total' => 1,
            'body' => $this->dailyTemplateBody($params, $firmaNombre),
            'parameters' => $params,
        ]];
    }

    public function dailyTemplateParams(array $resumen): array
    {
        $inicio = $resumen['inicio'];
        $fin = $resumen['fin'];
        $totales = $this->totalesPorCategoria($resumen['totales'] ?? []);

        return [
            mb_strtoupper($fin->copy()->locale('es')->translatedFormat('l d F Y'), 'UTF-8'),
            $this->periodoTexto($inicio, $fin),
            $this->categoriaResumenSimple($totales, 'INSTITUCIONES'),
            $this->categoriaResumenSimple($totales, 'REPORTES C5I'),
            $this->categoriaResumenSimple($totales, 'ABANDERAMIENTOS'),
            $this->categoriaResumenSimple($totales, 'MONITOREOS'),
            $this->categoriaResumenSimple($totales, 'AUXILIO VIAL A CONDUCTORES'),
            $this->categoriaResumenConOtros(
                $totales,
                'DISPOSITIVOS DE SEGURIDAD VIAL',
                ['OPERATIVOS']
            ),
            $this->categoriaResumenSimple($totales, 'CAMPAÑAS'),
            $this->categoriaResumenSimple($totales, 'PROXIMIDAD SOCIAL'),
        ];
    }

    protected function dailyTemplateBody(array $params, string $firmaNombre = ''): string
    {
        $p = array_values(array_map('strval', $params));

        for ($i = count($p); $i < 10; $i++) {
            $p[] = '';
        }

        $firmaCargo = trim((string) config(
            'services.whatsapp.vialidades_urbanas.firma_cargo',
            'SUBDIRECTOR DE PROTECCIÓN EN VIALIDADES URBANAS'
        ));
        $firmaNombre = trim($firmaNombre);

        return "UNIDAD DE PROTECCIÓN EN VIALIDADES URBANAS\n"
            . "{$p[0]}\n"
            . "ACTIVIDADES RELEVANTES DE LAS {$p[1]}\n\n"
            . "- Apoyo a instituciones:\n{$p[2]}\n\n"
            . "- Atención a reportes de C5i:\n{$p[3]}\n\n"
            . "- Abanderamientos:\n{$p[4]}\n\n"
            . "- Monitoreos:\n{$p[5]}\n\n"
            . "- Auxilio vial a Conductores:\n{$p[6]}\n\n"
            . "- Dispositivos de Vialidad:\n{$p[7]}\n\n"
            . "- Campañas:\n{$p[8]}\n\n"
            . "- Proximidad social:\n{$p[9]}\n\n"
            . "RESPETUOSAMENTE\n"
            . mb_strtoupper($firmaCargo, 'UTF-8')
            . ($firmaNombre !== '' ? "\n" . mb_strtoupper($firmaNombre, 'UTF-8') : '');
    }

    public function textChunks(string $mensaje, int $maxBodyChars = 3900): array
    {
        $chunks = $this->splitMessage($mensaje, $maxBodyChars);
        $total = count($chunks);

        if ($total <= 1) {
            return $chunks;
        }

        return array_map(function (string $chunk, int $index) use ($total) {
            return 'PARTE ' . ($index + 1) . ' DE ' . $total . "\n\n" . $chunk;
        }, $chunks, array_keys($chunks));
    }

    protected function mensaje(Carbon $inicio, Carbon $fin, array $totales, array $novedades, ?string $firmaNombreOverride = null): string
    {
        $lineas = [];
        $lineas[] = 'GUARDIA CIVIL';
        $lineas[] = 'COORDINACIÓN DEL AGRUPAMIENTO DE SEGURIDAD VIAL';
        $lineas[] = 'UNIDAD DE PROTECCIÓN EN VIALIDADES URBANAS';
        $lineas[] = mb_strtoupper($fin->copy()->locale('es')->translatedFormat('l d F Y'), 'UTF-8');
        $lineas[] = 'ACTIVIDADES RELEVANTES DE LAS '
            . $this->horaTexto($inicio)
            . ' HORAS DEL '
            . $inicio->format('d/m/Y')
            . ' A LAS '
            . $this->horaTexto($fin)
            . ' HORAS DEL '
            . $fin->format('d/m/Y');
        $lineas[] = '';

        if (empty($totales)) {
            $lineas[] = '- Sin actividades capturadas: 00';
            $lineas[] = '';
        } else {
            foreach ($this->lineasReporte($totales) as $lineaReporte) {
                $lineas[] = $lineaReporte;
            }
        }

        if (!empty($novedades)) {
            foreach ($novedades as $index => $novedad) {
                $lineas[] = ($index + 1) . '. ' . $novedad;
            }

            $lineas[] = '';
        }

        $lineas[] = '';
        $lineas[] = 'RESPETUOSAMENTE';
        $lineas[] = (string) config(
            'services.whatsapp.vialidades_urbanas.firma_cargo',
            'SUBDIRECTOR DE PROTECCIÓN EN VIALIDADES URBANAS'
        );

        $firmaNombre = $firmaNombreOverride ?? $this->firmaNombre();

        if ($firmaNombre !== '') {
            $lineas[] = mb_strtoupper($firmaNombre, 'UTF-8');
        }

        return trim(implode("\n", $lineas));
    }

    protected function lineasReporte(array $totales): array
    {
        $lineas = [];

        foreach ($totales as $categoria) {
            if (!isset(self::CATEGORY_LABELS[$this->norm((string) $categoria['nombre'])])) {
                continue;
            }

            $lineas[] = '- ' . $this->categoryLabel((string) $categoria['nombre']) . ': ' . $this->pad((int) $categoria['total']);
            $printedSubcategories = [];

            foreach ($categoria['subcategorias'] as $subcategoria) {
                $label = $this->subcategoryLabel((string) $subcategoria);
                $key = $this->norm($label);

                if (isset($printedSubcategories[$key])) {
                    continue;
                }

                $printedSubcategories[$key] = true;
                $lineas[] = '-' . $label;
            }

            $lineas[] = ' ';
        }

        while (!empty($lineas) && trim((string) end($lineas)) === '') {
            array_pop($lineas);
        }

        return $lineas;
    }

    protected function totalesPorCategoria(array $totales): array
    {
        $out = [];

        foreach ($totales as $categoria) {
            $key = $this->norm((string) ($categoria['nombre'] ?? ''));

            if (!isset(self::CATEGORY_LABELS[$key])) {
                continue;
            }

            $out[$key] = $categoria;
        }

        return $out;
    }

    protected function categoriaResumenSimple(array $totales, string $categoriaKey): string
    {
        $key = $this->norm($categoriaKey);
        $categoria = $totales[$key] ?? null;

        if (!$categoria) {
            return '00 - sin actividades.';
        }

        $total = (int) ($categoria['total'] ?? 0);
        $labels = [];
        $seen = [];

        foreach (($categoria['subcategorias'] ?? []) as $subcategoria) {
            $label = $this->subcategoryLabel((string) $subcategoria);
            $labelKey = $this->norm($label);

            if ($label === '' || isset($seen[$labelKey])) {
                continue;
            }

            $seen[$labelKey] = true;
            $labels[] = $label;
        }

        if ($total <= 0) {
            return '00 - sin actividades.';
        }

        if (empty($labels)) {
            return $this->pad($total) . ' - actividades registradas.';
        }

        return $this->pad($total) . ' - ' . $this->joinLabels($labels) . '.';
    }

    protected function categoriaResumenConOtros(array $totales, string $categoriaKey, array $categoriasResiduales): string
    {
        $key = $this->norm($categoriaKey);
        $categoria = $totales[$key] ?? [
            'nombre' => $categoriaKey,
            'total' => 0,
            'subcategorias' => [],
        ];

        foreach ($categoriasResiduales as $categoriaResidual) {
            $residual = $totales[$this->norm($categoriaResidual)] ?? null;

            if (!$residual || (int) ($residual['total'] ?? 0) <= 0) {
                continue;
            }

            $categoria['total'] = (int) ($categoria['total'] ?? 0) + (int) $residual['total'];
            $categoria['subcategorias'][] = 'Otros';
        }

        return $this->categoriaResumenSimple([$key => $categoria], $categoriaKey);
    }

    protected function joinLabels(array $labels): string
    {
        $labels = array_values(array_filter(array_map(fn ($label) => $this->cleanLabelForSentence((string) $label), $labels)));

        if (empty($labels)) {
            return '';
        }

        if (count($labels) === 1) {
            return $labels[0];
        }

        $last = array_pop($labels);

        return implode(', ', $labels) . ' y ' . $last;
    }

    protected function cleanLabelForSentence(string $label): string
    {
        return trim($label, " \t\n\r\0\x0B.");
    }

    protected function periodoTexto(Carbon $inicio, Carbon $fin): string
    {
        return $this->horaTexto($inicio)
            . ' HORAS DEL '
            . $inicio->format('d/m/Y')
            . ' A LAS '
            . $this->horaTexto($fin)
            . ' HORAS DEL '
            . $fin->format('d/m/Y');
    }

    protected function totales(Carbon $inicio, Carbon $fin): array
    {
        if (!$this->tablasActividadesDisponibles()) {
            return [];
        }

        $categoriasQuery = DB::table('actividades as a')
            ->join('actividad_categorias as c', 'c.id', '=', 'a.actividad_categoria_id')
            ->where('a.unidad_org_id', self::UNIDAD_VIALIDADES_URBANAS_ID)
            ->select('c.id', 'c.nombre')
            ->selectRaw('SUM(COALESCE(a.cantidad, 0)) as total')
            ->groupBy('c.id', 'c.nombre')
            ->orderBy('c.id');

        $this->aplicarRangoActividades($categoriasQuery, $inicio, $fin);

        $categorias = $categoriasQuery->get();

        $subcategoriasQuery = DB::table('actividades as a')
            ->join('actividad_categorias as c', 'c.id', '=', 'a.actividad_categoria_id')
            ->join('actividad_subcategorias as s', 's.id', '=', 'a.actividad_subcategoria_id')
            ->where('a.unidad_org_id', self::UNIDAD_VIALIDADES_URBANAS_ID)
            ->whereNotNull('a.actividad_subcategoria_id')
            ->select([
                'c.id as categoria_id',
                's.nombre',
                'a.motivo',
                'a.observaciones',
                'a.acciones_realizadas',
                'a.narrativa',
            ])
            ->orderBy('c.id')
            ->orderBy('s.id');

        $this->aplicarRangoActividades($subcategoriasQuery, $inicio, $fin);

        $subcategorias = $subcategoriasQuery->get()
            ->groupBy('categoria_id')
            ->map(function (Collection $items) {
                $seen = [];
                $labels = [];

                foreach ($items as $item) {
                    $label = $this->subcategoriaResumenLabel($item);
                    $key = $this->norm($label);

                    if ($label === '' || isset($seen[$key])) {
                        continue;
                    }

                    $seen[$key] = true;
                    $labels[] = $label;
                }

                return $labels;
            });

        return $categorias
            ->map(function ($categoria) use ($subcategorias) {
                return [
                    'nombre' => trim((string) $categoria->nombre),
                    'total' => (int) $categoria->total,
                    'subcategorias' => $subcategorias->get($categoria->id, []),
                ];
            })
            ->sortBy(fn (array $categoria) => $this->categorySort($categoria['nombre']))
            ->values()
            ->all();
    }

    protected function subcategoriaResumenLabel($item): string
    {
        $raw = trim((string) ($item->nombre ?? ''));

        if (in_array($this->norm($raw), self::SUBCATEGORIES_AS_OTHER, true)) {
            return 'Otros';
        }

        $label = $this->subcategoryLabel($raw);

        if (!$this->esSubcategoriaOtros($raw)) {
            return $label;
        }

        $detalle = $this->detalleOtros($item);

        return $detalle !== '' ? 'Otros (' . $detalle . ')' : 'Otros';
    }

    protected function detalleOtros($item): string
    {
        foreach (['motivo', 'observaciones', 'acciones_realizadas', 'narrativa'] as $field) {
            $text = $this->limpiarTexto($item->{$field} ?? null);

            if ($text === '') {
                continue;
            }

            $text = preg_replace('/^\s*otros?\s*[:\-]?\s*/i', '', $text) ?? $text;
            $text = preg_replace('/\s+/', ' ', $text) ?? $text;
            $text = trim($text, " \t\n\r\0\x0B.:;-");

            if ($text === '') {
                continue;
            }

            if (mb_strlen($text, 'UTF-8') > 45) {
                $text = mb_substr($text, 0, 42, 'UTF-8') . '...';
            }

            return mb_strtolower($text, 'UTF-8');
        }

        return '';
    }

    protected function esSubcategoriaOtros(string $value): bool
    {
        return str_contains($this->norm($value), 'OTRO');
    }

    protected function legacySubcategorias(Collection $items): array
    {
                return $items
                    ->pluck('nombre')
                    ->map(fn ($nombre) => trim((string) $nombre))
                    ->filter()
                    ->values()
                    ->all();
    }

    protected function novedades(Carbon $inicio, Carbon $fin): array
    {
        if (!$this->tablasActividadesDisponibles()) {
            return [];
        }

        $query = DB::table('actividades as a')
            ->where('a.unidad_org_id', self::UNIDAD_VIALIDADES_URBANAS_ID)
            ->select([
                'a.id',
                'a.motivo',
                'a.narrativa',
                'a.acciones_realizadas',
                'a.observaciones',
            ])
            ->orderBy('a.fecha')
            ->orderBy('a.hora')
            ->orderBy('a.id');

        $this->aplicarRangoActividades($query, $inicio, $fin);

        $novedades = [];
        $seen = [];

        foreach ($query->get() as $actividad) {
            $texto = $this->textoNovedad($actividad);

            if ($texto === '') {
                continue;
            }

            $key = mb_strtoupper($this->sinAcentos($texto), 'UTF-8');

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $novedades[] = $texto;
        }

        return $novedades;
    }

    protected function textoNovedad($actividad): string
    {
        foreach (['observaciones', 'acciones_realizadas', 'narrativa', 'motivo'] as $field) {
            $texto = $this->limpiarTexto($actividad->{$field} ?? null);

            if ($texto !== '') {
                return $texto;
            }
        }

        return '';
    }

    protected function aplicarRangoActividades($query, Carbon $inicio, Carbon $fin): void
    {
        $inicioStr = $inicio->format('Y-m-d H:i:s');
        $finStr = $fin->format('Y-m-d H:i:s');

        $query->whereRaw(
            "TIMESTAMP(a.fecha, COALESCE(NULLIF(a.hora, ''), '00:00:00')) >= ?",
            [$inicioStr]
        )->whereRaw(
            "TIMESTAMP(a.fecha, COALESCE(NULLIF(a.hora, ''), '00:00:00')) < ?",
            [$finStr]
        );
    }

    protected function splitMessage(string $mensaje, int $maxBodyChars): array
    {
        $maxBodyChars = max(500, $maxBodyChars);
        $mensaje = trim(str_replace(["\r\n", "\r"], "\n", $mensaje));

        if ($mensaje === '') {
            return [''];
        }

        if (mb_strlen($mensaje, 'UTF-8') <= $maxBodyChars) {
            return [$mensaje];
        }

        $chunks = [];
        $current = '';
        $blocks = preg_split("/\n{2,}/", $mensaje) ?: [$mensaje];

        foreach ($blocks as $block) {
            $block = trim($block);

            if ($block === '') {
                continue;
            }

            foreach ($this->splitBlock($block, $maxBodyChars) as $piece) {
                $candidate = $current === ''
                    ? $piece
                    : $current . "\n\n" . $piece;

                if (mb_strlen($candidate, 'UTF-8') <= $maxBodyChars) {
                    $current = $candidate;
                    continue;
                }

                if ($current !== '') {
                    $chunks[] = $current;
                }

                $current = $piece;
            }
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks ?: [''];
    }

    protected function splitBlock(string $block, int $maxBodyChars): array
    {
        if (mb_strlen($block, 'UTF-8') <= $maxBodyChars) {
            return [$block];
        }

        $pieces = [];
        $current = '';
        $lines = preg_split("/\n/", $block) ?: [$block];

        foreach ($lines as $line) {
            foreach ($this->splitLine($line, $maxBodyChars) as $linePiece) {
                $candidate = $current === ''
                    ? $linePiece
                    : $current . "\n" . $linePiece;

                if (mb_strlen($candidate, 'UTF-8') <= $maxBodyChars) {
                    $current = $candidate;
                    continue;
                }

                if ($current !== '') {
                    $pieces[] = $current;
                }

                $current = $linePiece;
            }
        }

        if ($current !== '') {
            $pieces[] = $current;
        }

        return $pieces;
    }

    protected function templateParameterText(string $text): string
    {
        $text = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $text);
        $text = preg_replace('/ {2,}/', ' ', $text) ?? $text;

        return trim($text);
    }

    protected function splitLine(string $line, int $maxBodyChars): array
    {
        if (mb_strlen($line, 'UTF-8') <= $maxBodyChars) {
            return [$line];
        }

        $pieces = [];
        $remaining = trim($line);

        while (mb_strlen($remaining, 'UTF-8') > $maxBodyChars) {
            $slice = mb_substr($remaining, 0, $maxBodyChars, 'UTF-8');
            $breakAt = mb_strrpos($slice, ' ', 0, 'UTF-8');

            if ($breakAt === false || $breakAt < (int) floor($maxBodyChars * 0.6)) {
                $breakAt = $maxBodyChars;
            }

            $pieces[] = trim(mb_substr($remaining, 0, $breakAt, 'UTF-8'));
            $remaining = trim(mb_substr($remaining, $breakAt, null, 'UTF-8'));
        }

        if ($remaining !== '') {
            $pieces[] = $remaining;
        }

        return $pieces;
    }

    protected function firmaNombre(): string
    {
        $configured = trim((string) config('services.whatsapp.vialidades_urbanas.firma_nombre', ''));

        if ($configured !== '') {
            return $configured;
        }

        try {
            $subdirector = Personal::query()
                ->where('unidad_id', self::UNIDAD_VIALIDADES_URBANAS_ID)
                ->where('puesto', 'SUBDIRECTOR')
                ->where('estatus', 'ACTIVO')
                ->orderBy('id')
                ->first();

            return $subdirector ? $subdirector->nombreCompletoConGrado() : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    protected function tablasActividadesDisponibles(): bool
    {
        try {
            return Schema::hasTable('actividades')
                && Schema::hasTable('actividad_categorias')
                && Schema::hasTable('actividad_subcategorias');
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function limpiarTexto($value): string
    {
        $text = trim((string) $value);
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    protected function categoryLabel(string $value): string
    {
        $key = $this->norm($value);

        return self::CATEGORY_LABELS[$key] ?? $this->displayText($value);
    }

    protected function subcategoryLabel(string $value): string
    {
        if (preg_match('/^otros?\s*\(/iu', trim($value))) {
            return trim($value);
        }

        $key = $this->norm($value);

        return self::SUBCATEGORY_LABELS[$key] ?? $this->displayText($value);
    }

    protected function categorySort(string $value): int
    {
        $key = $this->norm($value);
        $index = array_search($key, self::CATEGORY_ORDER, true);

        return $index === false ? 999 : (int) $index;
    }

    protected function displayText(string $value): string
    {
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);

        if ($value === '') {
            return '';
        }

        $text = mb_convert_case(mb_strtolower($value, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');

        foreach ([' A ', ' De ', ' Del ', ' La ', ' Las ', ' Los ', ' Y ', ' En ', ' Con '] as $needle) {
            $text = str_replace($needle, mb_strtolower($needle, 'UTF-8'), $text);
        }

        return str_replace(['C5I', 'Ssp', 'Calea'], ['C5i', 'SSP', 'CALEA'], $text);
    }

    protected function norm(string $value): string
    {
        $value = mb_strtoupper(trim($value), 'UTF-8');
        $value = $this->sinAcentos($value);
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return $value;
    }

    protected function sinAcentos(string $value): string
    {
        return strtr($value, [
            'Á' => 'A', 'À' => 'A', 'Ä' => 'A', 'Â' => 'A',
            'É' => 'E', 'È' => 'E', 'Ë' => 'E', 'Ê' => 'E',
            'Í' => 'I', 'Ì' => 'I', 'Ï' => 'I', 'Î' => 'I',
            'Ó' => 'O', 'Ò' => 'O', 'Ö' => 'O', 'Ô' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Ü' => 'U', 'Û' => 'U',
            'Ñ' => 'N',
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n',
        ]);
    }

    protected function pad(int $value): string
    {
        return str_pad((string) $value, 2, '0', STR_PAD_LEFT);
    }

    protected function horaCorte(): array
    {
        $configured = (string) config('cortes.hora_corte_vialidades_urbanas', '17:00:00');

        if (!preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', trim($configured), $matches)) {
            return [17, 0, 0];
        }

        $hora = (int) $matches[1];
        $minuto = (int) $matches[2];
        $segundo = isset($matches[3]) ? (int) $matches[3] : 0;

        if ($hora < 0 || $hora > 23 || $minuto < 0 || $minuto > 59 || $segundo < 0 || $segundo > 59) {
            return [17, 0, 0];
        }

        return [$hora, $minuto, $segundo];
    }

    protected function horaTexto(Carbon $value): string
    {
        return $value->format('H:i');
    }

    protected function incluirNovedades(): bool
    {
        return (bool) config('services.whatsapp.vialidades_urbanas.incluir_novedades', false);
    }

    protected function timezone(): string
    {
        return (string) config('app.schedule_timezone', config('app.timezone', 'America/Mexico_City'));
    }
}
