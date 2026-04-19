<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Servicio;
use App\Models\Grua;
use App\Models\Delegacion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServicioController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {

            $usuario = Auth::user();

            if (
                !$usuario ||
                (
                    !$usuario->hasRole('Superadmin') &&
                    !in_array((int)$usuario->unidad_id, [1, 2, 3], true)
                )
            ) {
                abort(403);
            }

            return $next($request);
        });
    }

    public function index(Grua $grua)
    {
        $this->autorizarAccesoAGrua($grua);

        $servicios = $grua->servicios;
        return view('servicios.index', compact('grua', 'servicios'));
    }

    public function create(Grua $grua)
    {
        $this->autorizarAccesoAGrua($grua);

        return view('servicios.create', compact('grua'));
    }

    public function store(Request $request, Grua $grua)
    {
        $this->autorizarAccesoAGrua($grua);

        $request->validate([
            'tipo_vehiculo' => 'required|string',
            'aseguradora' => 'nullable|string',
            'descripcion' => 'nullable|string',
            'foto_vehiculo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->all();

        if ($request->hasFile('foto_vehiculo')) {
            $data['foto_vehiculo'] = $request->file('foto_vehiculo')->store('fotos_vehiculos', 'public');
        }

        $grua->servicios()->create($data);

        return redirect()->route('servicios.index', $grua->id)->with('success', 'Servicio registrado correctamente.');
    }

    public function show(Grua $grua, Servicio $servicio)
    {
        $this->autorizarAccesoAGrua($grua);

        return view('servicios.show', compact('grua', 'servicio'));
    }

    public function edit(Grua $grua, Servicio $servicio)
    {
        $this->autorizarAccesoAGrua($grua);

        return view('servicios.edit', compact('grua', 'servicio'));
    }

    public function update(Request $request, Grua $grua, Servicio $servicio)
    {
        $this->autorizarAccesoAGrua($grua);

        $request->validate([
            'tipo_vehiculo' => 'required|string',
            'aseguradora' => 'nullable|string',
            'descripcion' => 'nullable|string',
            'foto_vehiculo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->all();

        if ($request->hasFile('foto_vehiculo')) {
            $data['foto_vehiculo'] = $request->file('foto_vehiculo')->store('fotos_vehiculos', 'public');
        }

        $servicio->update($data);

        return redirect()->route('servicios.index', $grua->id)->with('success', 'Servicio actualizado correctamente.');
    }

    public function grafico(Request $request)
    {
        $usuario = Auth::user();

        $anchor = $request->query('anchor');

        if ($anchor) {
            $a = Carbon::parse($anchor, 'America/Mexico_City')->startOfDay();
        } else {
            $a = Carbon::now('America/Mexico_City')->startOfDay();
        }

        $from = $a->copy()->startOfWeek(Carbon::MONDAY);
        $to = $from->copy()->addDays(6)->endOfDay();

        $gruasSeleccionadas = $request->input('gruas', []);
        if (is_string($gruasSeleccionadas)) {
            $gruasSeleccionadas = array_filter(array_map('intval', explode(',', $gruasSeleccionadas)));
        }
        if (!is_array($gruasSeleccionadas)) {
            $gruasSeleccionadas = [];
        }
        $gruasSeleccionadas = array_values(array_unique(array_filter(array_map('intval', $gruasSeleccionadas))));

        $delegacionesSeleccionadas = $request->input('delegaciones', []);
        if (is_string($delegacionesSeleccionadas)) {
            $delegacionesSeleccionadas = array_filter(array_map('intval', explode(',', $delegacionesSeleccionadas)));
        }
        if (!is_array($delegacionesSeleccionadas)) {
            $delegacionesSeleccionadas = [];
        }
        $delegacionesSeleccionadas = array_values(array_unique(array_filter(array_map('intval', $delegacionesSeleccionadas))));

        $origen = $request->input('origen');

        if ($usuario->hasRole('Superadmin') || (int)$usuario->unidad_id === 3) {
            if (!in_array($origen, ['siniestros', 'delegaciones', 'todos'], true)) {
                $origen = 'todos';
            }
        } elseif ((int)$usuario->unidad_id === 1) {
            $origen = 'siniestros';
        } elseif ((int)$usuario->unidad_id === 2) {
            $origen = 'delegaciones';
        } else {
            abort(403);
        }

        $delegacionIdsPermitidas = $this->obtenerIdsDelegacionesPermitidas($usuario, $origen);
        $delegacionesSeleccionadas = $this->normalizarDelegacionesSeleccionadas($delegacionesSeleccionadas, $delegacionIdsPermitidas, $usuario, $origen);

        $delegacionesCatalogo = $this->obtenerDelegacionesCatalogo($usuario, $origen, $delegacionIdsPermitidas);

        $gruasCatalogo = $this->construirConsultaGruasSegunOrigen($usuario, $origen, $delegacionesSeleccionadas)
            ->select('gruas.id', 'gruas.nombre', 'gruas.direccion')
            ->orderBy('gruas.nombre')
            ->orderBy('gruas.direccion')
            ->distinct()
            ->get()
            ->map(function ($grua) {
                $grua->label = trim($grua->nombre . ' - ' . ($grua->direccion ?? ''));
                return $grua;
            });

        $query = $this->construirConsultaGruasSegunOrigen($usuario, $origen, $delegacionesSeleccionadas)
            ->select('gruas.id', 'gruas.nombre', 'gruas.direccion')
            ->when(!empty($gruasSeleccionadas), function ($q) use ($gruasSeleccionadas) {
                $q->whereIn('gruas.id', $gruasSeleccionadas);
            })
            ->with(['servicios' => function ($q) use ($from, $to) {
                $q->select('id', 'vehiculo_id', 'grua_id', 'tipo_vehiculo', 'aseguradora', 'created_at')
                    ->whereBetween('created_at', [$from->toDateTimeString(), $to->toDateTimeString()])
                    ->with(['vehiculo' => function ($v) {
                        $v->select('id', 'marca', 'modelo', 'tipo', 'linea', 'color', 'placas', 'aseguradora');
                    }]);
            }])
            ->orderBy('gruas.nombre')
            ->orderBy('gruas.direccion')
            ->distinct();

        $gruasServicios = $query->get()->map(function ($grua) {
            $servicios = $grua->servicios ?? collect();

            $vehiculos = $servicios->map(function ($s) {
                $veh = $s->vehiculo;

                $aseg = trim((string)($s->aseguradora ?? ''));
                if ($aseg === '' && $veh) {
                    $aseg = trim((string)($veh->aseguradora ?? ''));
                }

                $asegUpper = mb_strtoupper($aseg, 'UTF-8');
                $sinSeguro = ['SIN SEGURO', 'NO', 'N/A', 'NA', 'NINGUNO', 'NULL', ''];
                $tieneSeguro = ($asegUpper === '' || in_array($asegUpper, $sinSeguro, true)) ? 0 : 1;

                return [
                    'placas' => $veh ? $veh->placas : null,
                    'marca' => $veh ? $veh->marca : null,
                    'linea' => $veh ? $veh->linea : null,
                    'modelo' => $veh ? $veh->modelo : null,
                    'color' => $veh ? $veh->color : null,
                    'tipo_vehiculo' => $s->tipo_vehiculo,
                    'tipo' => $veh ? $veh->tipo : null,
                    'aseguradora' => $aseg !== '' ? $aseg : null,
                    'tiene_seguro' => $tieneSeguro,
                    'servicio_id' => $s->id,
                    'fecha_servicio' => optional($s->created_at)->toDateTimeString(),
                ];
            })->values();

            return [
                'id' => $grua->id,
                'nombre' => $grua->nombre,
                'direccion' => $grua->direccion,
                'label' => trim($grua->nombre . ' - ' . ($grua->direccion ?? '')),
                'servicios_count' => $servicios->count(),
                'fecha_ultimo_servicio' => optional($servicios->max('created_at'))->toDateTimeString(),
                'vehiculos' => $vehiculos,
            ];
        });

        return view('servicios.grafico', [
            'gruasCatalogo' => $gruasCatalogo,
            'delegacionesCatalogo' => $delegacionesCatalogo,
            'gruasServicios' => $gruasServicios,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'anchor' => $a->toDateString(),
            'gruasSeleccionadas' => $gruasSeleccionadas,
            'delegacionesSeleccionadas' => $delegacionesSeleccionadas,
            'origen' => $origen,
            'puedeFiltrarOrigen' => $usuario->hasRole('Superadmin') || (int)$usuario->unidad_id === 3,
            'puedeFiltrarDelegaciones' => in_array($origen, ['delegaciones', 'todos'], true),
        ]);
    }

    public function destroy(Grua $grua, Servicio $servicio)
    {
        $this->autorizarAccesoAGrua($grua);

        $servicio->delete();
        return redirect()->route('servicios.index', $grua->id)->with('success', 'Servicio eliminado correctamente.');
    }

    private function construirConsultaGruasSegunOrigen($usuario, string $origen, array $delegacionesSeleccionadas = [])
    {
        $query = Grua::query();

        if ($origen === 'siniestros') {
            $query->whereHas('unidades', function ($q) {
                $q->where('unidades.id', 1);
            });

            return $query;
        }

        if ($origen === 'delegaciones') {
            $delegacionIds = !empty($delegacionesSeleccionadas)
                ? $delegacionesSeleccionadas
                : $this->obtenerIdsDelegacionesPermitidas($usuario, $origen);

            if (empty($delegacionIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereHas('delegaciones', function ($q) use ($delegacionIds) {
                    $q->whereIn('delegaciones.id', $delegacionIds);
                });
            }

            return $query;
        }

        if ($origen === 'todos') {
            $delegacionIds = !empty($delegacionesSeleccionadas)
                ? $delegacionesSeleccionadas
                : $this->obtenerIdsDelegacionesPermitidas($usuario, $origen);

            $query->where(function ($q) use ($delegacionIds) {
                $q->whereHas('unidades', function ($sub) {
                    $sub->where('unidades.id', 1);
                });

                if (!empty($delegacionIds)) {
                    $q->orWhereHas('delegaciones', function ($sub) use ($delegacionIds) {
                        $sub->whereIn('delegaciones.id', $delegacionIds);
                    });
                }
            });

            return $query;
        }

        $query->whereRaw('1 = 0');

        return $query;
    }

    private function obtenerIdsDelegacionesUsuario($usuario): array
    {
        $ids = [];

        if (!empty($usuario->delegacion_id)) {
            $ids[] = (int)$usuario->delegacion_id;
        }

        $idsPivot = DB::table('delegacion_user')
            ->where('user_id', $usuario->id)
            ->pluck('delegacion_id')
            ->map(function ($id) {
                return (int)$id;
            })
            ->toArray();

        return array_values(array_unique(array_merge($ids, $idsPivot)));
    }

    private function obtenerIdsDelegacionesPermitidas($usuario, string $origen): array
    {
        if (!in_array($origen, ['delegaciones', 'todos'], true)) {
            return [];
        }

        if (
            $usuario->hasRole('Superadmin')
            || (int)$usuario->unidad_id === 3
            || ((int)$usuario->unidad_id === 2 && $usuario->hasRole('Administrador'))
        ) {
            return Delegacion::query()
                ->where('activa', 1)
                ->pluck('id')
                ->map(function ($id) {
                    return (int)$id;
                })
                ->toArray();
        }

        if ((int)$usuario->unidad_id === 2) {
            return $this->obtenerIdsDelegacionesUsuario($usuario);
        }

        return [];
    }

    private function normalizarDelegacionesSeleccionadas(array $delegacionesSeleccionadas, array $delegacionIdsPermitidas, $usuario, string $origen): array
    {
        if (!in_array($origen, ['delegaciones', 'todos'], true)) {
            return [];
        }

        if (empty($delegacionIdsPermitidas)) {
            return [];
        }

        if (empty($delegacionesSeleccionadas)) {
            if ((int)$usuario->unidad_id === 2 && !$usuario->hasRole('Superadmin')) {
                return $delegacionIdsPermitidas;
            }

            return $origen === 'delegaciones' ? $delegacionIdsPermitidas : [];
        }

        return array_values(array_intersect($delegacionesSeleccionadas, $delegacionIdsPermitidas));
    }

    private function obtenerDelegacionesCatalogo($usuario, string $origen, array $delegacionIdsPermitidas)
    {
        if (!in_array($origen, ['delegaciones', 'todos'], true) || empty($delegacionIdsPermitidas)) {
            return collect();
        }

        return Delegacion::query()
            ->select('id', 'clave', 'nombre', 'municipio')
            ->whereIn('id', $delegacionIdsPermitidas)
            ->where('activa', 1)
            ->orderBy('nombre')
            ->get();
    }

    private function autorizarAccesoAGrua(Grua $grua): void
    {
        $usuario = Auth::user();

        if ($usuario->hasRole('Superadmin') || (int)$usuario->unidad_id === 3) {
            return;
        }

        if ((int)$usuario->unidad_id === 1) {
            $permitida = $grua->unidades()->where('unidades.id', 1)->exists();

            abort_unless($permitida, 403);

            return;
        }

        if ((int)$usuario->unidad_id === 2) {
            $delegacionIds = $this->obtenerIdsDelegacionesUsuario($usuario);

            $permitida = !empty($delegacionIds)
                && $grua->delegaciones()->whereIn('delegaciones.id', $delegacionIds)->exists();

            abort_unless($permitida, 403);

            return;
        }

        abort(403);
    }
}
