<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    private function actorEsSuperadmin(?User $actor = null): bool
    {
        $actor = $actor ?: Auth::user();
        return $actor && $actor->hasRole('Superadmin');
    }

    private function rolesVisiblesParaActor(User $actor)
    {
        return Role::query()
            ->with('unidad')
            ->orderBy('name')
            ->get()
            ->filter(fn (Role $role) => $actor->puedeVerRol($role))
            ->values();
    }

    private function buscarRolVisibleOFail($id): Role
    {
        $actor = Auth::user();
        $role = Role::query()->with('unidad')->findOrFail($id);

        if (!$actor || !$actor->puedeVerRol($role)) {
            abort(404);
        }

        return $role;
    }

    private function buscarRolAdministrableOFail($id): Role
    {
        $role = $this->buscarRolVisibleOFail($id);

        if (!$this->actorPuedeAdministrarRol(Auth::user(), $role)) {
            abort(403, 'No autorizado para administrar este rol.');
        }

        return $role;
    }

    private function actorPuedeAdministrarRol(?User $actor, Role $role): bool
    {
        if (!$actor) {
            return false;
        }

        if ($this->actorEsSuperadmin($actor)) {
            return true;
        }

        if ($role->name === 'Superadmin') {
            return false;
        }

        $unidadRolId = $role->unidadIdEfectiva();

        return !is_null($unidadRolId)
            && !is_null($actor->unidad_id)
            && (int) $unidadRolId === (int) $actor->unidad_id;
    }

    private function unidadesDisponiblesParaActor(User $actor)
    {
        return Unidad::query()
            ->where('activa', 1)
            ->when(!$this->actorEsSuperadmin($actor), function ($q) use ($actor) {
                $q->where('id', $actor->unidad_id);
            })
            ->orderBy('nombre')
            ->get();
    }

    private function validarNombreRolPermitido(User $actor, string $name): void
    {
        if (!$this->actorEsSuperadmin($actor) && trim($name) === 'Superadmin') {
            abort(403, 'No autorizado.');
        }
    }

    private function unidadIdParaGuardarRol(Request $request, User $actor, string $name): ?int
    {
        $unidadReservada = Role::unidadIdExclusivaPorNombre($name);

        if (!is_null($unidadReservada)) {
            if (!$this->actorEsSuperadmin($actor) && (int) ($actor->unidad_id ?? 0) !== (int) $unidadReservada) {
                throw ValidationException::withMessages([
                    'name' => 'Ese nombre de rol está reservado para otra unidad.',
                ]);
            }

            return (int) $unidadReservada;
        }

        if ($this->actorEsSuperadmin($actor)) {
            return $request->filled('unidad_id') ? (int) $request->input('unidad_id') : null;
        }

        if (empty($actor->unidad_id)) {
            throw ValidationException::withMessages([
                'unidad_id' => 'Tu usuario no tiene una unidad asignada para crear roles.',
            ]);
        }

        return (int) $actor->unidad_id;
    }

    public function index()
    {
        $actor = Auth::user();
        $roles = $this->rolesVisiblesParaActor($actor);
        $rolesAdministrables = $roles
            ->filter(fn (Role $role) => $this->actorPuedeAdministrarRol($actor, $role))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return view('admin.settings.roles.index', compact('roles', 'rolesAdministrables'));
    }

    public function create()
    {
        $actor = Auth::user();
        $unidades = $this->unidadesDisponiblesParaActor($actor);
        $puedeCrearRolGlobal = $this->actorEsSuperadmin($actor);
        $unidadIdDefault = $puedeCrearRolGlobal ? null : $actor->unidad_id;

        return view('admin.settings.roles.create', compact(
            'unidades',
            'puedeCrearRolGlobal',
            'unidadIdDefault'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'unidad_id' => 'nullable|exists:unidades,id',
        ]);

        $actor = Auth::user();
        $this->validarNombreRolPermitido($actor, $request->name);

        $unidadId = $this->unidadIdParaGuardarRol($request, $actor, $request->name);

        Role::create([
            'name' => $request->name,
            'guard_name' => 'web',
            'unidad_id' => $unidadId,
        ]);

        return redirect()->route('roles.index')->with('success', 'Rol creado exitosamente.');
    }

    public function show($id)
    {
        $role = $this->buscarRolVisibleOFail($id);

        return view('admin.settings.roles.show', compact('role'));
    }

    public function edit($id)
    {
        $actor = Auth::user();
        $role = $this->buscarRolAdministrableOFail($id);

        $unidades = $this->unidadesDisponiblesParaActor($actor);
        $puedeCrearRolGlobal = $this->actorEsSuperadmin($actor);
        $unidadIdDefault = $puedeCrearRolGlobal ? null : $actor->unidad_id;

        return view('admin.settings.roles.edit', compact(
            'role',
            'unidades',
            'puedeCrearRolGlobal',
            'unidadIdDefault'
        ));
    }

    public function update(Request $request, $id)
    {
        $actor = Auth::user();
        $role = $this->buscarRolAdministrableOFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'unidad_id' => 'nullable|exists:unidades,id',
        ]);

        $this->validarNombreRolPermitido($actor, $request->name);
        $unidadId = $this->unidadIdParaGuardarRol($request, $actor, $request->name);

        $role->update([
            'name' => $request->name,
            'unidad_id' => $unidadId,
        ]);

        return redirect()->route('roles.index')->with('success', 'Rol actualizado exitosamente.');
    }

    public function destroy($id)
    {
        $role = $this->buscarRolAdministrableOFail($id);

        if ($role->users()->count() > 0) {
            return redirect()->route('roles.index')
                ->with('error', 'El rol no puede ser eliminado porque tiene usuarios asociados.');
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Rol eliminado exitosamente.');
    }

    public function permissions($id)
    {
        $role = $this->buscarRolAdministrableOFail($id);

        $permissions = Permission::query()
            ->orderBy('name')
            ->get();

        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('admin.settings.roles.permissions', compact('role', 'permissions', 'rolePermissions'));
    }

    public function assignPermissions(Request $request, $id)
    {
        $role = $this->buscarRolAdministrableOFail($id);

        $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->syncPermissions($request->permissions ?? []);

        return redirect()->route('roles.permissions', $id)
            ->with('success', 'Permisos asignados correctamente.');
    }
}
