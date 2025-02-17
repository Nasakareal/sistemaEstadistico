<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return view('admin.settings.users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        return view('admin.settings.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        // Validar los datos del formulario
        $validatedData = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
            'area'     => 'nullable|string|max:30',
            'role'     => 'required|exists:roles,name',
        ]);

        try {
            // Crear el usuario
            $user = User::create([
                'name'     => $validatedData['name'],
                'email'    => $validatedData['email'],
                'password' => bcrypt($validatedData['password']), // Encripta la contraseña
                'estado'   => 'Activo', // Estado por defecto
                'area'     => $validatedData['area'] ?? null,
            ]);

            // Asignar rol al usuario
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
        $user = User::findOrFail($id);
        return view('admin.settings.users.show', compact('user'));
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $roles = Role::all();

        return view('admin.settings.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, $id)
    {
        // Validar los datos, incluyendo la contraseña como campo opcional
        $validatedData = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $id,
            'area'     => 'nullable|string|max:30',
            'role'     => 'required|exists:roles,name',
            'password' => 'nullable|min:6|confirmed', // Si se ingresa, debe tener confirmación
        ]);

        try {
            // Buscar el usuario
            $user = User::findOrFail($id);

            // Actualizar los datos comunes
            $user->update([
                'name'  => $validatedData['name'],
                'email' => $validatedData['email'],
                'area'  => $validatedData['area'] ?? null,
            ]);

            // Actualizar la contraseña solo si se ingresó un valor
            if (!empty($validatedData['password'])) {
                $user->password = Hash::make($validatedData['password']);
                $user->save();
            }

            // Actualizar los roles del usuario
            $user->syncRoles([$validatedData['role']]);

            Log::info("Usuario actualizado exitosamente: {$user->name}");

            return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente.');
        } catch (\Exception $e) {
            Log::error("Error al actualizar el usuario: " . $e->getMessage());
            return redirect()->back()->withErrors('Hubo un error al actualizar el usuario. Inténtelo nuevamente.');
        }
    }

    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();

            Log::info("Usuario eliminado exitosamente: {$user->name}");

            return redirect()->route('users.index')->with('success', 'Usuario eliminado correctamente.');
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
