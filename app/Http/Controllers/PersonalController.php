<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Models\Unidad;
use App\Models\Turno;
use App\Models\Patrulla;
use App\Models\Armamento;
use App\Models\PersonalAsignacion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PersonalController extends Controller
{
    private function actor()
    {
        return Auth::user();
    }

    private function actorEsSuperadmin(): bool
    {
        $actor = $this->actor();
        return $actor && $actor->hasRole('Superadmin');
    }

    private function actorEsAdministrador(): bool
    {
        $actor = $this->actor();
        return $actor && $actor->hasRole('Administrador') && !$actor->hasRole('Superadmin');
    }

    private function unidadIdActor(): ?int
    {
        return optional($this->actor())->unidad_id;
    }

    private function queryPersonalVisibleParaActor()
    {
        return Personal::query()
            ->when(!$this->actorEsSuperadmin(), function ($q) {
                $q->where('unidad_id', $this->unidadIdActor());
            });
    }

    private function buscarPersonalVisibleOFail($id): Personal
    {
        return $this->queryPersonalVisibleParaActor()->findOrFail($id);
    }

    private function unidadesDisponiblesParaActor()
    {
        return Unidad::query()
            ->where('activa', 1)
            ->when(!$this->actorEsSuperadmin(), function ($q) {
                $q->where('id', $this->unidadIdActor());
            })
            ->orderBy('nombre')
            ->get();
    }

    private function turnosDisponiblesParaActor()
    {
        return Turno::query()
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get();
    }

    private function patrullasDisponiblesParaActor(?int $unidadId = null, ?int $personalIdExcluir = null)
    {
        return Patrulla::query()
            ->where('activa', 1)
            ->when($this->actorEsSuperadmin(), function ($q) use ($unidadId) {
                if (!empty($unidadId)) {
                    $q->where('unidad_id', $unidadId);
                }
            })
            ->when(!$this->actorEsSuperadmin(), function ($q) {
                $q->where('unidad_id', $this->unidadIdActor());
            })
            ->when($personalIdExcluir !== null, function ($q) use ($personalIdExcluir) {
                $q->whereDoesntHave('personal', function ($subQ) use ($personalIdExcluir) {
                    $subQ->whereNull('deleted_at')
                        ->where('estatus', 'ACTIVO')
                        ->where('id', '!=', $personalIdExcluir);
                });
            }, function ($q) {
                $q->whereDoesntHave('personal', function ($subQ) {
                    $subQ->whereNull('deleted_at')
                        ->where('estatus', 'ACTIVO');
                });
            })
            ->orderBy('numero_economico')
            ->get();
    }

    private function usuariosDisponiblesParaActor(?int $userIdActual = null)
    {
        return User::query()
            ->when(!$this->actorEsSuperadmin(), function ($q) {
                $q->where('unidad_id', $this->unidadIdActor())
                  ->whereDoesntHave('roles', function ($subQ) {
                      $subQ->where('name', 'Superadmin');
                  });
            })
            ->where(function ($q) use ($userIdActual) {
                $q->whereDoesntHave('personal');

                if ($userIdActual) {
                    $q->orWhere('id', $userIdActual);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'unidad_id']);
    }

    private function normalizarUnidadParaActor(?int $unidadId): ?int
    {
        if ($this->actorEsSuperadmin()) {
            return $unidadId;
        }

        return $this->unidadIdActor();
    }

    private function patrullaPerteneceAUnidad(?int $patrullaId, ?int $unidadId): bool
    {
        if (empty($patrullaId)) {
            return true;
        }

        return Patrulla::query()
            ->where('id', $patrullaId)
            ->where('activa', 1)
            ->where('unidad_id', $unidadId)
            ->exists();
    }

    private function usuarioPerteneceAUnidadPermitida(?int $userId, ?int $unidadId, ?int $personalIdActual = null): bool
    {
        if (empty($userId)) {
            return true;
        }

        $query = User::query()
            ->where('id', $userId)
            ->where('unidad_id', $unidadId);

        if (!$this->actorEsSuperadmin()) {
            $query->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'Superadmin');
            });
        }

        $user = $query->first();

        if (!$user) {
            return false;
        }

        $ocupado = Personal::query()
            ->where('user_id', $userId)
            ->when($personalIdActual !== null, function ($q) use ($personalIdActual) {
                $q->where('id', '!=', $personalIdActual);
            })
            ->exists();

        return !$ocupado;
    }

    public function index()
    {
        $personals = $this->queryPersonalVisibleParaActor()
            ->with(['unidad', 'turno', 'patrulla', 'user'])
            ->orderByDesc('estatus')
            ->orderBy('nombre')
            ->orderBy('ap_paterno')
            ->orderBy('ap_materno')
            ->get();

        return view('admin.settings.personal.index', compact('personals'));
    }

    public function create()
    {
        $unidadIdDefault = $this->actorEsSuperadmin() ? null : $this->unidadIdActor();

        $unidades = $this->unidadesDisponiblesParaActor();
        $turnos = $this->turnosDisponiblesParaActor();
        $patrullas = $this->patrullasDisponiblesParaActor($unidadIdDefault);
        $usuariosDisponibles = $this->usuariosDisponiblesParaActor();
        $categoriasPersonal = ['OPERATIVO', 'ADMINISTRATIVO'];

        return view('admin.settings.personal.create', compact(
            'unidades',
            'turnos',
            'patrullas',
            'usuariosDisponibles',
            'categoriasPersonal',
            'unidadIdDefault'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'unidad_id' => 'required|exists:unidades,id',
            'turno_id' => 'nullable|exists:turnos,id',
            'patrulla_id' => 'nullable|exists:patrullas,id',
            'user_id' => 'nullable|exists:users,id|unique:personals,user_id',

            'nombre' => 'required|string|max:100',
            'ap_paterno' => 'nullable|string|max:100',
            'ap_materno' => 'nullable|string|max:100',

            'curp' => 'nullable|string|max:18|unique:personals,curp',
            'rfc' => 'nullable|string|max:13',

            'cuip' => 'nullable|string|max:30|unique:personals,cuip',

            'grado' => 'nullable|string|max:120',
            'puesto' => 'nullable|string|max:120',

            'adscripcion' => 'nullable|string|max:200',
            'area' => 'nullable|string|max:200',

            'categoria' => 'required|in:OPERATIVO,ADMINISTRATIVO',
            'estatus' => 'required|string|max:30',
            'fecha_ingreso' => 'nullable|date',
            'fecha_baja' => 'nullable|date',
        ]);

        $validated['unidad_id'] = $this->normalizarUnidadParaActor($validated['unidad_id'] ?? null);

        try {
            if (!empty($validated['patrulla_id'])) {
                if (!$this->patrullaPerteneceAUnidad($validated['patrulla_id'], $validated['unidad_id'])) {
                    return redirect()->back()
                        ->withErrors(['patrulla_id' => 'La patrulla seleccionada no pertenece a la unidad permitida o no está activa.'])
                        ->withInput();
                }

                $ocupada = Personal::query()
                    ->whereNull('deleted_at')
                    ->where('estatus', 'ACTIVO')
                    ->where('patrulla_id', $validated['patrulla_id'])
                    ->exists();

                if ($ocupada) {
                    return redirect()->back()
                        ->withErrors(['patrulla_id' => 'Esa patrulla ya está asignada a otro elemento ACTIVO.'])
                        ->withInput();
                }
            }

            if (!empty($validated['user_id'])) {
                if (!$this->usuarioPerteneceAUnidadPermitida($validated['user_id'], $validated['unidad_id'])) {
                    return redirect()->back()
                        ->withErrors(['user_id' => 'El usuario seleccionado no pertenece a la unidad permitida o ya está asignado a otro registro de personal.'])
                        ->withInput();
                }
            }

            Personal::create($validated);

            return redirect()
                ->route('personal.index')
                ->with('success', 'Personal creado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al crear personal: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withErrors('Hubo un error al crear el personal. Inténtelo nuevamente.')
                ->withInput();
        }
    }

    public function show($id)
    {
        $personal = $this->buscarPersonalVisibleOFail($id);

        $personal->load([
            'user',
            'unidad',
            'turno',
            'patrulla',
            'rolesServicio',
            'incidencias',
            'documentos',
            'asignaciones',
            'contactos',
            'domicilios',
            'emergencias',
            'asignaciones.armamento',
        ]);

        $asignacionesArmamentoActivas = PersonalAsignacion::query()
            ->where('personal_id', $personal->id)
            ->whereNotNull('armamento_id')
            ->whereNull('fecha_fin')
            ->with('armamento')
            ->orderByDesc('fecha_asignacion')
            ->get();

        $armamentosDisponibles = Armamento::query()
            ->where('estatus', 'ACTIVO')
            ->where('unidad_id', $personal->unidad_id)
            ->whereDoesntHave('asignaciones', function ($q) {
                $q->whereNull('fecha_fin');
            })
            ->orderBy('tipo')
            ->orderBy('clase')
            ->orderBy('marca')
            ->orderBy('modelo')
            ->get();

        return view('admin.settings.personal.show', compact(
            'personal',
            'armamentosDisponibles',
            'asignacionesArmamentoActivas'
        ));
    }

    public function edit($id)
    {
        $personal = $this->buscarPersonalVisibleOFail($id);

        $unidades = $this->unidadesDisponiblesParaActor();
        $turnos = $this->turnosDisponiblesParaActor();
        $patrullas = $this->patrullasDisponiblesParaActor(
            $this->actorEsSuperadmin() ? $personal->unidad_id : $this->unidadIdActor(),
            $personal->id
        );

        $usuariosDisponibles = $this->usuariosDisponiblesParaActor($personal->user_id);
        $categoriasPersonal = ['OPERATIVO', 'ADMINISTRATIVO'];

        return view('admin.settings.personal.edit', compact(
            'personal',
            'unidades',
            'turnos',
            'patrullas',
            'usuariosDisponibles',
            'categoriasPersonal'
        ));
    }

    public function update(Request $request, $id)
    {
        $personal = $this->buscarPersonalVisibleOFail($id);

        $validated = $request->validate([
            'unidad_id' => 'required|exists:unidades,id',
            'turno_id' => 'nullable|exists:turnos,id',
            'patrulla_id' => 'nullable|exists:patrullas,id',
            'user_id' => 'nullable|exists:users,id|unique:personals,user_id,' . $personal->id,

            'nombre' => 'required|string|max:100',
            'ap_paterno' => 'nullable|string|max:100',
            'ap_materno' => 'nullable|string|max:100',

            'curp' => 'nullable|string|max:18|unique:personals,curp,' . $personal->id,
            'rfc' => 'nullable|string|max:13',

            'cuip' => 'nullable|string|max:30|unique:personals,cuip,' . $personal->id,

            'grado' => 'nullable|string|max:120',
            'puesto' => 'nullable|string|max:120',

            'adscripcion' => 'nullable|string|max:200',
            'area' => 'nullable|string|max:200',

            'categoria' => 'required|in:OPERATIVO,ADMINISTRATIVO',
            'estatus' => 'required|string|max:30',
            'fecha_ingreso' => 'nullable|date',
            'fecha_baja' => 'nullable|date',
        ]);

        $validated['unidad_id'] = $this->normalizarUnidadParaActor($validated['unidad_id'] ?? null);

        try {
            if (!empty($validated['patrulla_id'])) {
                if (!$this->patrullaPerteneceAUnidad($validated['patrulla_id'], $validated['unidad_id'])) {
                    return redirect()->back()
                        ->withErrors(['patrulla_id' => 'La patrulla seleccionada no pertenece a la unidad permitida o no está activa.'])
                        ->withInput();
                }

                $ocupada = Personal::query()
                    ->whereNull('deleted_at')
                    ->where('estatus', 'ACTIVO')
                    ->where('id', '!=', $personal->id)
                    ->where('patrulla_id', $validated['patrulla_id'])
                    ->exists();

                if ($ocupada) {
                    return redirect()->back()
                        ->withErrors(['patrulla_id' => 'Esa patrulla ya está asignada a otro elemento ACTIVO.'])
                        ->withInput();
                }
            }

            if (!empty($validated['user_id'])) {
                if (!$this->usuarioPerteneceAUnidadPermitida($validated['user_id'], $validated['unidad_id'], $personal->id)) {
                    return redirect()->back()
                        ->withErrors(['user_id' => 'El usuario seleccionado no pertenece a la unidad permitida o ya está asignado a otro registro de personal.'])
                        ->withInput();
                }
            }

            $personal->update($validated);

            return redirect()
                ->route('personal.index')
                ->with('success', 'Personal actualizado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al actualizar personal: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withErrors('Hubo un error al actualizar el personal. Inténtelo nuevamente.')
                ->withInput();
        }
    }

    public function destroy($id)
    {
        $personal = $this->buscarPersonalVisibleOFail($id);

        try {
            $personal->delete();

            return redirect()
                ->route('personal.index')
                ->with('success', 'Personal eliminado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar personal: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withErrors('Hubo un error al eliminar el personal. Inténtelo nuevamente.');
        }
    }
}
