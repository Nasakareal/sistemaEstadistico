<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\HechoAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
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
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json($this->buildAuthResponse($user, $token));
    }

    public function me(Request $request)
    {
        return response()->json($this->buildAuthResponse($request->user()));
    }

    public function profile(Request $request)
    {
        return response()->json($this->buildAuthResponse($request->user()));
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = $request->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['La contraseña actual no coincide.'],
            ]);
        }

        if (Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['La nueva contraseña debe ser diferente a la actual.'],
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        return response()->json([
            'message' => 'Contraseña actualizada correctamente.',
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

    private function buildAuthResponse($user, ?string $token = null): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user->loadMissing([
            'unidad',
            'unidades',
            'delegacion',
            'destacamento',
            'turno',
            'patrulla',
            'roles.permissions',
            'permissions',
        ]);

        $primaryRole = $this->primaryRoleForUser($user);
        $visibleRoles = $user->roles
            ->filter(fn ($role) => $user->puedeVerRol($role))
            ->values();
        $permissions = $this->permissionsForUser($user)->values();

        $isSubdirector = $this->userHasCompatibleRole($user, 'Subdirector');
        $isJefeGrupo = $this->userHasCompatibleRole($user, 'Jefe de Grupo');

        $response = [
            'role' => $primaryRole ? [
                'id' => $primaryRole->id,
                'name' => $primaryRole->name,
            ] : null,
            'role_id' => $primaryRole ? $primaryRole->id : null,
            'permissions' => $permissions->all(),
            'flags' => [
                'is_subdirector' => $isSubdirector,
                'is_jefe_grupo' => $isJefeGrupo,
                'can_receive_disconnected_alerts' => $isJefeGrupo && !$isSubdirector,
            ],
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'telefono' => $user->telefono,
                'area' => $user->area,
                'estado' => $user->estado,
                'unidad_id' => $user->unidad_id,
                'delegacion_id' => $user->delegacion_id,
                'destacamento_id' => $user->destacamento_id,
                'turno_id' => $user->turno_id,
                'patrulla_id' => $user->patrulla_id,
                'compartir_ubicacion' => (int) ($user->compartir_ubicacion ?? 0),
                'role' => $primaryRole ? [
                    'id' => $primaryRole->id,
                    'name' => $primaryRole->name,
                ] : null,
                'role_id' => $primaryRole ? $primaryRole->id : null,
                'roles' => $visibleRoles->map(fn ($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                ])->values()->all(),
                'permissions' => $permissions->all(),
                'unidad' => $user->unidad ? [
                    'id' => $user->unidad->id,
                    'nombre' => $user->unidad->nombre,
                    'slug' => $user->unidad->slug,
                ] : null,
                'unidades' => $user->unidades->map(fn ($unidad) => [
                    'id' => $unidad->id,
                    'nombre' => $unidad->nombre,
                    'slug' => $unidad->slug,
                ])->values()->all(),
                'delegacion' => $user->delegacion ? [
                    'id' => $user->delegacion->id,
                    'nombre' => $user->delegacion->nombre,
                ] : null,
                'destacamento' => $user->destacamento ? [
                    'id' => $user->destacamento->id,
                    'nombre' => $user->destacamento->nombre,
                ] : null,
                'turno' => $user->turno ? [
                    'id' => $user->turno->id,
                    'nombre' => $user->turno->nombre,
                ] : null,
                'patrulla' => $user->patrulla ? [
                    'id' => $user->patrulla->id,
                    'numero_economico' => $user->patrulla->numero_economico,
                ] : null,
            ],
        ];

        if ($token !== null && trim($token) !== '') {
            $response['token'] = $token;
        }

        return $response;
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

    private function primaryRoleForUser($user)
    {
        $user->loadMissing('roles');

        return $user->roles->first(fn ($role) => $user->puedeVerRol($role));
    }

    private function userHasCompatibleRole($user, string $roleName): bool
    {
        $user->loadMissing('roles');

        return $user->roles->contains(function ($role) use ($user, $roleName) {
            return $role->name === $roleName && $user->puedeVerRol($role);
        });
    }
}
