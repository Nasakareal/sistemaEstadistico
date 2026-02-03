<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Servicio;
use App\Models\Grua;
use Carbon\Carbon;

class ServicioController extends Controller
{
    public function index(Grua $grua)
    {
        $servicios = $grua->servicios;
        return view('servicios.index', compact('grua', 'servicios'));
    }

    public function create(Grua $grua)
    {
        return view('servicios.create', compact('grua'));
    }

    public function store(Request $request, Grua $grua)
    {
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
        return view('servicios.show', compact('grua', 'servicio'));
    }

    public function edit(Grua $grua, Servicio $servicio)
    {
        return view('servicios.edit', compact('grua', 'servicio'));
    }

    public function update(Request $request, Grua $grua, Servicio $servicio)
    {
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
        $anchor = $request->query('anchor');

        if ($anchor) {
            $a = Carbon::parse($anchor, 'America/Mexico_City')->startOfDay();
        } else {
            $a = Carbon::now('America/Mexico_City')->startOfDay();
        }

        $from = $a->copy()->startOfWeek(Carbon::MONDAY);
        $to = $from->copy()->addDays(6)->endOfDay();

        $gruasSeleccionadas = $request->input('gruas');
        if (is_string($gruasSeleccionadas)) {
            $gruasSeleccionadas = array_filter(array_map('trim', explode(',', $gruasSeleccionadas)));
        }
        if (!is_array($gruasSeleccionadas)) {
            $gruasSeleccionadas = [];
        }

        $gruasCatalogo = Grua::query()
            ->select('id', 'nombre')
            ->orderBy('nombre')
            ->get();

        $query = Grua::query()
            ->select('id', 'nombre')
            ->when(!empty($gruasSeleccionadas), function ($q) use ($gruasSeleccionadas) {
                $q->whereIn('nombre', $gruasSeleccionadas);
            })
            ->with(['servicios' => function ($q) use ($from, $to) {
                $q->select('id', 'vehiculo_id', 'grua_id', 'tipo_vehiculo', 'aseguradora', 'created_at')
                    ->whereBetween('created_at', [$from->toDateTimeString(), $to->toDateTimeString()])
                    ->with(['vehiculo' => function ($v) {
                        $v->select('id', 'marca', 'modelo', 'tipo', 'linea', 'color', 'placas', 'aseguradora');
                    }]);
            }]);

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
                'servicios_count' => $servicios->count(),
                'fecha_ultimo_servicio' => optional($servicios->max('created_at'))->toDateTimeString(),
                'vehiculos' => $vehiculos,
            ];
        });

        return view('servicios.grafico', [
            'gruasCatalogo' => $gruasCatalogo,
            'gruasServicios' => $gruasServicios,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'anchor' => $a->toDateString(),
            'gruasSeleccionadas' => $gruasSeleccionadas,
        ]);
    }

    public function destroy(Grua $grua, Servicio $servicio)
    {
        $servicio->delete();
        return redirect()->route('servicios.index', $grua->id)->with('success', 'Servicio eliminado correctamente.');
    }
}
