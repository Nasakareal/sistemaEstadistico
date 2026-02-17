<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        $role = $user->roles()->pluck('name')->first();
        $permissions = $user->getAllPermissions()->pluck('name')->values();

        $isSubdirector = $user->hasRole('Subdirector');
        $isJefeGrupo = $user->hasRole('Jefe de Grupo');

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
            ],
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = $user->roles()->pluck('name')->first();
        $permissions = $user->getAllPermissions()->pluck('name')->values();

        $isSubdirector = $user->hasRole('Subdirector');
        $isJefeGrupo = $user->hasRole('Jefe de Grupo');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
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

        return response()->json($user->getAllPermissions()->pluck('name')->values());
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada']);
    }
}
