<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\HechoAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Credenciales incorrectas'], 401);
        }

        $user = Auth::user();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = $this->primaryRoleForUser($user);
        $permissions = $this->permissionsForUser($user);

        $isSubdirector = $this->userHasCompatibleRole($user, 'Subdirector');
        $isJefeGrupo = $this->userHasCompatibleRole($user, 'Jefe de Grupo');

        return response()->json([
            'token' => $user->createToken('mobile')->plainTextToken,
            'role' => $role,
            'permissions' => $permissions,
            'flags' => [
                'is_subdirector' => $isSubdirector,
                'is_jefe_grupo' => $isJefeGrupo,
                'can_receive_disconnected_alerts' => $isJefeGrupo && !$isSubdirector,
            ],
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'area' => $user->area,
                'unidad_id' => $user->unidad_id,
                'delegacion_id' => $user->delegacion_id,
                'destacamento_id' => $user->destacamento_id,
                'compartir_ubicacion' => (int) ($user->compartir_ubicacion ?? 0),
            ],
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = $this->primaryRoleForUser($user);
        $permissions = $this->permissionsForUser($user);

        $isSubdirector = $this->userHasCompatibleRole($user, 'Subdirector');
        $isJefeGrupo = $this->userHasCompatibleRole($user, 'Jefe de Grupo');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'area' => $user->area,
                'unidad_id' => $user->unidad_id,
                'delegacion_id' => $user->delegacion_id,
                'destacamento_id' => $user->destacamento_id,
                'compartir_ubicacion' => (int) ($user->compartir_ubicacion ?? 0),
            ],
            'role' => $role,
            'permissions' => $permissions,
            'flags' => [
                'is_subdirector' => $isSubdirector,
                'is_jefe_grupo' => $isJefeGrupo,
                'can_receive_disconnected_alerts' => $isJefeGrupo && !$isSubdirector,
            ],
        ]);
    }

    public function permissions(Request $request)
    {
        $user = $request->user();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json($this->permissionsForUser($user));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada']);
    }

    private function permissionsForUser($user)
    {
        $user->loadMissing(['roles.permissions', 'permissions']);

        $permissions = $user->roles
            ->filter(fn ($role) => $user->puedeVerRol($role))
            ->flatMap(fn ($role) => $role->permissions->pluck('name'))
            ->merge($user->permissions->pluck('name'))
            ->unique(function ($permission) {
                return mb_strtolower(trim((string) $permission), 'UTF-8');
            })
            ->values();

        return HechoAccess::filterPermissionsForUser(
            $permissions,
            $user
        );
    }

    private function primaryRoleForUser($user): ?string
    {
        $user->loadMissing('roles');

        $role = $user->roles->first(fn ($role) => $user->puedeVerRol($role));

        return $role ? $role->name : null;
    }

    private function userHasCompatibleRole($user, string $roleName): bool
    {
        $user->loadMissing('roles');

        return $user->roles->contains(function ($role) use ($user, $roleName) {
            return $role->name === $roleName && $user->puedeVerRol($role);
        });
    }
}
