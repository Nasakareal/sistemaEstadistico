<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;

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
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
            'area' => 'nullable|string|max:30',
            'role' => 'required|exists:roles,name',
        ]);

        try {
            // Crear el usuario
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => bcrypt($validatedData['password']), // Encripta la contraseña
                'estado' => 'Activo', // Estado por defecto
                'area' => $validatedData['area'] ?? null,
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
        // Validar los datos
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'area' => 'nullable|string|max:30',
            'role' => 'required|exists:roles,name',
        ]);

        try {
            // Buscar y actualizar el usuario
            $user = User::findOrFail($id);
            $user->update([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'area' => $validatedData['area'] ?? null,
            ]);

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
}
