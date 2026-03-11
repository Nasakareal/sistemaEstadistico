<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    private function actorEsSuperadmin(): bool
    {
        $actor = Auth::user();
        return $actor && $actor->hasRole('Superadmin');
    }

    private function queryRolesVisiblesParaActor()
    {
        return Role::query()
            ->when(!$this->actorEsSuperadmin(), function ($q) {
                $q->where('name', '!=', 'Superadmin');
            });
    }

    private function buscarRolVisibleOFail($id): Role
    {
        return $this->queryRolesVisiblesParaActor()->findOrFail($id);
    }

    private function validarNombreRolPermitido(string $name): void
    {
        if (!$this->actorEsSuperadmin() && trim($name) === 'Superadmin') {
            abort(403, 'No autorizado.');
        }
    }

    public function index()
    {
        $roles = $this->queryRolesVisiblesParaActor()
            ->orderBy('name')
            ->get();

        return view('admin.settings.roles.index', compact('roles'));
    }

    public function create()
    {
        return view('admin.settings.roles.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
        ]);

        $this->validarNombreRolPermitido($request->name);

        Role::create([
            'name' => $request->name,
            'guard_name' => 'web',
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
        $role = $this->buscarRolVisibleOFail($id);

        return view('admin.settings.roles.edit', compact('role'));
    }

    public function update(Request $request, $id)
    {
        $role = $this->buscarRolVisibleOFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
        ]);

        $this->validarNombreRolPermitido($request->name);

        $role->update([
            'name' => $request->name,
        ]);

        return redirect()->route('roles.index')->with('success', 'Rol actualizado exitosamente.');
    }

    public function destroy($id)
    {
        $role = $this->buscarRolVisibleOFail($id);

        if ($role->users()->count() > 0) {
            return redirect()->route('roles.index')
                ->with('error', 'El rol no puede ser eliminado porque tiene usuarios asociados.');
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Rol eliminado exitosamente.');
    }

    public function permissions($id)
    {
        $role = $this->buscarRolVisibleOFail($id);

        $permissions = Permission::query()
            ->orderBy('name')
            ->get();

        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('admin.settings.roles.permissions', compact('role', 'permissions', 'rolePermissions'));
    }

    public function assignPermissions(Request $request, $id)
    {
        $role = $this->buscarRolVisibleOFail($id);

        $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->syncPermissions($request->permissions ?? []);

        return redirect()->route('roles.permissions', $id)
            ->with('success', 'Permisos asignados correctamente.');
    }
}
