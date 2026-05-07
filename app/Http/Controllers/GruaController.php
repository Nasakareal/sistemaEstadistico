<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Grua;
use App\Models\Delegacion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GruaController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $usuario = Auth::user();

            if (
                !$usuario ||
                (
                    !$usuario->hasRole('Superadmin') &&
                    !in_array((int) $usuario->unidad_id, [1, 2, 3], true)
                )
            ) {
                abort(403);
            }

            return $next($request);
        });
    }

    public function index()
    {
        $usuario = Auth::user();

        $query = Grua::with(['unidades', 'delegaciones'])
            ->orderBy('nombre');

        if (!$usuario->hasRole('Superadmin') && (int) $usuario->unidad_id !== 3) {
            if ((int) $usuario->unidad_id === 1) {
                $query->whereHas('unidades', function ($q) {
                    $q->where('unidades.id', 1);
                });
            }

            if ((int) $usuario->unidad_id === 2) {
                $delegacionIds = $this->obtenerIdsDelegacionesUsuario($usuario);

                if (empty($delegacionIds)) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->whereHas('delegaciones', function ($q) use ($delegacionIds) {
                        $q->whereIn('delegaciones.id', $delegacionIds);
                    });
                }
            }
        }

        $gruas = $query->get();

        return view('gruas.index', compact('gruas'));
    }

    public function create()
    {
        $usuario = Auth::user();

        if ((int) $usuario->unidad_id === 1 && !$usuario->hasRole('Superadmin') && (int) $usuario->unidad_id !== 3) {
            $delegaciones = collect();
        } elseif ((int) $usuario->unidad_id === 2 && !$usuario->hasRole('Superadmin') && (int) $usuario->unidad_id !== 3) {
            $delegacionIds = $this->obtenerIdsDelegacionesUsuario($usuario);

            $delegaciones = Delegacion::where('activa', 1)
                ->whereIn('id', $delegacionIds)
                ->where('nombre', '!=', 'Morelia')
                ->orderBy('nombre')
                ->get();
        } else {
            $delegaciones = Delegacion::where('activa', 1)
                ->where('nombre', '!=', 'Morelia')
                ->orderBy('nombre')
                ->get();
        }

        return view('gruas.create', compact('delegaciones'));
    }

    public function store(Request $request)
    {
        $usuario = Auth::user();

        $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'nullable|string',
            'ubicacion_corralon' => 'nullable|string',
            'telefono' => 'nullable|string|max:15',
            'email' => 'nullable|email',
            'unidades' => 'nullable|array',
            'unidades.*' => 'integer|exists:unidades,id',
            'delegaciones' => 'nullable|array',
            'delegaciones.*' => 'integer|exists:delegaciones,id',
        ]);

        $unidades = collect($request->input('unidades', []))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();

        $delegaciones = collect($request->input('delegaciones', []))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();

        if (!$usuario->hasRole('Superadmin') && (int) $usuario->unidad_id !== 3) {
            if ((int) $usuario->unidad_id === 1) {
                $unidades = in_array(1, $unidades, true) ? [1] : [];
                $delegaciones = [];
            }

            if ((int) $usuario->unidad_id === 2) {
                $delegacionesPermitidas = $this->obtenerIdsDelegacionesUsuario($usuario);
                $delegaciones = array_values(array_intersect($delegaciones, $delegacionesPermitidas));
                $unidades = !empty($delegaciones) ? [2] : [];
            }
        } else {
            if (!empty($delegaciones) && !in_array(2, $unidades, true)) {
                $unidades[] = 2;
            }
        }

        if (empty($unidades) && empty($delegaciones)) {
            return back()->withErrors([
                'unidades' => 'Selecciona al menos una asignación.'
            ])->withInput();
        }

        $existia = DB::transaction(function () use ($request, $unidades, $delegaciones) {
            $data = $this->normalizarDatosGrua($request);

            $grua = $this->buscarGruaPorNombreNormalizado($data['nombre']);
            $existia = $grua !== null;

            if ($grua) {
                $this->completarDatosGruaSinPisar($grua, $data);
            } else {
                $grua = Grua::create($data);
            }

            $grua->unidades()->syncWithoutDetaching($unidades);
            $grua->delegaciones()->syncWithoutDetaching($delegaciones);

            return $existia;
        });

        return redirect()->route('gruas.index')
            ->with('success', $existia
                ? 'La grúa ya existía; se agregaron las asignaciones seleccionadas.'
                : 'Grúa registrada correctamente.');
    }

    public function show($id)
    {
        $grua = Grua::with(['unidades', 'delegaciones'])->findOrFail($id);
        $this->autorizarAccesoAGrua($grua);

        return view('gruas.show', compact('grua'));
    }

    public function edit($id)
    {
        $usuario = Auth::user();
        $grua = Grua::with(['unidades', 'delegaciones'])->findOrFail($id);
        $this->autorizarAccesoAGrua($grua);

        if ((int) $usuario->unidad_id === 1 && !$usuario->hasRole('Superadmin') && (int) $usuario->unidad_id !== 3) {
            $delegaciones = collect();
        } elseif ((int) $usuario->unidad_id === 2 && !$usuario->hasRole('Superadmin') && (int) $usuario->unidad_id !== 3) {
            $delegacionIds = $this->obtenerIdsDelegacionesUsuario($usuario);

            $delegaciones = Delegacion::where('activa', 1)
                ->whereIn('id', $delegacionIds)
                ->where('nombre', '!=', 'Morelia')
                ->orderBy('nombre')
                ->get();
        } else {
            $delegaciones = Delegacion::where('activa', 1)
                ->where('nombre', '!=', 'Morelia')
                ->orderBy('nombre')
                ->get();
        }

        return view('gruas.edit', compact('grua', 'delegaciones'));
    }

    public function update(Request $request, $id)
    {
        $usuario = Auth::user();
        $grua = Grua::findOrFail($id);
        $this->autorizarAccesoAGrua($grua);

        $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'nullable|string',
            'ubicacion_corralon' => 'nullable|string',
            'telefono' => 'nullable|string|max:15',
            'email' => 'nullable|email',
            'unidades' => 'nullable|array',
            'unidades.*' => 'integer|exists:unidades,id',
            'delegaciones' => 'nullable|array',
            'delegaciones.*' => 'integer|exists:delegaciones,id',
        ]);

        $unidades = collect($request->input('unidades', []))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();

        $delegaciones = collect($request->input('delegaciones', []))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();

        if (!$usuario->hasRole('Superadmin') && (int) $usuario->unidad_id !== 3) {
            if ((int) $usuario->unidad_id === 1) {
                $unidades = in_array(1, $unidades, true) ? [1] : [];
                $delegaciones = [];
            }

            if ((int) $usuario->unidad_id === 2) {
                $delegacionesPermitidas = $this->obtenerIdsDelegacionesUsuario($usuario);
                $delegaciones = array_values(array_intersect($delegaciones, $delegacionesPermitidas));
                $unidades = !empty($delegaciones) ? [2] : [];
            }
        } else {
            if (!empty($delegaciones) && !in_array(2, $unidades, true)) {
                $unidades[] = 2;
            }
        }

        if (empty($unidades) && empty($delegaciones)) {
            return back()->withErrors([
                'unidades' => 'Selecciona al menos una asignación.'
            ])->withInput();
        }

        $data = $this->normalizarDatosGrua($request);
        $nombreActual = $this->normalizarTextoGrua($grua->nombre);

        if ($data['nombre'] !== $nombreActual && $this->buscarGruaPorNombreNormalizado($data['nombre'], (int) $grua->id)) {
            return back()->withErrors([
                'nombre' => 'Ya existe una grúa con ese nombre. Edita esa grúa para agregarle unidades o delegaciones.'
            ])->withInput();
        }

        DB::transaction(function () use ($grua, $data, $unidades, $delegaciones) {
            $grua->update($data);

            $grua->unidades()->sync($unidades);
            $grua->delegaciones()->sync($delegaciones);
        });

        return redirect()->route('gruas.index')
            ->with('success', 'Grúa actualizada correctamente.');
    }

    public function destroy($id)
    {
        $grua = Grua::findOrFail($id);
        $this->autorizarAccesoAGrua($grua);

        $grua->delete();

        return redirect()->route('gruas.index')
            ->with('success', 'Grúa eliminada correctamente.');
    }

    private function obtenerIdsDelegacionesUsuario($usuario): array
    {
        $ids = [];

        if (!empty($usuario->delegacion_id)) {
            $ids[] = (int) $usuario->delegacion_id;
        }

        $idsPivot = DB::table('delegacion_user')
            ->where('user_id', $usuario->id)
            ->pluck('delegacion_id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->toArray();

        return array_values(array_unique(array_merge($ids, $idsPivot)));
    }

    private function autorizarAccesoAGrua(Grua $grua): void
    {
        $usuario = Auth::user();

        if ($usuario->hasRole('Superadmin') || (int) $usuario->unidad_id === 3) {
            return;
        }

        if ((int) $usuario->unidad_id === 1) {
            $permitida = $grua->unidades()->where('unidades.id', 1)->exists();
            abort_unless($permitida, 403);
            return;
        }

        if ((int) $usuario->unidad_id === 2) {
            $delegacionIds = $this->obtenerIdsDelegacionesUsuario($usuario);

            $permitida = !empty($delegacionIds)
                && $grua->delegaciones()->whereIn('delegaciones.id', $delegacionIds)->exists();

            abort_unless($permitida, 403);
            return;
        }

        abort(403);
    }

    private function normalizarDatosGrua(Request $request): array
    {
        $data = $request->only([
            'nombre',
            'direccion',
            'ubicacion_corralon',
            'telefono',
            'email',
        ]);

        return [
            'nombre' => $this->normalizarTextoGrua($data['nombre'] ?? ''),
            'direccion' => $this->normalizarTextoGrua($data['direccion'] ?? null),
            'ubicacion_corralon' => $this->normalizarTextoGrua($data['ubicacion_corralon'] ?? null),
            'telefono' => $this->normalizarTextoGrua($data['telefono'] ?? null),
            'email' => $this->normalizarTextoGrua($data['email'] ?? null),
        ];
    }

    private function normalizarTextoGrua($value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        if ($value === null || $value === '') {
            return null;
        }

        return mb_strtoupper($value, 'UTF-8');
    }

    private function buscarGruaPorNombreNormalizado(?string $nombre, ?int $exceptoId = null): ?Grua
    {
        if ($nombre === null || $nombre === '') {
            return null;
        }

        return Grua::query()
            ->whereRaw('UPPER(TRIM(nombre)) = ?', [$nombre])
            ->when($exceptoId, function ($query) use ($exceptoId) {
                $query->where('id', '!=', $exceptoId);
            })
            ->orderBy('id')
            ->first();
    }

    private function completarDatosGruaSinPisar(Grua $grua, array $data): void
    {
        $updates = [];

        foreach (['direccion', 'ubicacion_corralon', 'telefono', 'email'] as $field) {
            $actual = trim((string) ($grua->{$field} ?? ''));
            $nuevo = $data[$field] ?? null;

            if ($actual === '' && $nuevo !== null && $nuevo !== '') {
                $updates[$field] = $nuevo;
            }
        }

        if (!empty($updates)) {
            $grua->update($updates);
        }
    }
}
