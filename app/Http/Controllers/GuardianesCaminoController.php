<?php

namespace App\Http\Controllers;

use App\Models\Operativo;
use App\Models\OperativoCatalogo;
use App\Models\OperativoDispositivo;
use App\Models\OperativoDispositivoCatalogo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GuardianesCaminoController extends Controller
{
    public function index(Request $request)
    {
        $query = Operativo::query()
            ->with(['catalogo', 'unidad', 'delegacion', 'destacamento', 'creador'])
            ->whereHas('catalogo', function ($q) {
                $q->where('slug', 'guardianes-del-camino');
            })
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->orderByDesc('id');

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha', '>=', $request->fecha_inicio);
        }

        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha', '<=', $request->fecha_fin);
        }

        if ($request->filled('unidad_org_id')) {
            $query->where('unidad_org_id', $request->unidad_org_id);
        }

        if ($request->filled('delegacion_id')) {
            $query->where('delegacion_id', $request->delegacion_id);
        }

        if ($request->filled('destacamento_id')) {
            $query->where('destacamento_id', $request->destacamento_id);
        }

        $operativos = $query->paginate(20)->withQueryString();

        return view('guardianes_camino.index', compact('operativos'));
    }

    public function create()
    {
        $catalogo = OperativoCatalogo::where('slug', 'guardianes-del-camino')->firstOrFail();

        return view('guardianes_camino.create', compact('catalogo'));
    }

    public function store(Request $request)
    {
        $catalogo = OperativoCatalogo::where('slug', 'guardianes-del-camino')->firstOrFail();

        $data = $request->validate([
            'fecha' => ['required', 'date'],
            'hora' => ['nullable', 'date_format:H:i'],
            'unidad_org_id' => ['required', 'integer'],
            'delegacion_id' => ['nullable', 'integer'],
            'destacamento_id' => ['nullable', 'integer'],
            'lugar' => ['nullable', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $operativo = new Operativo();
        $operativo->captura_uuid = (string) Str::uuid();
        $operativo->fecha = $data['fecha'];
        $operativo->hora = $data['hora'] ?? null;
        $operativo->operativo_catalogo_id = $catalogo->id;
        $operativo->unidad_org_id = $data['unidad_org_id'];
        $operativo->delegacion_id = $data['delegacion_id'] ?? null;
        $operativo->destacamento_id = $data['destacamento_id'] ?? null;
        $operativo->lugar = $data['lugar'] ?? null;
        $operativo->descripcion = $data['descripcion'] ?? null;
        $operativo->observaciones = $data['observaciones'] ?? null;
        $operativo->created_by = Auth::id();
        $operativo->updated_by = Auth::id();
        $operativo->save();

        return redirect()
            ->route('guardianes_camino.show', $operativo->id)
            ->with('success', 'Operativo Guardianes del Camino creado correctamente.');
    }

    public function show($operativo)
    {
        $operativo = Operativo::with([
                'catalogo',
                'unidad',
                'delegacion',
                'destacamento',
                'creador',
                'dispositivos.catalogo',
                'dispositivos.destacamento',
                'dispositivos.usuario',
                'dispositivos.fotos',
            ])
            ->whereHas('catalogo', function ($q) {
                $q->where('slug', 'guardianes-del-camino');
            })
            ->findOrFail($operativo);

        $resumen = OperativoDispositivo::query()
            ->select(
                'operativo_dispositivo_catalogo_id',
                DB::raw('SUM(cantidad) as total_cantidad'),
                DB::raw('SUM(vehiculos_inspeccionados) as total_vehiculos_inspeccionados'),
                DB::raw('SUM(personas_inspeccionadas) as total_personas_inspeccionadas'),
                DB::raw('SUM(vehiculos_impactados) as total_vehiculos_impactados'),
                DB::raw('SUM(personas_impactadas) as total_personas_impactadas'),
                DB::raw('SUM(estado_fuerza_participante) as total_estado_fuerza_participante'),
                DB::raw('SUM(kilometros_recorridos) as total_kilometros_recorridos'),
                DB::raw('SUM(acompanamientos) as total_acompanamientos'),
                DB::raw('SUM(abanderamientos) as total_abanderamientos'),
                DB::raw('SUM(auxilios_viales) as total_auxilios_viales'),
                DB::raw('SUM(prox_empresas) as total_prox_empresas'),
                DB::raw('SUM(prox_tiendas_conveniencia) as total_prox_tiendas_conveniencia'),
                DB::raw('SUM(prox_escuelas) as total_prox_escuelas'),
                DB::raw('SUM(prox_hospitales) as total_prox_hospitales'),
                DB::raw('SUM(antecedentes_personas) as total_antecedentes_personas'),
                DB::raw('SUM(antecedentes_vehiculos) as total_antecedentes_vehiculos'),
                DB::raw('SUM(antecedentes_motos) as total_antecedentes_motos'),
                DB::raw('SUM(antecedentes_camiones) as total_antecedentes_camiones'),
                DB::raw('SUM(puestas_disposicion) as total_puestas_disposicion'),
                DB::raw('SUM(vehiculos_recuperados) as total_vehiculos_recuperados'),
                DB::raw('SUM(armas_aseguradas) as total_armas_aseguradas'),
                DB::raw('SUM(mercancia_recuperada) as total_mercancia_recuperada'),
                DB::raw('SUM(decomiso_drogas) as total_decomiso_drogas')
            )
            ->where('operativo_id', $operativo->id)
            ->groupBy('operativo_dispositivo_catalogo_id')
            ->with('catalogo')
            ->get();

        return view('guardianes_camino.show', compact('operativo', 'resumen'));
    }

    public function edit($operativo)
    {
        $operativo = Operativo::whereHas('catalogo', function ($q) {
                $q->where('slug', 'guardianes-del-camino');
            })
            ->findOrFail($operativo);

        return view('guardianes_camino.edit', compact('operativo'));
    }

    public function update(Request $request, $operativo)
    {
        $operativo = Operativo::whereHas('catalogo', function ($q) {
                $q->where('slug', 'guardianes-del-camino');
            })
            ->findOrFail($operativo);

        $data = $request->validate([
            'fecha' => ['required', 'date'],
            'hora' => ['nullable', 'date_format:H:i'],
            'unidad_org_id' => ['required', 'integer'],
            'delegacion_id' => ['nullable', 'integer'],
            'destacamento_id' => ['nullable', 'integer'],
            'lugar' => ['nullable', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $operativo->fecha = $data['fecha'];
        $operativo->hora = $data['hora'] ?? null;
        $operativo->unidad_org_id = $data['unidad_org_id'];
        $operativo->delegacion_id = $data['delegacion_id'] ?? null;
        $operativo->destacamento_id = $data['destacamento_id'] ?? null;
        $operativo->lugar = $data['lugar'] ?? null;
        $operativo->descripcion = $data['descripcion'] ?? null;
        $operativo->observaciones = $data['observaciones'] ?? null;
        $operativo->updated_by = Auth::id();
        $operativo->save();

        return redirect()
            ->route('guardianes_camino.show', $operativo->id)
            ->with('success', 'Operativo actualizado correctamente.');
    }

    public function destroy($operativo)
    {
        $operativo = Operativo::whereHas('catalogo', function ($q) {
                $q->where('slug', 'guardianes-del-camino');
            })
            ->findOrFail($operativo);

        $operativo->delete();

        return redirect()
            ->route('guardianes_camino.index')
            ->with('success', 'Operativo eliminado correctamente.');
    }

    public function resumen($operativo)
    {
        $operativo = Operativo::with(['catalogo', 'unidad', 'delegacion', 'destacamento'])
            ->whereHas('catalogo', function ($q) {
                $q->where('slug', 'guardianes-del-camino');
            })
            ->findOrFail($operativo);

        $resumen = OperativoDispositivo::query()
            ->select(
                'operativo_dispositivo_catalogo_id',
                DB::raw('SUM(cantidad) as total_cantidad'),
                DB::raw('SUM(vehiculos_inspeccionados) as total_vehiculos_inspeccionados'),
                DB::raw('SUM(personas_inspeccionadas) as total_personas_inspeccionadas'),
                DB::raw('SUM(vehiculos_impactados) as total_vehiculos_impactados'),
                DB::raw('SUM(personas_impactadas) as total_personas_impactadas'),
                DB::raw('SUM(estado_fuerza_participante) as total_estado_fuerza_participante'),
                DB::raw('SUM(kilometros_recorridos) as total_kilometros_recorridos'),
                DB::raw('SUM(acompanamientos) as total_acompanamientos'),
                DB::raw('SUM(abanderamientos) as total_abanderamientos'),
                DB::raw('SUM(auxilios_viales) as total_auxilios_viales'),
                DB::raw('SUM(prox_empresas) as total_prox_empresas'),
                DB::raw('SUM(prox_tiendas_conveniencia) as total_prox_tiendas_conveniencia'),
                DB::raw('SUM(prox_escuelas) as total_prox_escuelas'),
                DB::raw('SUM(prox_hospitales) as total_prox_hospitales'),
                DB::raw('SUM(antecedentes_personas) as total_antecedentes_personas'),
                DB::raw('SUM(antecedentes_vehiculos) as total_antecedentes_vehiculos'),
                DB::raw('SUM(antecedentes_motos) as total_antecedentes_motos'),
                DB::raw('SUM(antecedentes_camiones) as total_antecedentes_camiones'),
                DB::raw('SUM(puestas_disposicion) as total_puestas_disposicion'),
                DB::raw('SUM(vehiculos_recuperados) as total_vehiculos_recuperados'),
                DB::raw('SUM(armas_aseguradas) as total_armas_aseguradas'),
                DB::raw('SUM(mercancia_recuperada) as total_mercancia_recuperada'),
                DB::raw('SUM(decomiso_drogas) as total_decomiso_drogas')
            )
            ->where('operativo_id', $operativo->id)
            ->groupBy('operativo_dispositivo_catalogo_id')
            ->with('catalogo')
            ->get();

        $totalesGenerales = OperativoDispositivo::query()
            ->select(
                DB::raw('SUM(vehiculos_inspeccionados) as vehiculos_inspeccionados'),
                DB::raw('SUM(personas_inspeccionadas) as personas_inspeccionadas'),
                DB::raw('SUM(vehiculos_impactados) as vehiculos_impactados'),
                DB::raw('SUM(personas_impactadas) as personas_impactadas'),
                DB::raw('SUM(antecedentes_personas) as antecedentes_personas'),
                DB::raw('SUM(antecedentes_vehiculos) as antecedentes_vehiculos'),
                DB::raw('SUM(antecedentes_motos) as antecedentes_motos'),
                DB::raw('SUM(antecedentes_camiones) as antecedentes_camiones'),
                DB::raw('SUM(puestas_disposicion) as puestas_disposicion'),
                DB::raw('SUM(vehiculos_recuperados) as vehiculos_recuperados'),
                DB::raw('SUM(armas_aseguradas) as armas_aseguradas'),
                DB::raw('SUM(mercancia_recuperada) as mercancia_recuperada'),
                DB::raw('SUM(decomiso_drogas) as decomiso_drogas')
            )
            ->where('operativo_id', $operativo->id)
            ->first();

        return view('guardianes_camino.resumen', compact('operativo', 'resumen', 'totalesGenerales'));
    }

    public function whatsapp($operativo)
    {
        $operativo = Operativo::with('catalogo')
            ->whereHas('catalogo', function ($q) {
                $q->where('slug', 'guardianes-del-camino');
            })
            ->findOrFail($operativo);

        return redirect()
            ->route('guardianes_camino.resumen', $operativo->id)
            ->with('success', 'La salida para WhatsApp todavía no está implementada.');
    }
}
