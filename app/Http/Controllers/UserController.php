<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Unidad;
use App\Models\Turno;
use App\Models\Patrulla;
use App\Models\Delegacion;
use App\Models\Destacamento;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $actor = Auth::user();
        $unidadId = (int) $request->query('unidad_id', 0);

        $users = $this->queryUsuariosVisiblesParaActor($actor)
            ->with(['roles', 'unidad', 'turno', 'patrulla', 'delegacion', 'destacamento'])
            ->when($this->actorTieneVisibilidadGlobal($actor) && $unidadId > 0, function ($query) use ($unidadId) {
                $query->where('unidad_id', $unidadId);
            })
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
            'name' => 'nullable|string|max:255',
            'apellido_paterno' => 'nullable|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',
            'nombres' => 'required_without:name|nullable|string|max:255',
            'email' => 'required|email|unique:users,email',
            'telefono' => 'nullable|string|max:30',
            'telefono_whatsapp_operativo' => 'nullable|string|max:30',
            'password' => 'required|min:6|confirmed',
            'area' => 'nullable|string|max:30',
            'role_id' => 'required|integer|exists:roles,id',
            'unidad_id' => 'nullable|exists:unidades,id',
            'turno_id' => 'nullable|exists:turnos,id',
            'patrulla_id' => 'nullable|exists:patrullas,id',
            'delegacion_id' => 'nullable|integer|exists:delegaciones,id',
            'destacamento_id' => 'nullable|integer|exists:destacamentos,id',
        ]);

        $validatedData = $this->normalizarNombreUsuario($validatedData);
        $validatedData = $this->normalizarTelefonosWhatsAppParaActor($validatedData, $actor);

        $rol = $this->buscarRolAsignableParaActor($actor, (int) $validatedData['role_id']);

        if (!$rol) {
            throw ValidationException::withMessages([
                'role_id' => 'No puedes asignar ese rol.',
            ]);
        }

        $validatedData['unidad_id'] = $this->normalizarUnidadParaActor(
            $actor,
            $validatedData['unidad_id'] ?? null,
            $rol
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
        } elseif (!$this->delegacionEstaActiva($validatedData['delegacion_id'] ?? null)) {
            throw ValidationException::withMessages([
                'delegacion_id' => 'La delegación seleccionada no está activa.',
            ]);
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

        try {
            $user = User::create([
                'name' => $validatedData['name'],
                'apellido_paterno' => $validatedData['apellido_paterno'],
                'apellido_materno' => $validatedData['apellido_materno'],
                'nombres' => $validatedData['nombres'],
                'email' => $validatedData['email'],
                'telefono' => $validatedData['telefono'],
                'telefono_whatsapp_operativo' => $validatedData['telefono_whatsapp_operativo'],
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
            ->with(['roles', 'unidad', 'turno', 'patrulla', 'delegacion', 'destacamento'])
            ->findOrFail($id);

        return view('admin.settings.users.show', compact('user'));
    }

    public function edit($id)
    {
        $actor = Auth::user();

        $user = $this->queryUsuariosVisiblesParaActor($actor)
            ->with(['roles', 'unidad', 'turno', 'patrulla', 'delegacion', 'destacamento'])
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

        return view('admin.settings.users.edit', compact(
            'user',
            'roles',
            'unidades',
            'turnos',
            'patrullas',
            'delegaciones',
            'unidadDelegacionesId',
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
            'name' => 'nullable|string|max:255',
            'apellido_paterno' => 'nullable|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',
            'nombres' => 'required_without:name|nullable|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'telefono' => 'nullable|string|max:30',
            'telefono_whatsapp_operativo' => 'nullable|string|max:30',
            'area' => 'nullable|string|max:30',
            'role_id' => 'required|integer|exists:roles,id',
            'password' => 'nullable|min:6|confirmed',
            'unidad_id' => 'nullable|exists:unidades,id',
            'turno_id' => 'nullable|exists:turnos,id',
            'patrulla_id' => 'nullable|exists:patrullas,id',
            'delegacion_id' => 'nullable|integer|exists:delegaciones,id',
            'destacamento_id' => 'nullable|integer|exists:destacamentos,id',
        ]);

        $validatedData = $this->normalizarNombreUsuario($validatedData);
        $validatedData = $this->normalizarTelefonosWhatsAppParaActor($validatedData, $actor, $user);

        $rol = $this->buscarRolAsignableParaActor($actor, (int) $validatedData['role_id']);

        if (!$rol) {
            throw ValidationException::withMessages([
                'role_id' => 'No puedes asignar ese rol.',
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
            $validatedData['unidad_id'] ?? null,
            $rol
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
        } elseif (!$this->delegacionEstaActiva($validatedData['delegacion_id'] ?? null)) {
            throw ValidationException::withMessages([
                'delegacion_id' => 'La delegación seleccionada no está activa.',
            ]);
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

        try {
            $user->update([
                'name' => $validatedData['name'],
                'apellido_paterno' => $validatedData['apellido_paterno'],
                'apellido_materno' => $validatedData['apellido_materno'],
                'nombres' => $validatedData['nombres'],
                'email' => $validatedData['email'],
                'telefono' => $validatedData['telefono'],
                'telefono_whatsapp_operativo' => $validatedData['telefono_whatsapp_operativo'],
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

    private function normalizarNombreUsuario(array $validated): array
    {
        $validated['apellido_paterno'] = $this->limpiarTexto($validated['apellido_paterno'] ?? null);
        $validated['apellido_materno'] = $this->limpiarTexto($validated['apellido_materno'] ?? null);
        $validated['nombres'] = $this->limpiarTexto($validated['nombres'] ?? null)
            ?: $this->limpiarTexto($validated['name'] ?? null);

        $validated['name'] = User::nombreCompleto(
            $validated['nombres'],
            $validated['apellido_paterno'],
            $validated['apellido_materno']
        );

        return $validated;
    }

    private function limpiarTexto(?string $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function normalizarTelefonosWhatsAppParaActor(
        array $validatedData,
        User $actor,
        ?User $user = null
    ): array {
        if (!$this->actorEsSuperadmin($actor)) {
            $validatedData['telefono'] = $user ? $user->telefono : null;
            $validatedData['telefono_whatsapp_operativo'] = $user
                ? $user->telefono_whatsapp_operativo
                : null;

            return $validatedData;
        }

        $campos = [
            'telefono' => 'El WhatsApp autorizado ya está registrado en otro usuario.',
            'telefono_whatsapp_operativo' => 'El WhatsApp operativo ya está registrado en otro usuario.',
        ];

        foreach ($campos as $campo => $mensaje) {
            $validatedData[$campo] = $this->normalizarTelefonoMx($validatedData[$campo] ?? null);

            if (is_null($validatedData[$campo])) {
                continue;
            }

            $exists = User::query()
                ->where($campo, $validatedData[$campo])
                ->when($user, fn ($query) => $query->where('id', '!=', $user->id))
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    $campo => $mensaje,
                ]);
            }
        }

        return $validatedData;
    }

    private function normalizarTelefonoMx(?string $telefono): ?string
    {
        if (is_null($telefono) || trim($telefono) === '') {
            return null;
        }

        $telefono = preg_replace('/\D+/', '', $telefono);

        if ($telefono === '') {
            return null;
        }

        if (strlen($telefono) === 10) {
            return '521' . $telefono;
        }

        if (strlen($telefono) === 12 && str_starts_with($telefono, '52')) {
            return '521' . substr($telefono, 2);
        }

        if (strlen($telefono) === 13 && str_starts_with($telefono, '521')) {
            return $telefono;
        }

        return $telefono;
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

    private function actorTieneVisibilidadGlobal(User $actor): bool
    {
        return $this->actorEsSuperadmin($actor) || (int) ($actor->unidad_id ?? 0) === 3;
    }

    private function unidadesDisponiblesParaActor(User $actor)
    {
        return Unidad::query()
            ->when(!$this->actorTieneVisibilidadGlobal($actor), function ($q) use ($actor) {
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
        return Role::query()
            ->with('unidad')
            ->orderBy('roles.name')
            ->get()
            ->filter(fn (Role $role) => $actor->puedeVerRol($role))
            ->values();
    }

    private function buscarRolAsignableParaActor(User $actor, int $roleId): ?Role
    {
        $rol = Role::query()->with('unidad')->find($roleId);

        if (!$rol) {
            return null;
        }

        if ($actor->hasRole('Administrador') && !$actor->hasRole('Superadmin')) {
            if ($rol->name === 'Administrador') {
                return null;
            }
        }

        if (!$actor->puedeVerRol($rol)) {
            return null;
        }

        return $rol;
    }

    private function unidadEsCompatibleConRol(Role $rol, ?int $unidadId): bool
    {
        $unidadRolId = $rol->unidadIdEfectiva();

        if (is_null($unidadRolId)) {
            return true;
        }

        return !is_null($unidadId) && (int) $unidadRolId === (int) $unidadId;
    }

    private function normalizarUnidadParaActor(User $actor, ?int $unidadId, ?Role $rol = null): ?int
    {
        if (!$this->actorEsSuperadmin($actor)) {
            return $actor->unidad_id;
        }

        $unidadRolId = $rol ? $rol->unidadIdEfectiva() : null;

        return !is_null($unidadRolId) ? (int) $unidadRolId : $unidadId;
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

    private function delegacionEstaActiva(?int $delegacionId): bool
    {
        if (empty($delegacionId)) {
            return true;
        }

        return Delegacion::query()
            ->whereKey($delegacionId)
            ->where('activa', 1)
            ->exists();
    }

    private function queryUsuariosVisiblesParaActor(User $actor)
    {
        return User::query()
            ->when(!$this->actorEsSuperadmin($actor), function ($q) use ($actor) {
                $q->whereDoesntHave('roles', function ($subQ) {
                    $subQ->where('name', 'Superadmin');
                });

                if ($this->actorTieneVisibilidadGlobal($actor)) {
                    return;
                }

                $q->where('unidad_id', $actor->unidad_id);
            });
    }
}
