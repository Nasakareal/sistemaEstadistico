<?php

namespace App\Http\Controllers;

use App\Models\Operativo;
use App\Models\OperativoDispositivo;
use App\Models\OperativoDispositivoCatalogo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuardianesCaminoDispositivoController extends Controller
{
    public function create($operativo)
    {
        $operativo = Operativo::with('catalogo')
            ->whereHas('catalogo', function ($q) {
                $q->where('slug', 'guardianes-del-camino');
            })
            ->findOrFail($operativo);

        $catalogos = OperativoDispositivoCatalogo::query()
            ->where('activo', 1)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        return view('guardianes_camino.dispositivos.create', compact('operativo', 'catalogos'));
    }

    public function store(Request $request, $operativo)
    {
        $operativo = Operativo::with('catalogo')
            ->whereHas('catalogo', function ($q) {
                $q->where('slug', 'guardianes-del-camino');
            })
            ->findOrFail($operativo);

        $data = $request->validate([
            'operativo_dispositivo_catalogo_id' => ['required', 'integer', 'exists:operativo_dispositivo_catalogos,id'],
            'fecha' => ['required', 'date'],
            'hora' => ['nullable', 'date_format:H:i'],
            'unidad_org_id' => ['required', 'integer'],
            'delegacion_id' => ['nullable', 'integer'],
            'destacamento_id' => ['nullable', 'integer'],
            'lugar' => ['nullable', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'cantidad' => ['nullable', 'integer', 'min:0'],
            'vehiculos_inspeccionados' => ['nullable', 'integer', 'min:0'],
            'personas_inspeccionadas' => ['nullable', 'integer', 'min:0'],
            'vehiculos_impactados' => ['nullable', 'integer', 'min:0'],
            'personas_impactadas' => ['nullable', 'integer', 'min:0'],
            'estado_fuerza_participante' => ['nullable', 'integer', 'min:0'],
            'kilometros_recorridos' => ['nullable', 'numeric', 'min:0'],
            'crps_participantes' => ['nullable', 'string'],
            'acompanamientos' => ['nullable', 'integer', 'min:0'],
            'abanderamientos' => ['nullable', 'integer', 'min:0'],
            'auxilios_viales' => ['nullable', 'integer', 'min:0'],
            'prox_empresas' => ['nullable', 'integer', 'min:0'],
            'prox_tiendas_conveniencia' => ['nullable', 'integer', 'min:0'],
            'prox_escuelas' => ['nullable', 'integer', 'min:0'],
            'prox_hospitales' => ['nullable', 'integer', 'min:0'],
            'antecedentes_personas' => ['nullable', 'integer', 'min:0'],
            'antecedentes_vehiculos' => ['nullable', 'integer', 'min:0'],
            'antecedentes_motos' => ['nullable', 'integer', 'min:0'],
            'antecedentes_camiones' => ['nullable', 'integer', 'min:0'],
            'puestas_disposicion' => ['nullable', 'integer', 'min:0'],
            'vehiculos_recuperados' => ['nullable', 'integer', 'min:0'],
            'armas_aseguradas' => ['nullable', 'integer', 'min:0'],
            'mercancia_recuperada' => ['nullable', 'integer', 'min:0'],
            'decomiso_drogas' => ['nullable', 'integer', 'min:0'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $dispositivo = new OperativoDispositivo();
        $dispositivo->operativo_id = $operativo->id;
        $dispositivo->operativo_dispositivo_catalogo_id = $data['operativo_dispositivo_catalogo_id'];
        $dispositivo->fecha = $data['fecha'];
        $dispositivo->hora = $data['hora'] ?? null;
        $dispositivo->unidad_org_id = $data['unidad_org_id'];
        $dispositivo->delegacion_id = $data['delegacion_id'] ?? null;
        $dispositivo->destacamento_id = $data['destacamento_id'] ?? null;
        $dispositivo->user_id = Auth::id();
        $dispositivo->lugar = $data['lugar'] ?? null;
        $dispositivo->descripcion = $data['descripcion'] ?? null;
        $dispositivo->cantidad = $data['cantidad'] ?? 0;
        $dispositivo->vehiculos_inspeccionados = $data['vehiculos_inspeccionados'] ?? 0;
        $dispositivo->personas_inspeccionadas = $data['personas_inspeccionadas'] ?? 0;
        $dispositivo->vehiculos_impactados = $data['vehiculos_impactados'] ?? 0;
        $dispositivo->personas_impactadas = $data['personas_impactadas'] ?? 0;
        $dispositivo->estado_fuerza_participante = $data['estado_fuerza_participante'] ?? 0;
        $dispositivo->kilometros_recorridos = $data['kilometros_recorridos'] ?? 0;
        $dispositivo->crps_participantes = $data['crps_participantes'] ?? null;
        $dispositivo->acompanamientos = $data['acompanamientos'] ?? 0;
        $dispositivo->abanderamientos = $data['abanderamientos'] ?? 0;
        $dispositivo->auxilios_viales = $data['auxilios_viales'] ?? 0;
        $dispositivo->prox_empresas = $data['prox_empresas'] ?? 0;
        $dispositivo->prox_tiendas_conveniencia = $data['prox_tiendas_conveniencia'] ?? 0;
        $dispositivo->prox_escuelas = $data['prox_escuelas'] ?? 0;
        $dispositivo->prox_hospitales = $data['prox_hospitales'] ?? 0;
        $dispositivo->antecedentes_personas = $data['antecedentes_personas'] ?? 0;
        $dispositivo->antecedentes_vehiculos = $data['antecedentes_vehiculos'] ?? 0;
        $dispositivo->antecedentes_motos = $data['antecedentes_motos'] ?? 0;
        $dispositivo->antecedentes_camiones = $data['antecedentes_camiones'] ?? 0;
        $dispositivo->puestas_disposicion = $data['puestas_disposicion'] ?? 0;
        $dispositivo->vehiculos_recuperados = $data['vehiculos_recuperados'] ?? 0;
        $dispositivo->armas_aseguradas = $data['armas_aseguradas'] ?? 0;
        $dispositivo->mercancia_recuperada = $data['mercancia_recuperada'] ?? 0;
        $dispositivo->decomiso_drogas = $data['decomiso_drogas'] ?? 0;
        $dispositivo->observaciones = $data['observaciones'] ?? null;
        $dispositivo->created_by = Auth::id();
        $dispositivo->updated_by = Auth::id();
        $dispositivo->save();

        return redirect()
            ->route('guardianes_camino.dispositivos.show', [$operativo->id, $dispositivo->id])
            ->with('success', 'Dispositivo capturado correctamente.');
    }

    public function show($operativo, $dispositivo)
    {
        $operativo = Operativo::with('catalogo')
            ->whereHas('catalogo', function ($q) {
                $q->where('slug', 'guardianes-del-camino');
            })
            ->findOrFail($operativo);

        $dispositivo = OperativoDispositivo::with(['catalogo', 'operativo', 'unidad', 'delegacion', 'destacamento', 'usuario', 'fotos'])
            ->where('operativo_id', $operativo->id)
            ->findOrFail($dispositivo);

        return view('guardianes_camino.dispositivos.show', compact('operativo', 'dispositivo'));
    }

    public function edit($operativo, $dispositivo)
    {
        $operativo = Operativo::with('catalogo')
            ->whereHas('catalogo', function ($q) {
                $q->where('slug', 'guardianes-del-camino');
            })
            ->findOrFail($operativo);

        $dispositivo = OperativoDispositivo::where('operativo_id', $operativo->id)->findOrFail($dispositivo);

        $catalogos = OperativoDispositivoCatalogo::query()
            ->where('activo', 1)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        return view('guardianes_camino.dispositivos.edit', compact('operativo', 'dispositivo', 'catalogos'));
    }

    public function update(Request $request, $operativo, $dispositivo)
    {
        $operativo = Operativo::with('catalogo')
            ->whereHas('catalogo', function ($q) {
                $q->where('slug', 'guardianes-del-camino');
            })
            ->findOrFail($operativo);

        $dispositivo = OperativoDispositivo::where('operativo_id', $operativo->id)->findOrFail($dispositivo);

        $data = $request->validate([
            'operativo_dispositivo_catalogo_id' => ['required', 'integer', 'exists:operativo_dispositivo_catalogos,id'],
            'fecha' => ['required', 'date'],
            'hora' => ['nullable', 'date_format:H:i'],
            'unidad_org_id' => ['required', 'integer'],
            'delegacion_id' => ['nullable', 'integer'],
            'destacamento_id' => ['nullable', 'integer'],
            'lugar' => ['nullable', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'cantidad' => ['nullable', 'integer', 'min:0'],
            'vehiculos_inspeccionados' => ['nullable', 'integer', 'min:0'],
            'personas_inspeccionadas' => ['nullable', 'integer', 'min:0'],
            'vehiculos_impactados' => ['nullable', 'integer', 'min:0'],
            'personas_impactadas' => ['nullable', 'integer', 'min:0'],
            'estado_fuerza_participante' => ['nullable', 'integer', 'min:0'],
            'kilometros_recorridos' => ['nullable', 'numeric', 'min:0'],
            'crps_participantes' => ['nullable', 'string'],
            'acompanamientos' => ['nullable', 'integer', 'min:0'],
            'abanderamientos' => ['nullable', 'integer', 'min:0'],
            'auxilios_viales' => ['nullable', 'integer', 'min:0'],
            'prox_empresas' => ['nullable', 'integer', 'min:0'],
            'prox_tiendas_conveniencia' => ['nullable', 'integer', 'min:0'],
            'prox_escuelas' => ['nullable', 'integer', 'min:0'],
            'prox_hospitales' => ['nullable', 'integer', 'min:0'],
            'antecedentes_personas' => ['nullable', 'integer', 'min:0'],
            'antecedentes_vehiculos' => ['nullable', 'integer', 'min:0'],
            'antecedentes_motos' => ['nullable', 'integer', 'min:0'],
            'antecedentes_camiones' => ['nullable', 'integer', 'min:0'],
            'puestas_disposicion' => ['nullable', 'integer', 'min:0'],
            'vehiculos_recuperados' => ['nullable', 'integer', 'min:0'],
            'armas_aseguradas' => ['nullable', 'integer', 'min:0'],
            'mercancia_recuperada' => ['nullable', 'integer', 'min:0'],
            'decomiso_drogas' => ['nullable', 'integer', 'min:0'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $dispositivo->operativo_dispositivo_catalogo_id = $data['operativo_dispositivo_catalogo_id'];
        $dispositivo->fecha = $data['fecha'];
        $dispositivo->hora = $data['hora'] ?? null;
        $dispositivo->unidad_org_id = $data['unidad_org_id'];
        $dispositivo->delegacion_id = $data['delegacion_id'] ?? null;
        $dispositivo->destacamento_id = $data['destacamento_id'] ?? null;
        $dispositivo->lugar = $data['lugar'] ?? null;
        $dispositivo->descripcion = $data['descripcion'] ?? null;
        $dispositivo->cantidad = $data['cantidad'] ?? 0;
        $dispositivo->vehiculos_inspeccionados = $data['vehiculos_inspeccionados'] ?? 0;
        $dispositivo->personas_inspeccionadas = $data['personas_inspeccionadas'] ?? 0;
        $dispositivo->vehiculos_impactados = $data['vehiculos_impactados'] ?? 0;
        $dispositivo->personas_impactadas = $data['personas_impactadas'] ?? 0;
        $dispositivo->estado_fuerza_participante = $data['estado_fuerza_participante'] ?? 0;
        $dispositivo->kilometros_recorridos = $data['kilometros_recorridos'] ?? 0;
        $dispositivo->crps_participantes = $data['crps_participantes'] ?? null;
        $dispositivo->acompanamientos = $data['acompanamientos'] ?? 0;
        $dispositivo->abanderamientos = $data['abanderamientos'] ?? 0;
        $dispositivo->auxilios_viales = $data['auxilios_viales'] ?? 0;
        $dispositivo->prox_empresas = $data['prox_empresas'] ?? 0;
        $dispositivo->prox_tiendas_conveniencia = $data['prox_tiendas_conveniencia'] ?? 0;
        $dispositivo->prox_escuelas = $data['prox_escuelas'] ?? 0;
        $dispositivo->prox_hospitales = $data['prox_hospitales'] ?? 0;
        $dispositivo->antecedentes_personas = $data['antecedentes_personas'] ?? 0;
        $dispositivo->antecedentes_vehiculos = $data['antecedentes_vehiculos'] ?? 0;
        $dispositivo->antecedentes_motos = $data['antecedentes_motos'] ?? 0;
        $dispositivo->antecedentes_camiones = $data['antecedentes_camiones'] ?? 0;
        $dispositivo->puestas_disposicion = $data['puestas_disposicion'] ?? 0;
        $dispositivo->vehiculos_recuperados = $data['vehiculos_recuperados'] ?? 0;
        $dispositivo->armas_aseguradas = $data['armas_aseguradas'] ?? 0;
        $dispositivo->mercancia_recuperada = $data['mercancia_recuperada'] ?? 0;
        $dispositivo->decomiso_drogas = $data['decomiso_drogas'] ?? 0;
        $dispositivo->observaciones = $data['observaciones'] ?? null;
        $dispositivo->updated_by = Auth::id();
        $dispositivo->save();

        return redirect()
            ->route('guardianes_camino.dispositivos.show', [$operativo->id, $dispositivo->id])
            ->with('success', 'Dispositivo actualizado correctamente.');
    }

    public function destroy($operativo, $dispositivo)
    {
        $operativo = Operativo::with('catalogo')
            ->whereHas('catalogo', function ($q) {
                $q->where('slug', 'guardianes-del-camino');
            })
            ->findOrFail($operativo);

        $dispositivo = OperativoDispositivo::where('operativo_id', $operativo->id)->findOrFail($dispositivo);
        $dispositivo->delete();

        return redirect()
            ->route('guardianes_camino.show', $operativo->id)
            ->with('success', 'Dispositivo eliminado correctamente.');
    }
}
