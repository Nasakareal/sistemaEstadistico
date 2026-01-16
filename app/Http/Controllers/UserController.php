<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index()
    {
        $actor = Auth::user();

        // Si no es Superadmin, NO verá usuarios Superadmin
        $users = User::query()
            ->visibleFor($actor)
            ->get();

        return view('admin.settings.users.index', compact('users'));
    }

    public function create()
    {
        $actor = Auth::user();

        // Si no es Superadmin, NO verá el rol Superadmin
        $roles = Role::query()
            ->when(!$actor->hasRole('Superadmin'), function ($q) {
                $q->where('name', '!=', 'Superadmin');
            })
            ->get();

        return view('admin.settings.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $actor = Auth::user();

        // Validar los datos del formulario
        $validatedData = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
            'area'     => 'nullable|string|max:30',
            'role'     => 'required|exists:roles,name',
        ]);

        // Bloqueo: si no es Superadmin, no puede asignar Superadmin
        if (!$actor->hasRole('Superadmin') && $validatedData['role'] === 'Superadmin') {
            abort(403, 'No autorizado.');
        }

        try {
            $user = User::create([
                'name'     => $validatedData['name'],
                'email'    => $validatedData['email'],
                'password' => bcrypt($validatedData['password']),
                'estado'   => 'Activo',
                'area'     => $validatedData['area'] ?? null,
            ]);

            $user->assignRole($validatedData['role']);

            Log::info("Usuario creado exitosamente: {$user->name}");

            return redirect()->route('users.index')->with('success', 'Usuario creado correctamente.');
        } catch (\Exception $e) {
            Log::error("Error al crear el usuario: " . $e->getMessage());
            return redirect()->back()->withErrors('Hubo un error al crear el usuario. Inténtelo nuevamente.');
        }
    }

    public function show($id)
    {
        $actor = Auth::user();

        // Evita que NO-superadmin vea usuarios superadmin
        $user = User::query()->visibleFor($actor)->findOrFail($id);

        return view('admin.settings.users.show', compact('user'));
    }

    public function edit($id)
    {
        $actor = Auth::user();

        // Evita que NO-superadmin edite usuarios superadmin
        $user = User::query()->visibleFor($actor)->findOrFail($id);

        // Evita que NO-superadmin vea el rol superadmin en el combo
        $roles = Role::query()
            ->when(!$actor->hasRole('Superadmin'), function ($q) {
                $q->where('name', '!=', 'Superadmin');
            })
            ->get();

        return view('admin.settings.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, $id)
    {
        $actor = Auth::user();

        // Evita que NO-superadmin actualice usuarios superadmin
        $user = User::query()->visibleFor($actor)->findOrFail($id);

        $validatedData = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $id,
            'area'     => 'nullable|string|max:30',
            'role'     => 'required|exists:roles,name',
            'password' => 'nullable|min:6|confirmed',
        ]);

        // Bloqueo: si no es Superadmin, no puede asignar Superadmin
        if (!$actor->hasRole('Superadmin') && $validatedData['role'] === 'Superadmin') {
            abort(403, 'No autorizado.');
        }

        // Bloqueo: no permitir dejar el sistema sin Superadmin
        // Si el usuario actual es Superadmin y se intenta quitar ese rol, valida que no sea el último
        if ($user->hasRole('Superadmin') && $validatedData['role'] !== 'Superadmin') {
            $superadmins = User::role('Superadmin')->count();
            if ($superadmins <= 1) {
                throw ValidationException::withMessages([
                    'role' => 'No puedes dejar el sistema sin Superadmin.',
                ]);
            }
        }

        try {
            $user->update([
                'name'  => $validatedData['name'],
                'email' => $validatedData['email'],
                'area'  => $validatedData['area'] ?? null,
            ]);

            if (!empty($validatedData['password'])) {
                $user->password = Hash::make($validatedData['password']);
                $user->save();
            }

            // Sync de rol (solo 1 rol)
            $user->syncRoles([$validatedData['role']]);

            Log::info("Usuario actualizado exitosamente: {$user->name}");

            return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente.');
        } catch (ValidationException $e) {
            // Para devolver error en el formulario sin romper el flujo
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            Log::error("Error al actualizar el usuario: " . $e->getMessage());
            return redirect()->back()->withErrors('Hubo un error al actualizar el usuario. Inténtelo nuevamente.');
        }
    }

    public function destroy($id)
    {
        $actor = Auth::user();

        try {
            // Evita que NO-superadmin elimine usuarios superadmin (porque ni los ve con visibleFor)
            $user = User::query()->visibleFor($actor)->findOrFail($id);

            // Bloqueo: no permitir eliminar al último Superadmin
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
            'password'         => 'required|min:6|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return redirect()->back()->withErrors(['current_password' => 'La contraseña actual no coincide.']);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()->route('profile')->with('success', '¡Contraseña actualizada correctamente!');
    }
}
