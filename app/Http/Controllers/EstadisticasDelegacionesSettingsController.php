<?php

namespace App\Http\Controllers;

use App\Models\Delegacion;
use App\Models\DelegacionActividadFisica;
use App\Models\Hechos;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class EstadisticasDelegacionesSettingsController extends Controller
{
    private const UNIDAD_DELEGACIONES_ID = 2;

    public function index()
    {
        $this->ensureCanViewDelegacionesStats(request());

        return view('admin.settings.estadisticas_delegaciones.index');
    }

    public function controlHechos(Request $request)
    {
        $this->ensureCanViewDelegacionesStats($request);

        $tz = 'America/Mexico_City';
        $fechaCorte = Carbon::parse($request->input('fecha_corte', now($tz)->toDateString()), $tz)->format('Y-m-d');
        [$inicio, $fin] = $this->rangoCorte($fechaCorte);

        $estado = $request->input('estado', 'todos');
        if (!in_array($estado, ['todos', 'contados', 'falta_completar', 'sin_estadistica'], true)) {
            $estado = 'todos';
        }

        $buscar = trim((string) $request->input('buscar', ''));

        $query = Hechos::query()
            ->with(['delegacion', 'vehiculos', 'creator'])
            ->where('unidad_org_id', self::UNIDAD_DELEGACIONES_ID)
            ->where(function ($q) use ($inicio, $fin) {
                $q->where(function ($captura) use ($inicio, $fin) {
                    $captura->whereNotNull('captura_completa_at')
                        ->where('captura_completa_at', '>=', $inicio->format('Y-m-d H:i:s'))
                        ->where('captura_completa_at', '<', $fin->format('Y-m-d H:i:s'));
                })->orWhereRaw(
                    "TIMESTAMP(DATE(fecha), COALESCE(hora, '00:00:00')) >= ? AND TIMESTAMP(DATE(fecha), COALESCE(hora, '00:00:00')) < ?",
                    [$inicio->format('Y-m-d H:i:s'), $fin->format('Y-m-d H:i:s')]
                );
            });

        if ($buscar !== '') {
            $query->where(function ($q) use ($buscar) {
                $q->where('id', $buscar)
                    ->orWhere('folio_c5i', 'like', '%' . $buscar . '%')
                    ->orWhere('tipo_hecho', 'like', '%' . $buscar . '%')
                    ->orWhere('municipio', 'like', '%' . $buscar . '%')
                    ->orWhere('calle', 'like', '%' . $buscar . '%')
                    ->orWhere('colonia', 'like', '%' . $buscar . '%');
            });
        }

        $hechosDecorados = $query
            ->orderByDesc(DB::raw('COALESCE(captura_completa_at, created_at)'))
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->get()
            ->map(function (Hechos $hecho) use ($inicio, $fin, $fechaCorte) {
                return $this->decorarHechoParaControlDelegaciones($hecho, $inicio, $fin, $fechaCorte);
            });

        $resumen = [
            'total' => $hechosDecorados->count(),
            'contados' => $hechosDecorados->filter(fn ($hecho) => $hecho->control_delegaciones['se_contempla'])->count(),
            'falta_completar' => $hechosDecorados->filter(fn ($hecho) => !$hecho->control_delegaciones['captura_completa'])->count(),
            'sin_estadistica' => $hechosDecorados->filter(fn ($hecho) => !$hecho->control_delegaciones['se_contempla'])->count(),
        ];

        $nombreExcel = 'excel_delegaciones_' . $fechaCorte . '.xlsx';
        $rutaExcel = storage_path('app/cortes/excel_delegaciones/' . $nombreExcel);
        $excel = [
            'existe' => file_exists($rutaExcel),
            'archivo' => $nombreExcel,
            'url_descarga' => route('settings.estadisticas_delegaciones.excel_diario.descargar', $fechaCorte),
            'modificado' => file_exists($rutaExcel) ? Carbon::createFromTimestamp(filemtime($rutaExcel), $tz) : null,
        ];
        $puedeMoverCortes = $this->canManageDelegacionesStats($request->user());

        if ($estado !== 'todos') {
            $hechosDecorados = $hechosDecorados->filter(function ($hecho) use ($estado) {
                if ($estado === 'contados') {
                    return $hecho->control_delegaciones['se_contempla'];
                }

                if ($estado === 'falta_completar') {
                    return !$hecho->control_delegaciones['captura_completa'];
                }

                return !$hecho->control_delegaciones['se_contempla'];
            })->values();
        }

        $pagina = LengthAwarePaginator::resolveCurrentPage();
        $porPagina = 25;
        $hechos = new LengthAwarePaginator(
            $hechosDecorados->forPage($pagina, $porPagina)->values(),
            $hechosDecorados->count(),
            $porPagina,
            $pagina,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('admin.settings.estadisticas_delegaciones.control_hechos.index', compact(
            'hechos',
            'fechaCorte',
            'inicio',
            'fin',
            'estado',
            'buscar',
            'resumen',
            'excel',
            'puedeMoverCortes'
        ));
    }

    public function moverHechoCorte(Request $request, Hechos $hecho)
    {
        $this->ensureCanManageDelegacionesStats($request);

        abort_unless((int) $hecho->unidad_org_id === self::UNIDAD_DELEGACIONES_ID, 404);

        $data = $request->validate([
            'fecha_corte' => ['required', 'date_format:Y-m-d'],
        ]);

        $hecho->actualizarEstadoCaptura();
        $hecho->refresh();

        if (!$hecho->capturaCompletaCalculada()) {
            return redirect()
                ->back()
                ->with('error', 'Completa la captura del hecho antes de moverlo de corte.');
        }

        $tz = 'America/Mexico_City';
        $fechaCorte = Carbon::parse($data['fecha_corte'], $tz)->format('Y-m-d');
        $nuevoCorte = Carbon::parse($fechaCorte . ' 12:00:00', $tz);

        $hecho->update([
            'captura_completa' => true,
            'captura_completa_at' => $nuevoCorte,
        ]);

        return redirect()
            ->route('settings.estadisticas_delegaciones.control_hechos', ['fecha_corte' => $fechaCorte])
            ->with('success', 'Corte actualizado.');
    }

    public function gruasDelegaciones(Request $request)
    {
        $this->ensureCanViewDelegacionesStats($request);

        $data = $this->obtenerGruasPorDelegacion($request);

        return view('admin.settings.estadisticas_delegaciones.gruas.index', $data);
    }

    public function actividadesFisicas(Request $request)
    {
        $this->ensureCanViewDelegacionesStats($request);

        $fechaInicio = $this->normalizarFechaFiltro($request->input('fecha_inicio'));
        $fechaFin = $this->normalizarFechaFiltro($request->input('fecha_fin'));

        if ($fechaInicio && $fechaFin && $fechaInicio > $fechaFin) {
            [$fechaInicio, $fechaFin] = [$fechaFin, $fechaInicio];
        }

        $delegaciones = Delegacion::query()
            ->with('padre:id,nombre,clave')
            ->where(function ($query) {
                $query->where('activa', 1)
                    ->orWhereHas('actividadesFisicas');
            })
            ->orderBy('nombre')
            ->get(['id', 'clave', 'nombre', 'municipio', 'activa', 'delegacion_padre_id']);

        $tiposEjercicio = DelegacionActividadFisica::query()
            ->select('tipo_ejercicio')
            ->whereNotNull('tipo_ejercicio')
            ->where('tipo_ejercicio', '<>', '')
            ->distinct()
            ->orderBy('tipo_ejercicio')
            ->pluck('tipo_ejercicio')
            ->values();

        $delegacionId = (int) $request->input('delegacion_id', 0);
        if ($delegacionId > 0 && !$delegaciones->contains('id', $delegacionId)) {
            $delegacionId = 0;
        }

        $tipoEjercicio = trim((string) $request->input('tipo_ejercicio', ''));
        $buscar = trim((string) $request->input('buscar', ''));

        $query = DelegacionActividadFisica::query()
            ->with(['delegacion.padre', 'creador']);

        if ($fechaInicio) {
            $query->whereDate('fecha', '>=', $fechaInicio);
        }

        if ($fechaFin) {
            $query->whereDate('fecha', '<=', $fechaFin);
        }

        if ($delegacionId > 0) {
            $query->where('delegacion_id', $delegacionId);
        }

        if ($tipoEjercicio !== '') {
            $query->where('tipo_ejercicio', $tipoEjercicio);
        }

        if ($buscar !== '') {
            $query->where(function ($subquery) use ($buscar) {
                $subquery->where('tipo_ejercicio', 'like', '%' . $buscar . '%')
                    ->orWhere('elementos_participantes', 'like', '%' . $buscar . '%')
                    ->orWhereHas('delegacion', function ($delegacion) use ($buscar) {
                        $delegacion->where('nombre', 'like', '%' . $buscar . '%')
                            ->orWhere('clave', 'like', '%' . $buscar . '%');
                    });
            });
        }

        $resumenQuery = clone $query;
        $totalActividades = (clone $resumenQuery)->count();
        $totalElementos = (clone $resumenQuery)->sum('elementos_participantes');
        $delegacionesConActividad = (clone $resumenQuery)
            ->whereNotNull('delegacion_id')
            ->distinct('delegacion_id')
            ->count('delegacion_id');
        $actividadesConFoto = (clone $resumenQuery)
            ->whereNotNull('foto_path')
            ->count();

        $actividades = $query
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->orderByDesc('id')
            ->paginate(25)
            ->appends($request->query());

        return view('admin.settings.estadisticas_delegaciones.actividades_fisicas.index', [
            'actividades' => $actividades,
            'delegaciones' => $delegaciones,
            'tiposEjercicio' => $tiposEjercicio,
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
            'delegacionId' => $delegacionId,
            'tipoEjercicio' => $tipoEjercicio,
            'buscar' => $buscar,
            'puedeCapturar' => $this->canManageDelegacionesStats($request->user()),
            'resumen' => [
                'actividades' => $totalActividades,
                'con_foto' => $actividadesConFoto,
                'elementos' => $totalElementos,
                'delegaciones' => $delegacionesConActividad,
            ],
        ]);
    }

    public function guardarActividadFisica(Request $request)
    {
        $this->ensureCanManageDelegacionesStats($request);

        $data = $request->validate([
            'delegacion_id' => ['required', 'integer', 'exists:delegaciones,id'],
            'fecha' => ['nullable', 'date_format:Y-m-d'],
            'hora' => ['nullable', 'date_format:H:i'],
            'tipo_ejercicio' => ['required', 'string', 'max:180'],
            'elementos_participantes' => ['required', 'integer', 'min:0', 'max:999'],
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $ahora = now('America/Mexico_City');
        $foto = $request->file('foto');
        $extension = strtolower($foto->getClientOriginalExtension() ?: 'jpg');
        $nombreArchivo = $ahora->format('Ymd_His') . '_' . Str::random(10) . '.' . $extension;
        $fotoPath = $foto->storeAs('delegaciones/actividades-fisicas', $nombreArchivo, 'public');

        DelegacionActividadFisica::create([
            'delegacion_id' => (int) $data['delegacion_id'],
            'fecha' => $data['fecha'] ?: $ahora->toDateString(),
            'hora' => $data['hora'] ?: $ahora->format('H:i:s'),
            'tipo_ejercicio' => mb_strtoupper(trim((string) $data['tipo_ejercicio']), 'UTF-8'),
            'elementos_participantes' => (int) $data['elementos_participantes'],
            'foto_path' => $fotoPath,
            'foto_nombre_original' => $foto->getClientOriginalName(),
            'foto_hash' => hash_file('sha256', $foto->getRealPath()),
            'created_by' => optional($request->user())->id,
            'updated_by' => optional($request->user())->id,
        ]);

        return redirect()
            ->route('settings.estadisticas_delegaciones.actividades_fisicas')
            ->with('success', 'Actividad física registrada.');
    }

    public function exportarGruasDelegaciones(Request $request, string $formato)
    {
        $this->ensureCanViewDelegacionesStats($request);

        $data = $this->obtenerGruasPorDelegacion($request);

        if ($formato === 'excel') {
            return $this->descargarGruasDelegacionesExcel($data);
        }

        if ($formato === 'pdf') {
            $pdf = Pdf::loadView('admin.settings.estadisticas_delegaciones.gruas.pdf', $data)
                ->setPaper('letter', 'landscape');

            return $pdf->download('gruas_delegaciones_' . now('America/Mexico_City')->format('Ymd_His') . '.pdf');
        }

        abort(404);
    }

    public function excelDiario()
    {
        $this->ensureCanViewDelegacionesStats(request());

        $disk = Storage::disk('local');
        $directorio = 'cortes/excel_delegaciones';

        if (!$disk->exists($directorio)) {
            $disk->makeDirectory($directorio);
        }

        $cortes = collect($disk->files($directorio))
            ->filter(function ($file) {
                return preg_match('/excel_delegaciones_\d{4}-\d{2}-\d{2}\.xlsx$/', basename($file));
            })
            ->map(function ($file) {
                $nombre = basename($file);

                preg_match('/excel_delegaciones_(\d{4}-\d{2}-\d{2})\.xlsx$/', $nombre, $matches);

                return [
                    'archivo' => $nombre,
                    'ruta' => $file,
                    'fecha' => $matches[1] ?? null,
                    'url_descarga' => route('settings.estadisticas_delegaciones.excel_diario.descargar', $matches[1] ?? null),
                ];
            })
            ->filter(fn ($item) => !empty($item['fecha']))
            ->sortByDesc('fecha')
            ->values();

        return view('admin.settings.estadisticas_delegaciones.excel_diario.index', compact('cortes'));
    }

    public function descargarExcelDiario(string $fecha)
    {
        $this->ensureCanViewDelegacionesStats(request());

        $nombreArchivo = 'excel_delegaciones_' . $fecha . '.xlsx';
        $ruta = storage_path('app/cortes/excel_delegaciones/' . $nombreArchivo);

        abort_unless(file_exists($ruta), 404);

        return response()->download(
            $ruta,
            $nombreArchivo,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    public function excelMensual()
    {
        $this->ensureCanViewDelegacionesStats(request());

        $disk = Storage::disk('local');
        $directorio = 'cortes/excel_delegaciones_mensual';

        if (!$disk->exists($directorio)) {
            $disk->makeDirectory($directorio);
        }

        $cortes = collect($disk->files($directorio))
            ->filter(function ($file) {
                return preg_match('/excel_delegaciones_\d{4}-\d{2}\.xlsx$/', basename($file));
            })
            ->map(function ($file) {
                $nombre = basename($file);

                preg_match('/excel_delegaciones_(\d{4}-\d{2})\.xlsx$/', $nombre, $matches);

                return [
                    'archivo' => $nombre,
                    'ruta' => $file,
                    'fecha' => $matches[1] ?? null,
                    'url_descarga' => route('settings.estadisticas_delegaciones.excel_mensual.descargar', $matches[1] ?? null),
                ];
            })
            ->filter(fn ($item) => !empty($item['fecha']))
            ->sortByDesc('fecha')
            ->values();

        return view('admin.settings.estadisticas_delegaciones.excel_mensual.index', compact('cortes'));
    }

    public function descargarExcelMensual(string $fecha)
    {
        $this->ensureCanViewDelegacionesStats(request());

        $nombreArchivo = 'excel_delegaciones_' . $fecha . '.xlsx';
        $ruta = storage_path('app/cortes/excel_delegaciones_mensual/' . $nombreArchivo);

        abort_unless(file_exists($ruta), 404);

        return response()->download(
            $ruta,
            $nombreArchivo,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    private function obtenerGruasPorDelegacion(Request $request): array
    {
        $buscar = trim((string) $request->input('buscar', ''));
        $incluirInactivas = $request->boolean('incluir_inactivas');
        $buscarNormalizado = $this->normalizarTexto($buscar);

        $delegaciones = Delegacion::query()
            ->with([
                'padre:id,nombre,clave,municipio',
                'gruas' => function ($query) {
                    $query
                        ->select('gruas.id', 'gruas.nombre', 'gruas.direccion', 'gruas.ubicacion_corralon', 'gruas.telefono', 'gruas.email')
                        ->orderBy('gruas.nombre');
                },
            ])
            ->when(!$incluirInactivas, fn ($query) => $query->where('activa', 1))
            ->get(['id', 'clave', 'nombre', 'municipio', 'activa', 'delegacion_padre_id'])
            ->map(function (Delegacion $delegacion) use ($buscarNormalizado) {
                $regional = $delegacion->padre ?: $delegacion;
                $textoDelegacion = $this->normalizarTexto(implode(' ', [
                    $regional->nombre ?? '',
                    $regional->clave ?? '',
                    $delegacion->nombre ?? '',
                    $delegacion->clave ?? '',
                    $delegacion->municipio ?? '',
                ]));

                $coincideDelegacion = $buscarNormalizado === '' || str_contains($textoDelegacion, $buscarNormalizado);

                $gruas = $delegacion->gruas
                    ->filter(function ($grua) use ($buscarNormalizado, $coincideDelegacion) {
                        if ($buscarNormalizado === '' || $coincideDelegacion) {
                            return true;
                        }

                        return str_contains($this->normalizarTexto(implode(' ', [
                            $grua->nombre ?? '',
                            $grua->direccion ?? '',
                            $grua->ubicacion_corralon ?? '',
                            $grua->telefono ?? '',
                            $grua->email ?? '',
                        ])), $buscarNormalizado);
                    })
                    ->map(fn ($grua) => [
                        'id' => (int) $grua->id,
                        'nombre' => $grua->nombre,
                        'direccion' => $grua->direccion,
                        'ubicacion_corralon' => $grua->ubicacion_corralon,
                        'telefono' => $grua->telefono,
                        'email' => $grua->email,
                    ])
                    ->values();

                if ($buscarNormalizado !== '' && !$coincideDelegacion && $gruas->isEmpty()) {
                    return null;
                }

                return [
                    'id' => (int) $delegacion->id,
                    'regional' => $regional->nombre,
                    'delegacion' => $delegacion->nombre,
                    'clave' => $delegacion->clave,
                    'municipio' => $delegacion->municipio,
                    'activa' => (bool) $delegacion->activa,
                    'gruas' => $gruas,
                    'total_gruas' => $gruas->count(),
                ];
            })
            ->filter()
            ->sortBy(fn (array $delegacion) => $this->normalizarTexto(
                ($delegacion['regional'] ?? '') . ' ' . ($delegacion['delegacion'] ?? '')
            ))
            ->values();

        $rows = $delegaciones
            ->flatMap(function (array $delegacion) {
                if ($delegacion['gruas']->isEmpty()) {
                    return [[
                        'regional' => $delegacion['regional'],
                        'delegacion' => $delegacion['delegacion'],
                        'clave' => $delegacion['clave'],
                        'municipio' => $delegacion['municipio'],
                        'grua' => null,
                    ]];
                }

                return $delegacion['gruas']->map(fn (array $grua) => [
                    'regional' => $delegacion['regional'],
                    'delegacion' => $delegacion['delegacion'],
                    'clave' => $delegacion['clave'],
                    'municipio' => $delegacion['municipio'],
                    'grua' => $grua,
                ]);
            })
            ->values();

        return [
            'delegaciones' => $delegaciones,
            'rows' => $rows,
            'buscar' => $buscar,
            'incluirInactivas' => $incluirInactivas,
            'resumen' => [
                'delegaciones' => $delegaciones->count(),
                'gruas_asignadas' => $rows
                    ->pluck('grua.id')
                    ->filter()
                    ->unique()
                    ->count(),
                'relaciones' => $rows->filter(fn ($row) => !empty($row['grua']))->count(),
                'sin_gruas' => $delegaciones->filter(fn ($delegacion) => $delegacion['gruas']->isEmpty())->count(),
            ],
        ];
    }

    private function descargarGruasDelegacionesExcel(array $data)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('GRUAS DELEGACIONES');

        $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A1', 'GRUAS POR DELEGACION');
        $sheet->setCellValue('A2', 'Generado: ' . now('America/Mexico_City')->format('d/m/Y H:i'));

        $headers = [
            'Regional',
            'Delegacion',
            'Clave',
            'Municipio',
            'Grua',
            'Domicilio',
            'Ubicacion corralon',
            'Telefono',
            'Correo',
        ];

        $sheet->fromArray($headers, null, 'A4');

        $rowNumber = 5;
        foreach ($data['rows'] as $row) {
            $grua = $row['grua'] ?? [];

            $sheet->fromArray([
                $row['regional'] ?? '',
                $row['delegacion'] ?? '',
                $row['clave'] ?? '',
                $row['municipio'] ?? '',
                $grua['nombre'] ?? 'SIN GRUA ASIGNADA',
                $grua['direccion'] ?? '',
                $grua['ubicacion_corralon'] ?? '',
                $grua['telefono'] ?? '',
                $grua['email'] ?? '',
            ], null, 'A' . $rowNumber);

            $rowNumber++;
        }

        $lastRow = max(4, $rowNumber - 1);

        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A4:I4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2F75B5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle("A4:I{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'B7B7B7'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_TOP,
                'wrapText' => true,
            ],
        ]);

        foreach (range('A', 'I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->freezePane('A5');
        $sheet->setAutoFilter("A4:I{$lastRow}");

        $tempPath = storage_path('app/temp_gruas_delegaciones_' . now('America/Mexico_City')->format('Ymd_His') . '.xlsx');

        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0775, true);
        }

        (new Xlsx($spreadsheet))->save($tempPath);

        return response()
            ->download(
                $tempPath,
                'gruas_delegaciones_' . now('America/Mexico_City')->format('Ymd_His') . '.xlsx',
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            )
            ->deleteFileAfterSend(true);
    }

    private function ensureCanManageDelegacionesStats(Request $request): void
    {
        if (!$this->canManageDelegacionesStats($request->user())) {
            abort(403);
        }
    }

    private function ensureCanViewDelegacionesStats(Request $request): void
    {
        if (!$this->canViewDelegacionesStats($request->user())) {
            abort(403);
        }
    }

    private function canViewDelegacionesStats($user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->hasRole('Superadmin')) {
            return true;
        }

        if ((int) ($user->unidad_id ?? 0) === 3) {
            return true;
        }

        return $this->canManageDelegacionesStats($user);
    }

    private function canManageDelegacionesStats($user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->hasRole('Superadmin')) {
            return true;
        }

        if (
            (int) ($user->unidad_id ?? 0) === self::UNIDAD_DELEGACIONES_ID
            && ($user->hasRole('Administrador') || $user->hasRole('Subdirector'))
        ) {
            return true;
        }

        return false;
    }

    private function decorarHechoParaControlDelegaciones(Hechos $hecho, Carbon $inicio, Carbon $fin, string $fechaCorte): Hechos
    {
        $capturaAt = $hecho->captura_completa_at
            ? Carbon::parse($hecho->captura_completa_at, 'America/Mexico_City')
            : null;
        $fechaHoraHecho = $this->fechaHoraHecho($hecho);

        $enCorteCaptura = $capturaAt
            && $capturaAt->greaterThanOrEqualTo($inicio)
            && $capturaAt->lessThan($fin)
            && (bool) $hecho->captura_completa;

        $enCorteHecho = $fechaHoraHecho
            && $fechaHoraHecho->greaterThanOrEqualTo($inicio)
            && $fechaHoraHecho->lessThan($fin);

        $estadisticas = [];

        if ($enCorteCaptura) {
            if ((int) ($hecho->checaron_antecedentes ?? 0) === 1) {
                $estadisticas[] = 'Control vehicular: revisión de antecedentes';
                $estadisticas[] = 'Control aseguramientos: consulta de antecedentes';
            }

            if ((int) ($hecho->vehiculos_mp ?? 0) > 0 || !empty($hecho->oficio_mp)) {
                $estadisticas[] = 'Control vehicular: puestos a disposición del MP';
            }

            if ((int) ($hecho->personas_mp ?? 0) > 0) {
                $estadisticas[] = 'Control aseguramientos: personas al MP';
            }

            $situacion = mb_strtoupper(trim((string) ($hecho->situacion ?? '')));
            if ($situacion !== '') {
                $estadisticas[] = 'Hechos de tránsito: ' . mb_strtolower($situacion, 'UTF-8');
            }

            if (!empty($hecho->tipo_hecho)) {
                $estadisticas[] = 'Tipo de hecho: ' . $hecho->tipo_hecho;
            }

            if ($hecho->vehiculos->isNotEmpty()) {
                $estadisticas[] = 'Clasificación de vehículos involucrados';
            }
        }

        $vehiculosCorralon = $hecho->vehiculos->filter(function ($vehiculo) {
            return $this->vehiculoTieneCorralon($vehiculo);
        })->count();

        if ($enCorteCaptura && $vehiculosCorralon > 0) {
            $estadisticas[] = 'Control vehicular: corralón por hechos de tránsito';
        }

        $estadisticas = array_values(array_unique($estadisticas));
        $capturaCompleta = $hecho->capturaCompletaCalculada();

        $hecho->control_delegaciones = [
            'fecha_corte' => $fechaCorte,
            'captura_completa' => $capturaCompleta,
            'faltantes' => $capturaCompleta ? [] : $hecho->faltantesCapturaTexto(),
            'estadisticas' => $estadisticas,
            'se_contempla' => !empty($estadisticas),
            'captura_at' => $capturaAt,
            'corte_actual' => $capturaAt ? $this->fechaCorteDesdeTimestamp($capturaAt) : null,
            'evento_at' => $fechaHoraHecho,
            'en_corte_hecho' => $enCorteHecho,
            'en_corte_captura' => $enCorteCaptura,
            'vehiculos_corralon' => $vehiculosCorralon,
        ];

        return $hecho;
    }

    private function rangoCorte(string $fecha): array
    {
        $tz = 'America/Mexico_City';
        $horaCorte = config('cortes.hora_corte_delegaciones', '17:00:00');
        $fin = Carbon::parse($fecha . ' ' . $horaCorte, $tz);

        return [$fin->copy()->subDay(), $fin];
    }

    private function fechaHoraHecho(Hechos $hecho): ?Carbon
    {
        if (empty($hecho->fecha)) {
            return null;
        }

        $tz = 'America/Mexico_City';
        $fecha = $hecho->fecha instanceof \DateTimeInterface
            ? Carbon::instance($hecho->fecha)->timezone($tz)->toDateString()
            : Carbon::parse((string) $hecho->fecha, $tz)->toDateString();
        $hora = $this->normalizarHoraHecho($hecho->hora ?? null);

        return Carbon::parse($fecha . ' ' . $hora, $tz);
    }

    private function normalizarHoraHecho($hora): string
    {
        if ($hora instanceof \DateTimeInterface) {
            return Carbon::instance($hora)->format('H:i:s');
        }

        $hora = trim((string) $hora);

        if ($hora === '') {
            return '00:00:00';
        }

        if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $hora)) {
            return strlen($hora) === 5 ? $hora . ':00' : $hora;
        }

        return Carbon::parse($hora, 'America/Mexico_City')->format('H:i:s');
    }

    private function fechaCorteDesdeTimestamp(Carbon $fecha): string
    {
        $horaCorte = config('cortes.hora_corte_delegaciones', '17:00:00');

        return $fecha->format('H:i:s') >= $horaCorte
            ? $fecha->copy()->addDay()->toDateString()
            : $fecha->toDateString();
    }

    private function vehiculoTieneCorralon($vehiculo): bool
    {
        if (!empty($vehiculo->grua_id)) {
            return true;
        }

        $corralon = $this->normalizarTexto((string) ($vehiculo->corralon ?? ''));

        if ($corralon === '') {
            return false;
        }

        return !in_array($corralon, [
            'N/A',
            'NA',
            'NO',
            'NO APLICA',
            'NO SE UTILIZA',
            'NO SE UTILIZO',
            'NINGUNO',
            'NULL',
            'O',
            'SIN CORRALON',
            'SIN DATO',
        ], true);
    }

    private function normalizarTexto(string $texto): string
    {
        $texto = mb_strtoupper(trim($texto), 'UTF-8');

        return strtr($texto, [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ü' => 'U',
            'Ñ' => 'N',
        ]);
    }

    private function normalizarFechaFiltro($fecha): ?string
    {
        $fecha = trim((string) $fecha);

        if ($fecha === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $fecha, 'America/Mexico_City')->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
