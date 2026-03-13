<?php

namespace App\Http\Controllers;

use App\Models\Delegacion;
use App\Models\Operativo;
use App\Models\OperativoCatalogo;
use App\Models\OperativoFoto;
use App\Models\Unidad;
use Carbon\Carbon;
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
            ->with(['catalogo', 'unidad', 'delegacion', 'fotos'])
            ->whereDate('fecha', $fechaSeleccionada)
            ->orderBy('fecha', 'desc')
            ->orderByDesc('created_at');

        $this->applyOperativosVisibilityScope($query, Auth::user());

        if ($request->filled('operativo_catalogo_id')) {
            $query->where('operativo_catalogo_id', (int) $request->operativo_catalogo_id);
        }

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

        $catalogos = $this->catalogosVisiblesParaUsuario(Auth::user());

        $operativos = $query->get();

        return view('operativos.index', compact('operativos', 'catalogos', 'fechaSeleccionada'));
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
            'operativo_catalogo_id' => 'required|exists:operativo_catalogos,id',
            'lugar' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'dispositivos_realizados' => 'nullable|integer|min:0',
            'vehiculos_inspeccionados' => 'nullable|integer|min:0',
            'personas_inspeccionadas' => 'nullable|integer|min:0',
            'vehiculos_impactados' => 'nullable|integer|min:0',
            'personas_impactadas' => 'nullable|integer|min:0',
            'antecedentes_personas' => 'nullable|integer|min:0',
            'antecedentes_vehiculos' => 'nullable|integer|min:0',
            'antecedentes_motos' => 'nullable|integer|min:0',
            'antecedentes_camiones' => 'nullable|integer|min:0',
            'estado_fuerza_participante' => 'nullable|integer|min:0',
            'kilometros_recorridos' => 'nullable|numeric|min:0',
            'acompanamientos' => 'nullable|integer|min:0',
            'abanderamientos' => 'nullable|integer|min:0',
            'auxilios_viales' => 'nullable|integer|min:0',
            'puestas_disposicion' => 'nullable|integer|min:0',
            'vehiculos_recuperados' => 'nullable|integer|min:0',
            'armas_aseguradas' => 'nullable|integer|min:0',
            'mercancia_recuperada' => 'nullable|integer|min:0',
            'decomiso_drogas' => 'nullable|integer|min:0',
            'crps_participantes' => 'nullable|string',
            'observaciones' => 'nullable|string',
            'fotos' => 'nullable|array',
            'fotos.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $catalogo = $this->catalogoPermitidoParaUsuario((int) $validated['operativo_catalogo_id'], Auth::user());

        if (!$catalogo) {
            return back()->withErrors([
                'operativo_catalogo_id' => 'El catálogo seleccionado no está disponible para su unidad.',
            ])->withInput();
        }

        return DB::transaction(function () use ($request, $validated) {
            $user = Auth::user();

            $operativo = Operativo::create([
                'fecha' => $validated['fecha'],
                'operativo_catalogo_id' => $validated['operativo_catalogo_id'],
                'unidad_org_id' => $user->unidad_id ?? null,
                'delegacion_id' => $user->delegacion_id ?? null,
                'lugar' => $validated['lugar'] ?? null,
                'descripcion' => $validated['descripcion'] ?? null,
                'dispositivos_realizados' => (int) ($validated['dispositivos_realizados'] ?? 0),
                'vehiculos_inspeccionados' => (int) ($validated['vehiculos_inspeccionados'] ?? 0),
                'personas_inspeccionadas' => (int) ($validated['personas_inspeccionadas'] ?? 0),
                'vehiculos_impactados' => (int) ($validated['vehiculos_impactados'] ?? 0),
                'personas_impactadas' => (int) ($validated['personas_impactadas'] ?? 0),
                'antecedentes_personas' => (int) ($validated['antecedentes_personas'] ?? 0),
                'antecedentes_vehiculos' => (int) ($validated['antecedentes_vehiculos'] ?? 0),
                'antecedentes_motos' => (int) ($validated['antecedentes_motos'] ?? 0),
                'antecedentes_camiones' => (int) ($validated['antecedentes_camiones'] ?? 0),
                'estado_fuerza_participante' => (int) ($validated['estado_fuerza_participante'] ?? 0),
                'kilometros_recorridos' => $validated['kilometros_recorridos'] ?? 0,
                'acompanamientos' => (int) ($validated['acompanamientos'] ?? 0),
                'abanderamientos' => (int) ($validated['abanderamientos'] ?? 0),
                'auxilios_viales' => (int) ($validated['auxilios_viales'] ?? 0),
                'puestas_disposicion' => (int) ($validated['puestas_disposicion'] ?? 0),
                'vehiculos_recuperados' => (int) ($validated['vehiculos_recuperados'] ?? 0),
                'armas_aseguradas' => (int) ($validated['armas_aseguradas'] ?? 0),
                'mercancia_recuperada' => (int) ($validated['mercancia_recuperada'] ?? 0),
                'decomiso_drogas' => (int) ($validated['decomiso_drogas'] ?? 0),
                'crps_participantes' => $validated['crps_participantes'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            if ($request->hasFile('fotos')) {
                foreach ($request->file('fotos') as $file) {
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

            return redirect()->route('operativos.index')->with('success', 'Operativo creado correctamente.');
        });
    }

    public function show(Operativo $operativo)
    {
        $this->authorizeOperativoAccess($operativo);

        $operativo->load(['catalogo', 'unidad', 'delegacion', 'fotos', 'creador', 'editor']);

        return view('operativos.show', compact('operativo'));
    }

    public function edit(Operativo $operativo)
    {
        $this->authorize('editar operativos');
        $this->authorizeOperativoAccess($operativo);

        $catalogos = $this->catalogosVisiblesParaUsuario(Auth::user());

        $operativo->load(['fotos']);

        return view('operativos.edit', compact('operativo', 'catalogos'));
    }

    public function update(Request $request, Operativo $operativo)
    {
        $this->authorize('editar operativos');
        $this->authorizeOperativoAccess($operativo);

        $validated = $request->validate([
            'fecha' => 'required|date',
            'operativo_catalogo_id' => 'required|exists:operativo_catalogos,id',
            'lugar' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'dispositivos_realizados' => 'nullable|integer|min:0',
            'vehiculos_inspeccionados' => 'nullable|integer|min:0',
            'personas_inspeccionadas' => 'nullable|integer|min:0',
            'vehiculos_impactados' => 'nullable|integer|min:0',
            'personas_impactadas' => 'nullable|integer|min:0',
            'antecedentes_personas' => 'nullable|integer|min:0',
            'antecedentes_vehiculos' => 'nullable|integer|min:0',
            'antecedentes_motos' => 'nullable|integer|min:0',
            'antecedentes_camiones' => 'nullable|integer|min:0',
            'estado_fuerza_participante' => 'nullable|integer|min:0',
            'kilometros_recorridos' => 'nullable|numeric|min:0',
            'acompanamientos' => 'nullable|integer|min:0',
            'abanderamientos' => 'nullable|integer|min:0',
            'auxilios_viales' => 'nullable|integer|min:0',
            'puestas_disposicion' => 'nullable|integer|min:0',
            'vehiculos_recuperados' => 'nullable|integer|min:0',
            'armas_aseguradas' => 'nullable|integer|min:0',
            'mercancia_recuperada' => 'nullable|integer|min:0',
            'decomiso_drogas' => 'nullable|integer|min:0',
            'crps_participantes' => 'nullable|string',
            'observaciones' => 'nullable|string',
            'fotos' => 'nullable|array',
            'fotos.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096',
            'eliminar_fotos' => 'nullable|array',
            'eliminar_fotos.*' => 'integer|exists:operativo_fotos,id',
        ]);

        $catalogo = $this->catalogoPermitidoParaUsuario((int) $validated['operativo_catalogo_id'], Auth::user());

        if (!$catalogo) {
            return back()->withErrors([
                'operativo_catalogo_id' => 'El catálogo seleccionado no está disponible para su unidad.',
            ])->withInput();
        }

        return DB::transaction(function () use ($request, $validated, $operativo) {
            $user = Auth::user();

            $operativo->update([
                'fecha' => $validated['fecha'],
                'operativo_catalogo_id' => $validated['operativo_catalogo_id'],
                'lugar' => $validated['lugar'] ?? null,
                'descripcion' => $validated['descripcion'] ?? null,
                'dispositivos_realizados' => (int) ($validated['dispositivos_realizados'] ?? 0),
                'vehiculos_inspeccionados' => (int) ($validated['vehiculos_inspeccionados'] ?? 0),
                'personas_inspeccionadas' => (int) ($validated['personas_inspeccionadas'] ?? 0),
                'vehiculos_impactados' => (int) ($validated['vehiculos_impactados'] ?? 0),
                'personas_impactadas' => (int) ($validated['personas_impactadas'] ?? 0),
                'antecedentes_personas' => (int) ($validated['antecedentes_personas'] ?? 0),
                'antecedentes_vehiculos' => (int) ($validated['antecedentes_vehiculos'] ?? 0),
                'antecedentes_motos' => (int) ($validated['antecedentes_motos'] ?? 0),
                'antecedentes_camiones' => (int) ($validated['antecedentes_camiones'] ?? 0),
                'estado_fuerza_participante' => (int) ($validated['estado_fuerza_participante'] ?? 0),
                'kilometros_recorridos' => $validated['kilometros_recorridos'] ?? 0,
                'acompanamientos' => (int) ($validated['acompanamientos'] ?? 0),
                'abanderamientos' => (int) ($validated['abanderamientos'] ?? 0),
                'auxilios_viales' => (int) ($validated['auxilios_viales'] ?? 0),
                'puestas_disposicion' => (int) ($validated['puestas_disposicion'] ?? 0),
                'vehiculos_recuperados' => (int) ($validated['vehiculos_recuperados'] ?? 0),
                'armas_aseguradas' => (int) ($validated['armas_aseguradas'] ?? 0),
                'mercancia_recuperada' => (int) ($validated['mercancia_recuperada'] ?? 0),
                'decomiso_drogas' => (int) ($validated['decomiso_drogas'] ?? 0),
                'crps_participantes' => $validated['crps_participantes'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
                'updated_by' => $user->id,
            ]);

            if (!empty($validated['eliminar_fotos'])) {
                $fotosEliminar = $operativo->fotos()->whereIn('id', $validated['eliminar_fotos'])->get();

                foreach ($fotosEliminar as $foto) {
                    if (!empty($foto->foto_path) && Storage::disk('public')->exists($foto->foto_path)) {
                        Storage::disk('public')->delete($foto->foto_path);
                    }
                    $foto->delete();
                }
            }

            if ($request->hasFile('fotos')) {
                foreach ($request->file('fotos') as $file) {
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

            return redirect()->route('operativos.index')->with('success', 'Operativo actualizado correctamente.');
        });
    }

    public function destroy(Operativo $operativo)
    {
        $this->authorize('eliminar operativos');
        $this->authorizeOperativoAccess($operativo);

        return DB::transaction(function () use ($operativo) {
            $operativo->load('fotos');

            foreach ($operativo->fotos as $foto) {
                if (!empty($foto->foto_path) && Storage::disk('public')->exists($foto->foto_path)) {
                    Storage::disk('public')->delete($foto->foto_path);
                }
            }

            $operativo->delete();

            return back()->with('success', 'Operativo eliminado correctamente.');
        });
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

    private function catalogoPermitidoParaUsuario(int $catalogoId, $usuario): ?OperativoCatalogo
    {
        $query = OperativoCatalogo::query()
            ->where('id', $catalogoId)
            ->where('activo', 1);

        if (
            $usuario->hasRole('Superadmin')
            || $usuario->hasRole('Administrador')
            || $usuario->hasRole('Coordinador')
        ) {
            return $query->first();
        }

        $unidadId = (int) ($usuario->unidad_id ?? 0);

        return $query
            ->where(function ($q) use ($unidadId) {
                $q->whereNull('unidad_id');

                if ($unidadId > 0) {
                    $q->orWhere('unidad_id', $unidadId);
                }
            })
            ->first();
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

    private function authorizeOperativoAccess(Operativo $operativo): void
    {
        $query = Operativo::query()->whereKey($operativo->id);
        $this->applyOperativosVisibilityScope($query, Auth::user());

        abort_unless($query->exists(), 403);
    }
}
