<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Unidad;
use App\Models\Turno;
use App\Models\Patrulla;
use App\Models\Delegacion;
use App\Models\Destacamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index()
    {
        $actor = Auth::user();

        $users = $this->queryUsuariosVisiblesParaActor($actor)
            ->with(['roles', 'unidad', 'turno', 'patrulla', 'unidades', 'delegacion', 'destacamento'])
            ->get();

        return view('admin.settings.users.index', compact('users'));
    }

    public function create()
    {
        $actor = Auth::user();

        $roles = $this->rolesDisponiblesParaActor($actor);
        $unidades = $this->unidadesDisponiblesParaActor($actor);
        $turnos = $this->turnosDisponiblesParaActor();

        $unidadIdDefault = $this->actorEsSuperadmin($actor) ? null : $actor->unidad_id;

        $patrullas = $this->patrullasDisponiblesParaActor($actor, $unidadIdDefault);
        $delegaciones = $this->delegacionesDisponiblesParaActor();

        $unidadDelegacionesId = $this->unidadDelegacionesId();
        $unidadCarreterasId = $this->unidadCarreterasId();

        $destacamentos = $this->destacamentosDisponiblesParaActor($actor, $unidadIdDefault);

        return view('admin.settings.users.create', compact(
            'roles',
            'unidades',
            'turnos',
            'patrullas',
            'delegaciones',
            'unidadDelegacionesId',
            'unidadIdDefault',
            'unidadCarreterasId',
            'destacamentos'
        ));
    }

    public function store(Request $request)
    {
        $actor = Auth::user();
        $unidadCarreterasId = $this->unidadCarreterasId();

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'area' => 'nullable|string|max:30',
            'role' => 'required|exists:roles,name',
            'unidad_id' => 'nullable|exists:unidades,id',
            'turno_id' => 'nullable|exists:turnos,id',
            'patrulla_id' => 'nullable|exists:patrullas,id',
            'delegacion_id' => 'nullable|integer|exists:delegaciones,id',
            'destacamento_id' => 'nullable|integer|exists:destacamentos,id',
            'unidades_ids' => 'nullable|array',
            'unidades_ids.*' => 'integer|exists:unidades,id',
        ]);

        $rol = $this->buscarRolAsignableParaActor($actor, $validatedData['role']);

        if (!$rol) {
            throw ValidationException::withMessages([
                'role' => 'No puedes asignar ese rol.',
            ]);
        }

        $validatedData['unidad_id'] = $this->normalizarUnidadParaActor(
            $actor,
            $validatedData['unidad_id'] ?? null
        );

        if (!$this->unidadEsCompatibleConRol($rol, $validatedData['unidad_id'] ?? null)) {
            throw ValidationException::withMessages([
                'unidad_id' => 'La unidad seleccionada no es compatible con el rol elegido.',
            ]);
        }

        if (!$this->patrullaPerteneceAUnidad(
            $validatedData['patrulla_id'] ?? null,
            $validatedData['unidad_id'] ?? null
        )) {
            throw ValidationException::withMessages([
                'patrulla_id' => 'La patrulla seleccionada no pertenece a la unidad permitida.',
            ]);
        }

        if (!$this->isUnidadDelegaciones($validatedData['unidad_id'] ?? null)) {
            $validatedData['delegacion_id'] = null;
        }

        if (!$this->isUnidadCarreteras($validatedData['unidad_id'] ?? null)) {
            $validatedData['destacamento_id'] = null;
        } else {
            if (!empty($validatedData['destacamento_id'])) {
                $ok = Destacamento::query()
                    ->where('id', (int) $validatedData['destacamento_id'])
                    ->where('unidad_id', (int) $unidadCarreterasId)
                    ->exists();

                if (!$ok) {
                    throw ValidationException::withMessages([
                        'destacamento_id' => 'El destacamento seleccionado no pertenece a CARRETERAS.',
                    ]);
                }
            }
        }

        $unidadesExtra = $this->unidadesExtraPermitidasParaActor(
            $actor,
            $validatedData['unidades_ids'] ?? []
        );

        try {
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => bcrypt($validatedData['password']),
                'estado' => 'Activo',
                'area' => $validatedData['area'] ?? null,
                'unidad_id' => $validatedData['unidad_id'] ?? null,
                'turno_id' => $validatedData['turno_id'] ?? null,
                'patrulla_id' => $validatedData['patrulla_id'] ?? null,
                'delegacion_id' => $validatedData['delegacion_id'] ?? null,
                'destacamento_id' => $validatedData['destacamento_id'] ?? null,
            ]);

            $user->assignRole($rol->name);

            $user->unidades()->sync(!empty($unidadesExtra) ? $unidadesExtra : []);

            Log::info("Usuario creado exitosamente: {$user->name}");

            return redirect()->route('users.index')->with('success', 'Usuario creado correctamente.');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error("Error al crear el usuario: " . $e->getMessage());

            return redirect()->back()
                ->withErrors('Hubo un error al crear el usuario. Inténtelo nuevamente.')
                ->withInput();
        }
    }

    public function show($id)
    {
        $actor = Auth::user();

        $user = $this->queryUsuariosVisiblesParaActor($actor)
            ->with(['roles', 'unidad', 'turno', 'patrulla', 'unidades', 'delegacion', 'destacamento'])
            ->findOrFail($id);

        return view('admin.settings.users.show', compact('user'));
    }

    public function edit($id)
    {
        $actor = Auth::user();

        $user = $this->queryUsuariosVisiblesParaActor($actor)
            ->with(['roles', 'unidad', 'turno', 'patrulla', 'unidades', 'delegacion', 'destacamento'])
            ->findOrFail($id);

        $roles = $this->rolesDisponiblesParaActor($actor);
        $unidades = $this->unidadesDisponiblesParaActor($actor);
        $turnos = $this->turnosDisponiblesParaActor();

        $unidadIdParaPatrullas = $this->actorEsSuperadmin($actor)
            ? ($user->unidad_id ?? null)
            : $actor->unidad_id;

        $patrullas = $this->patrullasDisponiblesParaActor($actor, $unidadIdParaPatrullas);
        $delegaciones = $this->delegacionesDisponiblesParaActor();
        $unidadDelegacionesId = $this->unidadDelegacionesId();

        $unidadCarreterasId = $this->unidadCarreterasId();
        $destacamentos = $this->destacamentosDisponiblesParaActor($actor, $user->unidad_id ?? null);

        $unidadesExtraSeleccionadas = $user->unidades->pluck('id')->all();

        if (!$this->actorEsSuperadmin($actor)) {
            $unidadesExtraSeleccionadas = collect($unidadesExtraSeleccionadas)
                ->filter(fn ($unidadId) => (int) $unidadId === (int) $actor->unidad_id)
                ->values()
                ->all();
        }

        return view('admin.settings.users.edit', compact(
            'user',
            'roles',
            'unidades',
            'turnos',
            'patrullas',
            'delegaciones',
            'unidadDelegacionesId',
            'unidadesExtraSeleccionadas',
            'unidadCarreterasId',
            'destacamentos'
        ));
    }

    public function update(Request $request, $id)
    {
        $actor = Auth::user();

        $user = $this->queryUsuariosVisiblesParaActor($actor)
            ->with('roles')
            ->findOrFail($id);

        $unidadCarreterasId = $this->unidadCarreterasId();

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'area' => 'nullable|string|max:30',
            'role' => 'required|exists:roles,name',
            'password' => 'nullable|min:6|confirmed',
            'unidad_id' => 'nullable|exists:unidades,id',
            'turno_id' => 'nullable|exists:turnos,id',
            'patrulla_id' => 'nullable|exists:patrullas,id',
            'delegacion_id' => 'nullable|integer|exists:delegaciones,id',
            'destacamento_id' => 'nullable|integer|exists:destacamentos,id',
            'unidades_ids' => 'nullable|array',
            'unidades_ids.*' => 'integer|exists:unidades,id',
        ]);

        $rol = $this->buscarRolAsignableParaActor($actor, $validatedData['role']);

        if (!$rol) {
            throw ValidationException::withMessages([
                'role' => 'No puedes asignar ese rol.',
            ]);
        }

        if ($user->hasRole('Superadmin') && $rol->name !== 'Superadmin') {
            $superadmins = User::role('Superadmin')->count();

            if ($superadmins <= 1) {
                throw ValidationException::withMessages([
                    'role' => 'No puedes dejar el sistema sin Superadmin.',
                ]);
            }
        }

        $validatedData['unidad_id'] = $this->normalizarUnidadParaActor(
            $actor,
            $validatedData['unidad_id'] ?? null
        );

        if (!$this->unidadEsCompatibleConRol($rol, $validatedData['unidad_id'] ?? null)) {
            throw ValidationException::withMessages([
                'unidad_id' => 'La unidad seleccionada no es compatible con el rol elegido.',
            ]);
        }

        if (!$this->patrullaPerteneceAUnidad(
            $validatedData['patrulla_id'] ?? null,
            $validatedData['unidad_id'] ?? null
        )) {
            throw ValidationException::withMessages([
                'patrulla_id' => 'La patrulla seleccionada no pertenece a la unidad permitida.',
            ]);
        }

        if (!$this->isUnidadDelegaciones($validatedData['unidad_id'] ?? null)) {
            $validatedData['delegacion_id'] = null;
        }

        if (!$this->isUnidadCarreteras($validatedData['unidad_id'] ?? null)) {
            $validatedData['destacamento_id'] = null;
        } else {
            if (!empty($validatedData['destacamento_id'])) {
                $ok = Destacamento::query()
                    ->where('id', (int) $validatedData['destacamento_id'])
                    ->where('unidad_id', (int) $unidadCarreterasId)
                    ->exists();

                if (!$ok) {
                    throw ValidationException::withMessages([
                        'destacamento_id' => 'El destacamento seleccionado no pertenece a CARRETERAS.',
                    ]);
                }
            }
        }

        $unidadesExtra = $this->unidadesExtraPermitidasParaActor(
            $actor,
            $validatedData['unidades_ids'] ?? []
        );

        try {
            $user->update([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'area' => $validatedData['area'] ?? null,
                'unidad_id' => $validatedData['unidad_id'] ?? null,
                'turno_id' => $validatedData['turno_id'] ?? null,
                'patrulla_id' => $validatedData['patrulla_id'] ?? null,
                'delegacion_id' => $validatedData['delegacion_id'] ?? null,
                'destacamento_id' => $validatedData['destacamento_id'] ?? null,
            ]);

            if (!empty($validatedData['password'])) {
                $user->password = Hash::make($validatedData['password']);
                $user->save();
            }

            $user->syncRoles([$rol->name]);
            $user->unidades()->sync($unidadesExtra);

            Log::info("Usuario actualizado exitosamente: {$user->name}");

            return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente.');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error("Error al actualizar el usuario: " . $e->getMessage());

            return redirect()->back()
                ->withErrors('Hubo un error al actualizar el usuario. Inténtelo nuevamente.')
                ->withInput();
        }
    }

    public function destroy($id)
    {
        $actor = Auth::user();

        try {
            $user = $this->queryUsuariosVisiblesParaActor($actor)->findOrFail($id);

            if ($user->hasRole('Superadmin')) {
                $superadmins = User::role('Superadmin')->count();

                if ($superadmins <= 1) {
                    throw ValidationException::withMessages([
                        'user' => 'No puedes eliminar al último Superadmin.',
                    ]);
                }
            }

            $user->delete();

            Log::info("Usuario eliminado exitosamente: {$user->name}");

            return redirect()->route('users.index')->with('success', 'Usuario eliminado correctamente.');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        } catch (\Exception $e) {
            Log::error("Error al eliminar el usuario: " . $e->getMessage());
            return redirect()->back()->withErrors('Hubo un error al eliminar el usuario. Inténtelo nuevamente.');
        }
    }

    public function profile()
    {
        $user = Auth::user();
        return view('admin.settings.users.profile', compact('user'));
    }

    public function showChangePasswordForm()
    {
        return view('admin.settings.users.change-password');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return redirect()->back()->withErrors([
                'current_password' => 'La contraseña actual no coincide.'
            ]);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()->route('profile')->with('success', '¡Contraseña actualizada correctamente!');
    }

    private function unidadCarreterasId(): ?int
    {
        $id = Unidad::query()->where('slug', 'carreteras')->value('id');
        return $id ? (int) $id : null;
    }

    private function isUnidadCarreteras(?int $unidadId): bool
    {
        $carreterasId = $this->unidadCarreterasId();
        return $carreterasId !== null && (int) $unidadId === (int) $carreterasId;
    }

    private function destacamentosDisponiblesParaActor(User $actor, ?int $unidadId = null)
    {
        $carreterasId = $this->unidadCarreterasId();

        $query = Destacamento::query()
            ->where('activo', 1);

        if ($carreterasId === null) {
            return $query->whereRaw('1=0')->get();
        }

        if ($this->actorEsSuperadmin($actor)) {
            return $query
                ->where('unidad_id', $carreterasId)
                ->orderBy('nombre')
                ->get();
        }

        if ((int) ($actor->unidad_id ?? 0) === (int) $carreterasId) {
            return $query
                ->where('unidad_id', $carreterasId)
                ->orderBy('nombre')
                ->get();
        }

        return $query->whereRaw('1=0')->get();
    }

    private function unidadDelegacionesId(): ?int
    {
        $id = Unidad::query()->where('slug', 'delegaciones')->value('id');
        return $id ? (int) $id : null;
    }

    private function isUnidadDelegaciones(?int $unidadId): bool
    {
        $delegacionesId = $this->unidadDelegacionesId();
        return $delegacionesId !== null && (int) $unidadId === (int) $delegacionesId;
    }

    private function actorEsSuperadmin(User $actor): bool
    {
        return $actor->hasRole('Superadmin');
    }

    private function actorEsAdministrador(User $actor): bool
    {
        return $actor->hasRole('Administrador') && !$actor->hasRole('Superadmin');
    }

    private function unidadesDisponiblesParaActor(User $actor)
    {
        return Unidad::query()
            ->when(!$this->actorEsSuperadmin($actor), function ($q) use ($actor) {
                $q->where('id', $actor->unidad_id);
            })
            ->orderBy('nombre')
            ->get();
    }

    private function patrullasDisponiblesParaActor(User $actor, ?int $unidadId = null)
    {
        return Patrulla::query()
            ->when($this->actorEsSuperadmin($actor), function ($q) use ($unidadId) {
                if (!empty($unidadId)) {
                    $q->where('unidad_id', $unidadId);
                }
            })
            ->when(!$this->actorEsSuperadmin($actor), function ($q) use ($actor) {
                $q->where('unidad_id', $actor->unidad_id);
            })
            ->orderBy('numero_economico')
            ->get();
    }

    private function delegacionesDisponiblesParaActor()
    {
        return Delegacion::query()
            ->where('activa', 1)
            ->orderBy('nombre')
            ->get();
    }

    private function turnosDisponiblesParaActor()
    {
        return Turno::query()
            ->orderBy('nombre')
            ->get();
    }

    private function rolesDisponiblesParaActor(User $actor)
    {
        return $actor->rolesVisibles();
    }

    private function buscarRolAsignableParaActor(User $actor, string $roleName): ?Role
    {
        return $actor->rolesVisiblesQuery()
            ->where('name', $roleName)
            ->first();
    }

    private function unidadEsCompatibleConRol(Role $rol, ?int $unidadId): bool
    {
        if (is_null($rol->unidad_id)) {
            return true;
        }

        return !is_null($unidadId) && (int) $rol->unidad_id === (int) $unidadId;
    }

    private function normalizarUnidadParaActor(User $actor, ?int $unidadId): ?int
    {
        if ($this->actorEsSuperadmin($actor)) {
            return $unidadId;
        }

        return $actor->unidad_id;
    }

    private function patrullaPerteneceAUnidad(?int $patrullaId, ?int $unidadId): bool
    {
        if (empty($patrullaId)) {
            return true;
        }

        return Patrulla::query()
            ->where('id', $patrullaId)
            ->where('unidad_id', $unidadId)
            ->exists();
    }

    private function unidadesExtraPermitidasParaActor(User $actor, array $unidadesIds = []): array
    {
        $unidadesIds = array_map('intval', $unidadesIds);

        if ($this->actorEsSuperadmin($actor)) {
            return $unidadesIds;
        }

        if (empty($actor->unidad_id)) {
            return [];
        }

        return collect($unidadesIds)
            ->filter(fn ($id) => (int) $id === (int) $actor->unidad_id)
            ->values()
            ->all();
    }

    private function queryUsuariosVisiblesParaActor(User $actor)
    {
        return User::query()
            ->when(!$this->actorEsSuperadmin($actor), function ($q) use ($actor) {
                $q->whereDoesntHave('roles', function ($subQ) {
                    $subQ->where('name', 'Superadmin');
                });

                if ($this->actorEsAdministrador($actor)) {
                    $q->where('unidad_id', $actor->unidad_id);
                } else {
                    $q->where('id', $actor->id);
                }
            });
    }
}
