<?php

namespace App\Http\Controllers;

use App\Models\Hechos;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EstadisticasDelegacionesSettingsController extends Controller
{
    private const UNIDAD_DELEGACIONES_ID = 2;

    public function index()
    {
        return view('admin.settings.estadisticas_delegaciones.index');
    }

    public function controlHechos(Request $request)
    {
        $this->ensureCanManageDelegacionesStats($request);

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
                    "TIMESTAMP(fecha, COALESCE(hora, '00:00:00')) >= ? AND TIMESTAMP(fecha, COALESCE(hora, '00:00:00')) < ?",
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
            'resumen'
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

    public function excelDiario()
    {
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
        $nombreArchivo = 'excel_delegaciones_' . $fecha . '.xlsx';
        $ruta = storage_path('app/cortes/excel_delegaciones_mensual/' . $nombreArchivo);

        abort_unless(file_exists($ruta), 404);

        return response()->download(
            $ruta,
            $nombreArchivo,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    private function ensureCanManageDelegacionesStats(Request $request): void
    {
        $user = $request->user();

        if (!$user) {
            abort(403);
        }

        if ($user->hasRole('Superadmin')) {
            return;
        }

        if (
            (int) ($user->unidad_id ?? 0) === self::UNIDAD_DELEGACIONES_ID
            && ($user->hasRole('Administrador') || $user->hasRole('Subdirector'))
        ) {
            return;
        }

        abort(403);
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

        if ($enCorteHecho && $vehiculosCorralon > 0) {
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

        $hora = $hecho->hora ?: '00:00:00';

        return Carbon::parse($hecho->fecha . ' ' . $hora, 'America/Mexico_City');
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
}
