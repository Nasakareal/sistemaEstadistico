<?php

namespace App\Http\Controllers;

use App\Models\Delegacion;
use App\Models\Operativo;
use App\Models\OperativoCatalogo;
use App\Models\OperativoFoto;
use App\Models\Unidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OperativoController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'can:ver operativos']);
    }

    public function index(Request $request)
    {
        $tz = 'America/Mexico_City';

        $fechaSeleccionada = $request->filled('fecha')
            ? $request->input('fecha')
            : now($tz)->toDateString();

        $query = Operativo::query()
            ->whereDate('fecha', $fechaSeleccionada)
            ->whereNotNull('captura_uuid');

        $this->applyOperativosVisibilityScope($query, Auth::user());

        if ($request->filled('q')) {
            $q = trim((string) $request->q);
            $query->where(function ($sub) use ($q) {
                $sub->where('lugar', 'like', "%{$q}%")
                    ->orWhere('descripcion', 'like', "%{$q}%")
                    ->orWhere('observaciones', 'like', "%{$q}%")
                    ->orWhereHas('catalogo', function ($cat) use ($q) {
                        $cat->where('nombre', 'like', "%{$q}%");
                    });
            });
        }

        if ($request->filled('operativo_catalogo_id')) {
            $catalogoId = (int) $request->operativo_catalogo_id;
            $query->whereExists(function ($sub) use ($catalogoId) {
                $sub->select(DB::raw(1))
                    ->from('operativos as op2')
                    ->whereColumn('op2.captura_uuid', 'operativos.captura_uuid')
                    ->where('op2.operativo_catalogo_id', $catalogoId);
            });
        }

        $capturas = $query
            ->select(
                'captura_uuid',
                'fecha',
                'hora',
                'unidad_org_id',
                'delegacion_id',
                'descripcion',
                'lugar'
            )
            ->selectRaw('MIN(created_at) as created_at')
            ->selectRaw('COUNT(*) as total_operativos')
            ->groupBy(
                'captura_uuid',
                'fecha',
                'hora',
                'unidad_org_id',
                'delegacion_id',
                'descripcion',
                'lugar'
            )
            ->orderBy('fecha', 'desc')
            ->orderBy('hora', 'desc')
            ->orderByDesc('created_at')
            ->get();

        $unidades = Unidad::whereIn('id', $capturas->pluck('unidad_org_id')->filter()->unique()->values())->get()->keyBy('id');
        $delegaciones = Delegacion::whereIn('id', $capturas->pluck('delegacion_id')->filter()->unique()->values())->get()->keyBy('id');

        $capturas->transform(function ($row) use ($unidades, $delegaciones) {
            $row->unidad = $row->unidad_org_id ? ($unidades[$row->unidad_org_id] ?? null) : null;
            $row->delegacion = $row->delegacion_id ? ($delegaciones[$row->delegacion_id] ?? null) : null;
            return $row;
        });

        $catalogos = $this->catalogosVisiblesParaUsuario(Auth::user());
        $operativos = $capturas;

        $queryWhatsapp = Operativo::query()
            ->with(['catalogo', 'unidad', 'delegacion'])
            ->whereDate('fecha', $fechaSeleccionada)
            ->whereNotNull('captura_uuid')
            ->orderBy('delegacion_id')
            ->orderBy('unidad_org_id')
            ->orderBy('hora')
            ->orderBy('operativo_catalogo_id');

        $this->applyOperativosVisibilityScope($queryWhatsapp, Auth::user());

        $operativosWhatsapp = $queryWhatsapp->get();

        $whatsappTexto = $this->buildWhatsappDailySummaryText($operativosWhatsapp, $fechaSeleccionada);
        $whatsappUrl = 'https://wa.me/?text=' . rawurlencode($whatsappTexto);

        return view('operativos.index', compact(
            'operativos',
            'catalogos',
            'fechaSeleccionada',
            'whatsappTexto',
            'whatsappUrl'
        ));
    }

    public function create()
    {
        $this->authorize('crear operativos');

        $catalogos = $this->catalogosVisiblesParaUsuario(Auth::user());

        return view('operativos.create', compact('catalogos'));
    }

    public function store(Request $request)
    {
        $this->authorize('crear operativos');

        $validated = $request->validate([
            'fecha' => 'required|date',
            'hora' => 'required',
            'descripcion_general' => 'required|string|max:255',
            'tramos' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.operativo_catalogo_id' => 'required|exists:operativo_catalogos,id',
            'items.*.dispositivos_realizados' => 'nullable|integer|min:0',
            'items.*.vehiculos_inspeccionados' => 'nullable|integer|min:0',
            'items.*.personas_inspeccionadas' => 'nullable|integer|min:0',
            'items.*.vehiculos_impactados' => 'nullable|integer|min:0',
            'items.*.personas_impactadas' => 'nullable|integer|min:0',
            'items.*.estado_fuerza_participante' => 'nullable|integer|min:0',
            'items.*.kilometros_recorridos' => 'nullable|numeric|min:0',
            'items.*.crps_participantes' => 'nullable|string|max:255',
            'totales.antecedentes_personas' => 'nullable|integer|min:0',
            'totales.antecedentes_vehiculos' => 'nullable|integer|min:0',
            'totales.antecedentes_motos' => 'nullable|integer|min:0',
            'totales.antecedentes_camiones' => 'nullable|integer|min:0',
            'totales.puestas_disposicion' => 'nullable|integer|min:0',
            'totales.vehiculos_recuperados' => 'nullable|integer|min:0',
            'totales.armas_aseguradas' => 'nullable|integer|min:0',
            'totales.mercancia_recuperada' => 'nullable|integer|min:0',
            'totales.decomiso_drogas' => 'nullable|integer|min:0',
            'observaciones' => 'nullable|string',
            'fotos' => 'nullable|array',
            'fotos.*' => 'nullable|array',
            'fotos.*.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $user = Auth::user();
        $items = $validated['items'] ?? [];
        $totales = $validated['totales'] ?? [];

        $catalogosPermitidos = $this->catalogosVisiblesParaUsuario($user)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        foreach ($items as $item) {
            $catalogoId = (int) ($item['operativo_catalogo_id'] ?? 0);

            if (!in_array($catalogoId, $catalogosPermitidos, true)) {
                return back()->withErrors([
                    'items' => 'Uno o más catálogos seleccionados no están disponibles para su unidad.',
                ])->withInput();
            }
        }

        $hayCaptura = false;

        foreach ($items as $catalogoId => $item) {
            $catalogoId = (int) $catalogoId;

            $dispositivosRealizados = (int) ($item['dispositivos_realizados'] ?? 0);
            $vehiculosInspeccionados = (int) ($item['vehiculos_inspeccionados'] ?? 0);
            $personasInspeccionadas = (int) ($item['personas_inspeccionadas'] ?? 0);
            $vehiculosImpactados = (int) ($item['vehiculos_impactados'] ?? 0);
            $personasImpactadas = (int) ($item['personas_impactadas'] ?? 0);
            $estadoFuerza = (int) ($item['estado_fuerza_participante'] ?? 0);
            $kilometros = (float) ($item['kilometros_recorridos'] ?? 0);
            $crps = trim((string) ($item['crps_participantes'] ?? ''));
            $fotosItem = $request->file("fotos.$catalogoId", []);

            if (
                $dispositivosRealizados > 0 ||
                $vehiculosInspeccionados > 0 ||
                $personasInspeccionadas > 0 ||
                $vehiculosImpactados > 0 ||
                $personasImpactadas > 0 ||
                $estadoFuerza > 0 ||
                $kilometros > 0 ||
                $crps !== '' ||
                !empty($fotosItem)
            ) {
                $hayCaptura = true;
                break;
            }
        }

        if (!$hayCaptura) {
            return back()->withErrors([
                'items' => 'Debe capturar al menos un operativo con datos o fotografías.',
            ])->withInput();
        }

        return DB::transaction(function () use ($request, $validated, $user, $items, $totales) {
            $capturaUuid = (string) Str::uuid();
            $primerRegistro = true;

            foreach ($items as $catalogoId => $item) {
                $catalogoId = (int) $catalogoId;

                $dispositivosRealizados = (int) ($item['dispositivos_realizados'] ?? 0);
                $vehiculosInspeccionados = (int) ($item['vehiculos_inspeccionados'] ?? 0);
                $personasInspeccionadas = (int) ($item['personas_inspeccionadas'] ?? 0);
                $vehiculosImpactados = (int) ($item['vehiculos_impactados'] ?? 0);
                $personasImpactadas = (int) ($item['personas_impactadas'] ?? 0);
                $estadoFuerza = (int) ($item['estado_fuerza_participante'] ?? 0);
                $kilometros = (float) ($item['kilometros_recorridos'] ?? 0);
                $crps = trim((string) ($item['crps_participantes'] ?? ''));
                $fotosItem = $request->file("fotos.$catalogoId", []);

                $guardar = (
                    $dispositivosRealizados > 0 ||
                    $vehiculosInspeccionados > 0 ||
                    $personasInspeccionadas > 0 ||
                    $vehiculosImpactados > 0 ||
                    $personasImpactadas > 0 ||
                    $estadoFuerza > 0 ||
                    $kilometros > 0 ||
                    $crps !== '' ||
                    !empty($fotosItem)
                );

                if (!$guardar) {
                    continue;
                }

                $operativo = Operativo::create([
                    'captura_uuid' => $capturaUuid,
                    'fecha' => $validated['fecha'],
                    'hora' => $validated['hora'],
                    'operativo_catalogo_id' => (int) ($item['operativo_catalogo_id'] ?? $catalogoId),
                    'unidad_org_id' => $user->unidad_id ?? null,
                    'delegacion_id' => $user->delegacion_id ?? null,
                    'destacamento_id' => $user->destacamento_id ?? null,
                    'lugar' => $validated['tramos'] ?? null,
                    'descripcion' => $validated['descripcion_general'] ?? null,
                    'dispositivos_realizados' => $dispositivosRealizados,
                    'vehiculos_inspeccionados' => $vehiculosInspeccionados,
                    'personas_inspeccionadas' => $personasInspeccionadas,
                    'vehiculos_impactados' => $vehiculosImpactados,
                    'personas_impactadas' => $personasImpactadas,
                    'antecedentes_personas' => $primerRegistro ? (int) ($totales['antecedentes_personas'] ?? 0) : 0,
                    'antecedentes_vehiculos' => $primerRegistro ? (int) ($totales['antecedentes_vehiculos'] ?? 0) : 0,
                    'antecedentes_motos' => $primerRegistro ? (int) ($totales['antecedentes_motos'] ?? 0) : 0,
                    'antecedentes_camiones' => $primerRegistro ? (int) ($totales['antecedentes_camiones'] ?? 0) : 0,
                    'estado_fuerza_participante' => $estadoFuerza,
                    'kilometros_recorridos' => $kilometros,
                    'acompanamientos' => 0,
                    'abanderamientos' => 0,
                    'auxilios_viales' => 0,
                    'puestas_disposicion' => $primerRegistro ? (int) ($totales['puestas_disposicion'] ?? 0) : 0,
                    'vehiculos_recuperados' => $primerRegistro ? (int) ($totales['vehiculos_recuperados'] ?? 0) : 0,
                    'armas_aseguradas' => $primerRegistro ? (int) ($totales['armas_aseguradas'] ?? 0) : 0,
                    'mercancia_recuperada' => $primerRegistro ? (int) ($totales['mercancia_recuperada'] ?? 0) : 0,
                    'decomiso_drogas' => $primerRegistro ? (int) ($totales['decomiso_drogas'] ?? 0) : 0,
                    'crps_participantes' => $crps !== '' ? $crps : null,
                    'observaciones' => $validated['observaciones'] ?? null,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);

                if (!empty($fotosItem)) {
                    foreach ($fotosItem as $file) {
                        $fotoHash = hash_file('sha256', $file->getRealPath());
                        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
                        $filename = now()->format('Ymd_His') . '_' . Str::random(10) . '.' . $ext;
                        $fotoPath = $file->storeAs('operativos', $filename, 'public');

                        OperativoFoto::create([
                            'operativo_id' => $operativo->id,
                            'foto_path' => $fotoPath,
                            'foto_nombre_original' => $file->getClientOriginalName(),
                            'foto_hash' => $fotoHash,
                            'created_by' => $user->id,
                        ]);
                    }
                }

                $primerRegistro = false;
            }

            return redirect()->route('operativos.show', $capturaUuid)->with('success', 'Consolidado de operativos guardado correctamente.');
        });
    }

    public function show(string $capturaUuid)
    {
        $operativos = $this->getVisibleCaptureOrFail($capturaUuid);

        $operativos->load(['catalogo', 'unidad', 'delegacion', 'fotos', 'creador', 'editor']);

        $primero = $operativos->first();
        $whatsappTexto = $this->buildWhatsappText($operativos);

        return view('operativos.show', compact('operativos', 'primero', 'capturaUuid', 'whatsappTexto'));
    }

    public function edit(string $capturaUuid)
    {
        $this->authorize('editar operativos');

        $operativos = $this->getVisibleCaptureOrFail($capturaUuid);
        $operativos->load(['fotos']);

        $catalogos = $this->catalogosVisiblesParaUsuario(Auth::user());
        $operativosPorCatalogo = $operativos->keyBy('operativo_catalogo_id');
        $primero = $operativos->first();

        $totales = [
            'antecedentes_personas' => $primero->antecedentes_personas ?? 0,
            'antecedentes_vehiculos' => $primero->antecedentes_vehiculos ?? 0,
            'antecedentes_motos' => $primero->antecedentes_motos ?? 0,
            'antecedentes_camiones' => $primero->antecedentes_camiones ?? 0,
            'puestas_disposicion' => $primero->puestas_disposicion ?? 0,
            'vehiculos_recuperados' => $primero->vehiculos_recuperados ?? 0,
            'armas_aseguradas' => $primero->armas_aseguradas ?? 0,
            'mercancia_recuperada' => $primero->mercancia_recuperada ?? 0,
            'decomiso_drogas' => $primero->decomiso_drogas ?? 0,
        ];

        $fecha = $primero->fecha ?? null;
        $hora = $primero->hora ?? null;
        $descripcionGeneral = $primero->descripcion ?? null;
        $tramos = $primero->lugar ?? null;
        $observaciones = $primero->observaciones ?? null;

        return view('operativos.edit', compact(
            'capturaUuid',
            'catalogos',
            'operativosPorCatalogo',
            'totales',
            'fecha',
            'hora',
            'descripcionGeneral',
            'tramos',
            'observaciones'
        ));
    }

    public function update(Request $request, string $capturaUuid)
    {
        $this->authorize('editar operativos');

        $existentes = $this->getVisibleCaptureOrFail($capturaUuid);
        $existentes->load(['fotos']);

        $validated = $request->validate([
            'fecha' => 'required|date',
            'hora' => 'required',
            'descripcion_general' => 'required|string|max:255',
            'tramos' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.operativo_catalogo_id' => 'required|exists:operativo_catalogos,id',
            'items.*.operativo_id' => 'nullable|integer|exists:operativos,id',
            'items.*.dispositivos_realizados' => 'nullable|integer|min:0',
            'items.*.vehiculos_inspeccionados' => 'nullable|integer|min:0',
            'items.*.personas_inspeccionadas' => 'nullable|integer|min:0',
            'items.*.vehiculos_impactados' => 'nullable|integer|min:0',
            'items.*.personas_impactadas' => 'nullable|integer|min:0',
            'items.*.estado_fuerza_participante' => 'nullable|integer|min:0',
            'items.*.kilometros_recorridos' => 'nullable|numeric|min:0',
            'items.*.crps_participantes' => 'nullable|string|max:255',
            'totales.antecedentes_personas' => 'nullable|integer|min:0',
            'totales.antecedentes_vehiculos' => 'nullable|integer|min:0',
            'totales.antecedentes_motos' => 'nullable|integer|min:0',
            'totales.antecedentes_camiones' => 'nullable|integer|min:0',
            'totales.puestas_disposicion' => 'nullable|integer|min:0',
            'totales.vehiculos_recuperados' => 'nullable|integer|min:0',
            'totales.armas_aseguradas' => 'nullable|integer|min:0',
            'totales.mercancia_recuperada' => 'nullable|integer|min:0',
            'totales.decomiso_drogas' => 'nullable|integer|min:0',
            'observaciones' => 'nullable|string',
            'fotos' => 'nullable|array',
            'fotos.*' => 'nullable|array',
            'fotos.*.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096',
            'eliminar_fotos' => 'nullable|array',
            'eliminar_fotos.*' => 'integer|exists:operativo_fotos,id',
        ]);

        $user = Auth::user();
        $items = $validated['items'] ?? [];
        $totales = $validated['totales'] ?? [];
        $eliminarFotosIds = collect($validated['eliminar_fotos'] ?? [])->map(fn ($id) => (int) $id)->toArray();

        $existentesPorCatalogo = $existentes->keyBy('operativo_catalogo_id');

        $catalogosPermitidos = $this->catalogosVisiblesParaUsuario($user)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        foreach ($items as $item) {
            $catalogoId = (int) ($item['operativo_catalogo_id'] ?? 0);

            if (!in_array($catalogoId, $catalogosPermitidos, true)) {
                return back()->withErrors([
                    'items' => 'Uno o más catálogos seleccionados no están disponibles para su unidad.',
                ])->withInput();
            }
        }

        $hayCaptura = false;

        foreach ($items as $catalogoId => $item) {
            $catalogoId = (int) $catalogoId;

            $dispositivosRealizados = (int) ($item['dispositivos_realizados'] ?? 0);
            $vehiculosInspeccionados = (int) ($item['vehiculos_inspeccionados'] ?? 0);
            $personasInspeccionadas = (int) ($item['personas_inspeccionadas'] ?? 0);
            $vehiculosImpactados = (int) ($item['vehiculos_impactados'] ?? 0);
            $personasImpactadas = (int) ($item['personas_impactadas'] ?? 0);
            $estadoFuerza = (int) ($item['estado_fuerza_participante'] ?? 0);
            $kilometros = (float) ($item['kilometros_recorridos'] ?? 0);
            $crps = trim((string) ($item['crps_participantes'] ?? ''));
            $fotosItem = $request->file("fotos.$catalogoId", []);

            $existente = $existentesPorCatalogo->get((int) ($item['operativo_catalogo_id'] ?? $catalogoId));

            $fotosExistentesNoEliminadas = 0;
            if ($existente && $existente->fotos) {
                $fotosExistentesNoEliminadas = $existente->fotos
                    ->reject(function ($foto) use ($eliminarFotosIds) {
                        return in_array((int) $foto->id, $eliminarFotosIds, true);
                    })
                    ->count();
            }

            if (
                $dispositivosRealizados > 0 ||
                $vehiculosInspeccionados > 0 ||
                $personasInspeccionadas > 0 ||
                $vehiculosImpactados > 0 ||
                $personasImpactadas > 0 ||
                $estadoFuerza > 0 ||
                $kilometros > 0 ||
                $crps !== '' ||
                !empty($fotosItem) ||
                $fotosExistentesNoEliminadas > 0
            ) {
                $hayCaptura = true;
                break;
            }
        }

        if (!$hayCaptura) {
            return back()->withErrors([
                'items' => 'Debe conservar o capturar al menos un operativo con datos o fotografías.',
            ])->withInput();
        }

        return DB::transaction(function () use (
            $request,
            $validated,
            $user,
            $items,
            $totales,
            $existentes,
            $existentesPorCatalogo,
            $capturaUuid,
            $eliminarFotosIds
        ) {
            $primerRegistro = true;
            $idsMantener = [];

            if (!empty($eliminarFotosIds)) {
                $fotosEliminar = OperativoFoto::query()
                    ->whereIn('id', $eliminarFotosIds)
                    ->whereIn('operativo_id', $existentes->pluck('id'))
                    ->get();

                foreach ($fotosEliminar as $foto) {
                    if (!empty($foto->foto_path) && Storage::disk('public')->exists($foto->foto_path)) {
                        Storage::disk('public')->delete($foto->foto_path);
                    }

                    $foto->delete();
                }
            }

            $existentes = $this->getVisibleCaptureOrFail($capturaUuid);
            $existentes->load(['fotos']);
            $existentesPorCatalogo = $existentes->keyBy('operativo_catalogo_id');

            foreach ($items as $catalogoId => $item) {
                $catalogoId = (int) $catalogoId;

                $dispositivosRealizados = (int) ($item['dispositivos_realizados'] ?? 0);
                $vehiculosInspeccionados = (int) ($item['vehiculos_inspeccionados'] ?? 0);
                $personasInspeccionadas = (int) ($item['personas_inspeccionadas'] ?? 0);
                $vehiculosImpactados = (int) ($item['vehiculos_impactados'] ?? 0);
                $personasImpactadas = (int) ($item['personas_impactadas'] ?? 0);
                $estadoFuerza = (int) ($item['estado_fuerza_participante'] ?? 0);
                $kilometros = (float) ($item['kilometros_recorridos'] ?? 0);
                $crps = trim((string) ($item['crps_participantes'] ?? ''));
                $fotosItem = $request->file("fotos.$catalogoId", []);

                $existente = $existentesPorCatalogo->get((int) ($item['operativo_catalogo_id'] ?? $catalogoId));

                $fotosExistentesNoEliminadas = 0;
                if ($existente && $existente->fotos) {
                    $fotosExistentesNoEliminadas = $existente->fotos->count();
                }

                $guardar = (
                    $dispositivosRealizados > 0 ||
                    $vehiculosInspeccionados > 0 ||
                    $personasInspeccionadas > 0 ||
                    $vehiculosImpactados > 0 ||
                    $personasImpactadas > 0 ||
                    $estadoFuerza > 0 ||
                    $kilometros > 0 ||
                    $crps !== '' ||
                    !empty($fotosItem) ||
                    $fotosExistentesNoEliminadas > 0
                );

                if (!$guardar) {
                    if ($existente) {
                        foreach ($existente->fotos as $foto) {
                            if (!empty($foto->foto_path) && Storage::disk('public')->exists($foto->foto_path)) {
                                Storage::disk('public')->delete($foto->foto_path);
                            }
                            $foto->delete();
                        }

                        $existente->delete();
                    }

                    continue;
                }

                $payload = [
                    'captura_uuid' => $capturaUuid,
                    'fecha' => $validated['fecha'],
                    'hora' => $validated['hora'],
                    'operativo_catalogo_id' => (int) ($item['operativo_catalogo_id'] ?? $catalogoId),
                    'unidad_org_id' => $user->unidad_id ?? null,
                    'delegacion_id' => $user->delegacion_id ?? null,
                    'destacamento_id' => $user->destacamento_id ?? null,
                    'lugar' => $validated['tramos'] ?? null,
                    'descripcion' => $validated['descripcion_general'] ?? null,
                    'dispositivos_realizados' => $dispositivosRealizados,
                    'vehiculos_inspeccionados' => $vehiculosInspeccionados,
                    'personas_inspeccionadas' => $personasInspeccionadas,
                    'vehiculos_impactados' => $vehiculosImpactados,
                    'personas_impactadas' => $personasImpactadas,
                    'antecedentes_personas' => $primerRegistro ? (int) ($totales['antecedentes_personas'] ?? 0) : 0,
                    'antecedentes_vehiculos' => $primerRegistro ? (int) ($totales['antecedentes_vehiculos'] ?? 0) : 0,
                    'antecedentes_motos' => $primerRegistro ? (int) ($totales['antecedentes_motos'] ?? 0) : 0,
                    'antecedentes_camiones' => $primerRegistro ? (int) ($totales['antecedentes_camiones'] ?? 0) : 0,
                    'estado_fuerza_participante' => $estadoFuerza,
                    'kilometros_recorridos' => $kilometros,
                    'acompanamientos' => 0,
                    'abanderamientos' => 0,
                    'auxilios_viales' => 0,
                    'puestas_disposicion' => $primerRegistro ? (int) ($totales['puestas_disposicion'] ?? 0) : 0,
                    'vehiculos_recuperados' => $primerRegistro ? (int) ($totales['vehiculos_recuperados'] ?? 0) : 0,
                    'armas_aseguradas' => $primerRegistro ? (int) ($totales['armas_aseguradas'] ?? 0) : 0,
                    'mercancia_recuperada' => $primerRegistro ? (int) ($totales['mercancia_recuperada'] ?? 0) : 0,
                    'decomiso_drogas' => $primerRegistro ? (int) ($totales['decomiso_drogas'] ?? 0) : 0,
                    'crps_participantes' => $crps !== '' ? $crps : null,
                    'observaciones' => $validated['observaciones'] ?? null,
                    'updated_by' => $user->id,
                ];

                if ($existente) {
                    $existente->update($payload);
                    $operativo = $existente;
                } else {
                    $payload['created_by'] = $user->id;
                    $operativo = Operativo::create($payload);
                }

                $idsMantener[] = $operativo->id;

                if (!empty($fotosItem)) {
                    foreach ($fotosItem as $file) {
                        $fotoHash = hash_file('sha256', $file->getRealPath());
                        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
                        $filename = now()->format('Ymd_His') . '_' . Str::random(10) . '.' . $ext;
                        $fotoPath = $file->storeAs('operativos', $filename, 'public');

                        OperativoFoto::create([
                            'operativo_id' => $operativo->id,
                            'foto_path' => $fotoPath,
                            'foto_nombre_original' => $file->getClientOriginalName(),
                            'foto_hash' => $fotoHash,
                            'created_by' => $user->id,
                        ]);
                    }
                }

                $primerRegistro = false;
            }

            $sobrantes = Operativo::query()
                ->where('captura_uuid', $capturaUuid)
                ->whereNotIn('id', $idsMantener)
                ->get();

            foreach ($sobrantes as $sobrante) {
                $sobrante->load('fotos');

                foreach ($sobrante->fotos as $foto) {
                    if (!empty($foto->foto_path) && Storage::disk('public')->exists($foto->foto_path)) {
                        Storage::disk('public')->delete($foto->foto_path);
                    }

                    $foto->delete();
                }

                $sobrante->delete();
            }

            return redirect()->route('operativos.show', $capturaUuid)->with('success', 'Consolidado de operativos actualizado correctamente.');
        });
    }

    public function destroy(string $capturaUuid)
    {
        $this->authorize('eliminar operativos');

        $operativos = $this->getVisibleCaptureOrFail($capturaUuid);
        $operativos->load('fotos');

        return DB::transaction(function () use ($operativos) {
            foreach ($operativos as $operativo) {
                foreach ($operativo->fotos as $foto) {
                    if (!empty($foto->foto_path) && Storage::disk('public')->exists($foto->foto_path)) {
                        Storage::disk('public')->delete($foto->foto_path);
                    }
                    $foto->delete();
                }

                $operativo->delete();
            }

            return back()->with('success', 'Consolidado de operativos eliminado correctamente.');
        });
    }

    public function whatsapp(Request $request)
    {
        $fechaSeleccionada = $request->filled('fecha')
            ? $request->input('fecha')
            : now('America/Mexico_City')->toDateString();

        $query = Operativo::query()
            ->with(['catalogo', 'unidad', 'delegacion'])
            ->whereDate('fecha', $fechaSeleccionada)
            ->whereNotNull('captura_uuid')
            ->orderBy('unidad_org_id')
            ->orderBy('delegacion_id')
            ->orderBy('hora')
            ->orderBy('operativo_catalogo_id');

        $this->applyOperativosVisibilityScope($query, Auth::user());

        $operativos = $query->get();
        $texto = $this->buildWhatsappText($operativos);

        return response()->json([
            'fecha' => $fechaSeleccionada,
            'texto' => $texto,
        ]);
    }

    private function catalogosVisiblesParaUsuario($usuario)
    {
        $query = OperativoCatalogo::query()
            ->where('activo', 1)
            ->orderBy('orden')
            ->orderBy('nombre');

        if (
            $usuario->hasRole('Superadmin')
            || $usuario->hasRole('Administrador')
            || $usuario->hasRole('Coordinador')
        ) {
            return $query->get();
        }

        $unidadId = (int) ($usuario->unidad_id ?? 0);

        return $query
            ->where(function ($q) use ($unidadId) {
                $q->whereNull('unidad_id');

                if ($unidadId > 0) {
                    $q->orWhere('unidad_id', $unidadId);
                }
            })
            ->get();
    }

    private function applyOperativosVisibilityScope($query, $usuario): void
    {
        if (
            $usuario->hasRole('Superadmin')
            || $usuario->hasRole('Administrador')
            || $usuario->hasRole('Coordinador')
        ) {
            return;
        }

        $unidadId = (int) ($usuario->unidad_id ?? 0);

        $unidadCarreterasId = (int) Unidad::query()
            ->where('slug', 'carreteras')
            ->value('id');

        if ($unidadCarreterasId > 0 && $unidadId === $unidadCarreterasId) {
            $query->where('unidad_org_id', $unidadCarreterasId);
            return;
        }

        if ($unidadId === 2) {
            $delegacionId = (int) ($usuario->delegacion_id ?? 0);

            if ($delegacionId <= 0) {
                $query->whereRaw('1=0');
                return;
            }

            $esRegional = Delegacion::query()
                ->where('id', $delegacionId)
                ->whereNull('delegacion_padre_id')
                ->exists();

            if ($usuario->hasRole('Subdirector')) {
                if ($esRegional) {
                    $ids = Delegacion::query()
                        ->where('id', $delegacionId)
                        ->orWhere('delegacion_padre_id', $delegacionId)
                        ->pluck('id')
                        ->toArray();

                    $query->whereIn('delegacion_id', $ids);
                } else {
                    $query->where('delegacion_id', $delegacionId);
                }
            } else {
                $query->where('delegacion_id', $delegacionId);
            }

            return;
        }

        if ($unidadId > 0) {
            $query->where('unidad_org_id', $unidadId);
            return;
        }

        $query->whereRaw('1=0');
    }

    private function getVisibleCaptureOrFail(string $capturaUuid)
    {
        $query = Operativo::query()->where('captura_uuid', $capturaUuid);
        $this->applyOperativosVisibilityScope($query, Auth::user());

        $operativos = $query->orderBy('operativo_catalogo_id')->get();

        abort_if($operativos->isEmpty(), 404);

        return $operativos;
    }

    private function buildWhatsappText($operativos): string
    {
        if ($operativos->isEmpty()) {
            return 'SIN RESULTADOS';
        }

        $lineas = [];
        $lineas[] = 'RESULTADO GENERAL DE OPERATIVOS';
        $lineas[] = 'FECHA: ' . $operativos->first()->fecha;

        $porUnidad = $operativos->groupBy(function ($op) {
            $unidad = $op->unidad->nombre ?? 'SIN UNIDAD';
            $delegacion = $op->delegacion->nombre ?? null;
            return $delegacion ? $unidad . ' - ' . $delegacion : $unidad;
        });

        foreach ($porUnidad as $nombreUnidad => $items) {
            $lineas[] = '';
            $lineas[] = $nombreUnidad;

            foreach ($items as $op) {
                $lineas[] = $this->formatWhatsappOperativo($op);
            }
        }

        return implode("\n", $lineas);
    }

    private function formatWhatsappOperativo($op): string
    {
        $nombre = mb_strtoupper($op->catalogo->nombre ?? 'OPERATIVO');
        $lineas = [];
        $lineas[] = $nombre . ': ' . (int) $op->dispositivos_realizados;

        if ($this->slugEs($op, ['psv-puesto-de-seguridad-y-vigilancia', 'rsv-recorridos-de-seguridad-y-vigilancia-patrullaje'])) {
            $lineas[] = 'VEHÍCULOS INSPECCIONADOS: ' . (int) $op->vehiculos_inspeccionados;
            $lineas[] = 'PERSONAS INSPECCIONADAS: ' . (int) $op->personas_inspeccionadas;
        } elseif ($this->slugEs($op, ['caballeros-del-camino'])) {
            $lineas[] = 'ACOMPAÑAMIENTOS: ' . (int) $op->acompanamientos;
            $lineas[] = 'ABANDERAMIENTOS: ' . (int) $op->abanderamientos;
            $lineas[] = 'AUXILIOS VIALES: ' . (int) $op->auxilios_viales;
        } elseif ($this->slugEs($op, ['carrusel'])) {
            $lineas[] = 'VEHÍCULOS IMPACTADOS: ' . (int) $op->vehiculos_impactados;
        } else {
            $lineas[] = 'VEHÍCULOS IMPACTADOS: ' . (int) $op->vehiculos_impactados;
            $lineas[] = 'PERSONAS IMPACTADAS: ' . (int) $op->personas_impactadas;
        }

        $lineas[] = 'ESTADO DE FUERZA PARTICIPANTE: ' . (int) $op->estado_fuerza_participante . ' elementos.';
        $lineas[] = 'CRP´S PARTICIPANTES: ' . ($op->crps_participantes ?: 'SIN DATO');
        $lineas[] = 'KILÓMETROS RECORRIDOS: ' . (float) $op->kilometros_recorridos;

        return implode("\n", $lineas);
    }

    private function slugEs($op, array $slugs): bool
    {
        $slug = $op->catalogo->slug ?? null;
        return in_array($slug, $slugs, true);
    }

    private function buildWhatsappDailySummaryText($operativos, string $fechaSeleccionada): string
    {
        if ($operativos->isEmpty()) {
            return 'SIN RESULTADOS PARA LA FECHA SELECCIONADA.';
        }

        $fechaFormateada = \Carbon\Carbon::parse($fechaSeleccionada)->format('d/m/Y');
        $horaFormateada = now('America/Mexico_City')->format('H:i') . ' hs.';

        $primerRegistro = $operativos->first();

        $descripcionGeneral = mb_strtoupper(trim((string) ($primerRegistro->descripcion ?? '')));
        $tramos = trim((string) ($primerRegistro->lugar ?? ''));

        $porSlug = $operativos->groupBy(function ($op) {
            return $op->catalogo->slug ?? 'sin-slug';
        });

        $sumar = function ($slug, $campo) use ($porSlug) {
            if (!isset($porSlug[$slug])) {
                return 0;
            }
            return (int) $porSlug[$slug]->sum($campo);
        };

        $sumarLista = function (array $slugs, $campo) use ($porSlug) {
            $total = 0;
            foreach ($slugs as $slug) {
                if (isset($porSlug[$slug])) {
                    $total += (int) $porSlug[$slug]->sum($campo);
                }
            }
            return $total;
        };

        $crpsParticipantes = $operativos
            ->pluck('crps_participantes')
            ->filter()
            ->flatMap(function ($item) {
                return preg_split('/[,;\n]+/', $item);
            })
            ->map(function ($item) {
                return trim($item);
            })
            ->filter()
            ->unique()
            ->values()
            ->implode(', ');

        $estadoFuerzaTotal = (int) $operativos->sum('estado_fuerza_participante');
        $kilometrosTotal = (float) $operativos->sum('kilometros_recorridos');

        $antecedentesPersonas = (int) $operativos->max('antecedentes_personas');
        $antecedentesVehiculos = (int) $operativos->max('antecedentes_vehiculos');
        $antecedentesMotos = (int) $operativos->max('antecedentes_motos');
        $antecedentesCamiones = (int) $operativos->max('antecedentes_camiones');
        $puestasDisposicion = (int) $operativos->max('puestas_disposicion');
        $vehiculosRecuperados = (int) $operativos->max('vehiculos_recuperados');
        $armasAseguradas = (int) $operativos->max('armas_aseguradas');
        $mercanciaRecuperada = (int) $operativos->max('mercancia_recuperada');
        $decomisoDrogas = (int) $operativos->max('decomiso_drogas');

        $totalInspecciones = (int) $operativos->sum('vehiculos_inspeccionados') + (int) $operativos->sum('personas_inspeccionadas');

        $lineas = [];

        $lineas[] = 'GUARDIA CIVIL';
        $lineas[] = '';
        $lineas[] = 'COORDINACIÓN DEL AGRUPAMIENTO DE SEGURIDAD VIAL';
        $lineas[] = '';
        $lineas[] = 'UNIDAD DE PROTECCIÓN EN CARRETERAS';
        $lineas[] = '';
        $lineas[] = 'ASUNTO: CONSOLIDADO DE NOVEDADES DE ACTIVIDADES DIARIAS.';
        $lineas[] = '';
        $lineas[] = $fechaFormateada . '         ' . $horaFormateada;
        $lineas[] = '';
        $lineas[] = 'DESCRIPCIÓN GENERAL:';
        $lineas[] = $descripcionGeneral !== '' ? $descripcionGeneral : 'SIN DESCRIPCIÓN GENERAL';
        if ($tramos !== '') {
            $lineas[] = 'EN TRAMOS CARRETEROS DE LOS MUNICIPIOS: ' . $tramos . '.';
        }
        $lineas[] = '';
        $lineas[] = '';
        $lineas[] = 'DISPOSITIVOS:';
        $lineas[] = '';

        $lineas[] = 'PSV (PUESTO DE SEGURIDAD Y VIGILANCIA): ' . $sumar('psv-puesto-de-seguridad-y-vigilancia', 'dispositivos_realizados');
        $lineas[] = 'VEHÍCULOS INSPECCIONADOS: ' . $sumar('psv-puesto-de-seguridad-y-vigilancia', 'vehiculos_inspeccionados');
        $lineas[] = 'PERSONAS INSPECCIONADAS: ' . $sumar('psv-puesto-de-seguridad-y-vigilancia', 'personas_inspeccionadas');
        $lineas[] = 'ESTADO DE FUERZA PARTICIPANTE: ' . $sumar('psv-puesto-de-seguridad-y-vigilancia', 'estado_fuerza_participante') . ' elementos.';
        $lineas[] = 'CRP´S. PARTICIPANTES: ' . ($crpsParticipantes !== '' ? $crpsParticipantes : 'SIN DATO');
        $lineas[] = 'KILÓMETROS RECORRIDOS: ' . $sumar('psv-puesto-de-seguridad-y-vigilancia', 'kilometros_recorridos');
        $lineas[] = '';

        $lineas[] = 'RSV (RECORRIDOS DE SEGURIDAD Y VIGILANCIA - PATRULLAJE): ' . $sumar('rsv-recorridos-de-seguridad-y-vigilancia-patrullaje', 'dispositivos_realizados');
        $lineas[] = 'VEHÍCULOS INSPECCIONADOS: ' . $sumar('rsv-recorridos-de-seguridad-y-vigilancia-patrullaje', 'vehiculos_inspeccionados');
        $lineas[] = 'PERSONAS INSPECCIONADAS: ' . $sumar('rsv-recorridos-de-seguridad-y-vigilancia-patrullaje', 'personas_inspeccionadas');
        $lineas[] = 'ESTADO DE FUERZA PARTICIPANTE: ' . $sumar('rsv-recorridos-de-seguridad-y-vigilancia-patrullaje', 'estado_fuerza_participante') . ' elementos.';
        $lineas[] = 'CRP´S. PARTICIPANTES: ' . ($crpsParticipantes !== '' ? $crpsParticipantes : 'SIN DATO');
        $lineas[] = 'KILÓMETROS RECORRIDOS: ' . $sumar('rsv-recorridos-de-seguridad-y-vigilancia-patrullaje', 'kilometros_recorridos');
        $lineas[] = '';

        $lineas[] = 'DISPOSITIVO CASCO: ' . $sumar('casco', 'dispositivos_realizados');
        $lineas[] = 'VEHÍCULOS IMPACTADOS: ' . $sumar('casco', 'vehiculos_impactados');
        $lineas[] = 'PERSONAS IMPACTADAS: ' . $sumar('casco', 'personas_impactadas');
        $lineas[] = 'ESTADO DE FUERZA PARTICIPANTE: ' . $sumar('casco', 'estado_fuerza_participante') . ' elementos.';
        $lineas[] = 'CRP´S. PARTICIPANTES: ' . ($crpsParticipantes !== '' ? $crpsParticipantes : 'SIN DATO');
        $lineas[] = 'KILÓMETROS RECORRIDOS: ' . $sumar('casco', 'kilometros_recorridos');
        $lineas[] = '';

        $lineas[] = 'DISPOSITIVO CINTURÓN: ' . $sumar('cinturon', 'dispositivos_realizados');
        $lineas[] = 'VEHÍCULOS IMPACTADOS: ' . $sumar('cinturon', 'vehiculos_impactados');
        $lineas[] = 'PERSONAS IMPACTADAS: ' . $sumar('cinturon', 'personas_impactadas');
        $lineas[] = 'ESTADO DE FUERZA PARTICIPANTE: ' . $sumar('cinturon', 'estado_fuerza_participante') . ' elementos.';
        $lineas[] = 'CRP´S. PARTICIPANTES: ' . ($crpsParticipantes !== '' ? $crpsParticipantes : 'SIN DATO');
        $lineas[] = 'KILÓMETROS RECORRIDOS: ' . $sumar('cinturon', 'kilometros_recorridos');
        $lineas[] = '';

        $lineas[] = 'DISPOSITIVO CARRUSEL: ' . $sumar('carrusel', 'dispositivos_realizados');
        $lineas[] = 'VEHÍCULOS IMPACTADOS: ' . $sumar('carrusel', 'vehiculos_impactados');
        $lineas[] = 'ESTADO DE FUERZA PARTICIPANTE: ' . $sumar('carrusel', 'estado_fuerza_participante') . ' elementos.';
        $lineas[] = 'CRP´S. PARTICIPANTES: ' . ($crpsParticipantes !== '' ? $crpsParticipantes : 'SIN DATO');
        $lineas[] = 'KILÓMETROS RECORRIDOS: ' . $sumar('carrusel', 'kilometros_recorridos');
        $lineas[] = '';

        $lineas[] = 'CORDILLERA: ' . $sumar('cordillera', 'dispositivos_realizados');
        $lineas[] = 'VEHÍCULOS IMPACTADOS: ' . $sumar('cordillera', 'vehiculos_impactados');
        $lineas[] = 'PERSONAS IMPACTADAS: ' . $sumar('cordillera', 'personas_impactadas');
        $lineas[] = 'ESTADO DE FUERZA PARTICIPANTE: ' . $sumar('cordillera', 'estado_fuerza_participante') . ' elementos.';
        $lineas[] = 'CRP´S. PARTICIPANTES: ' . ($crpsParticipantes !== '' ? $crpsParticipantes : 'SIN DATO');
        $lineas[] = 'KILÓMETROS RECORRIDOS: ' . $sumar('cordillera', 'kilometros_recorridos');
        $lineas[] = '';

        $lineas[] = 'DISPOSITIVO ASIENTO SEGURO PASAJEROS MENORES: ' . $sumar('asiento-seguro-pasajeros-menores', 'dispositivos_realizados');
        $lineas[] = 'VEHÍCULOS IMPACTADOS: ' . $sumar('asiento-seguro-pasajeros-menores', 'vehiculos_impactados');
        $lineas[] = 'PERSONAS IMPACTADAS: ' . $sumar('asiento-seguro-pasajeros-menores', 'personas_impactadas');
        $lineas[] = 'ESTADO DE FUERZA PARTICIPANTE: ' . $sumar('asiento-seguro-pasajeros-menores', 'estado_fuerza_participante') . ' elementos.';
        $lineas[] = 'CRP´S. PARTICIPANTES: ' . ($crpsParticipantes !== '' ? $crpsParticipantes : 'SIN DATO');
        $lineas[] = 'KILÓMETROS RECORRIDOS: ' . $sumar('asiento-seguro-pasajeros-menores', 'kilometros_recorridos');
        $lineas[] = '';

        $lineas[] = 'CABALLEROS DEL CAMINO: ' . $sumar('caballeros-del-camino', 'dispositivos_realizados');
        $lineas[] = '• ACOMPAÑAMIENTOS (ESCOLTAS, CARAVANAS, EMERGENCIAS, OTROS): ' . $sumar('caballeros-del-camino', 'acompanamientos');
        $lineas[] = '• ABANDERAMIENTOS (HECHOS DE TRÁNSITO, EVENTOS, OTROS): ' . $sumar('caballeros-del-camino', 'abanderamientos');
        $lineas[] = '• AUXILIOS VIALES (FALLAS MECÁNICAS, PEATÓN, OTROS): ' . $sumar('caballeros-del-camino', 'auxilios_viales');
        $lineas[] = 'ESTADO DE FUERZA PARTICIPANTE: ' . $sumar('caballeros-del-camino', 'estado_fuerza_participante') . ' elementos.';
        $lineas[] = 'CRP´S. PARTICIPANTES: ' . ($crpsParticipantes !== '' ? $crpsParticipantes : 'SIN DATO');
        $lineas[] = 'KILÓMETROS RECORRIDOS: ' . $sumar('caballeros-del-camino', 'kilometros_recorridos');
        $lineas[] = '';

        $lineas[] = 'PROXIMIDAD SOCIAL';
        $lineas[] = '- EMPRESAS';
        $lineas[] = '- TIENDAS DE CONVENIENCIA';
        $lineas[] = '- ESCUELAS';
        $lineas[] = '- HOSPITALES';
        $lineas[] = '';
        $lineas[] = 'TOTALES:';
        $lineas[] = '';
        $lineas[] = 'INSPECCIONES DE PERSONAS Y/O VEHÍCULOS: ' . $totalInspecciones;
        $lineas[] = 'ANTECEDENTES DE PERSONAS: ' . $antecedentesPersonas;
        $lineas[] = 'ANTECEDENTES DE VEHÍCULOS: ' . $antecedentesVehiculos;
        $lineas[] = 'ANTECEDENTES DE MOTOS: ' . $antecedentesMotos;
        $lineas[] = 'ANTECEDENTES DE CAMIONES: ' . $antecedentesCamiones;
        $lineas[] = '';
        $lineas[] = 'PUESTAS A DISPOSICIÓN: ' . $puestasDisposicion;
        $lineas[] = '• VEHÍCULOS RECUPERADOS: ' . $vehiculosRecuperados;
        $lineas[] = '• ARMAS ASEGURADAS: ' . $armasAseguradas;
        $lineas[] = '• MERCANCÍA RECUPERADA: ' . $mercanciaRecuperada;
        $lineas[] = '• DECOMISO DE DROGAS: ' . $decomisoDrogas;
        $lineas[] = '';
        $lineas[] = 'ESTADO DE FUERZA PARTICIPANTE: ' . $estadoFuerzaTotal . ' elementos.';
        $lineas[] = 'CRP´S. PARTICIPANTES: ' . ($crpsParticipantes !== '' ? $crpsParticipantes : 'SIN DATO');
        $lineas[] = 'KILÓMETROS RECORRIDOS: ' . $kilometrosTotal;
        $lineas[] = '';
        $lineas[] = 'SE ANEXAN GRÁFICAS.';
        $lineas[] = '';
        $lineas[] = '';
        $lineas[] = 'RESPETUOSAMENTE.';

        return implode("\n", $lineas);
    }
}
