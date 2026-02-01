<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Unidad;
use App\Models\Turno;
use App\Models\Patrulla;
use App\Models\Delegacion;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private function unidadDelegacionesId(): ?int
    {
        $id = Unidad::query()->where('slug', 'delegaciones')->value('id');
        return $id ? (int) $id : null;
    }

    private function isUnidadDelegaciones(?int $unidadId): bool
    {
        $delegacionesId = $this->unidadDelegacionesId();
        return $delegacionesId !== null && (int)$unidadId === (int)$delegacionesId;
    }

    public function index()
    {
        $actor = Auth::user();

        $users = User::query()
            ->visibleFor($actor)
            ->with(['roles', 'unidad', 'turno', 'patrulla', 'unidades', 'delegacion'])
            ->get();

        return view('admin.settings.users.index', compact('users'));
    }

    public function create()
    {
        $actor = Auth::user();

        $roles = Role::query()
            ->when(!$actor->hasRole('Superadmin'), function ($q) {
                $q->where('name', '!=', 'Superadmin');
            })
            ->get();

        $unidades = Unidad::query()->orderBy('nombre')->get();
        $turnos = Turno::query()->orderBy('nombre')->get();
        $patrullas = Patrulla::query()->orderBy('numero_economico')->get();

        $delegaciones = Delegacion::query()
            ->where('activa', 1)
            ->orderBy('nombre')
            ->get();

        $unidadDelegacionesId = $this->unidadDelegacionesId();

        return view('admin.settings.users.create', compact(
            'roles',
            'unidades',
            'turnos',
            'patrullas',
            'delegaciones',
            'unidadDelegacionesId'
        ));
    }

    public function store(Request $request)
    {
        $actor = Auth::user();

        $unidadDelegacionesId = $this->unidadDelegacionesId();

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'area' => 'nullable|string|max:30',
            'role' => 'required|exists:roles,name',

            'unidad_id' => 'nullable|exists:unidades,id',
            'turno_id' => 'nullable|exists:turnos,id',
            'patrulla_id' => 'nullable|exists:patrullas,id',

            'delegacion_id' => [
                Rule::requiredIf(fn () => $unidadDelegacionesId !== null && (int)($request->input('unidad_id')) === (int)$unidadDelegacionesId),
                'nullable',
                'integer',
                'exists:delegaciones,id',
            ],

            'unidades_ids' => 'nullable|array',
            'unidades_ids.*' => 'integer|exists:unidades,id',
        ]);

        if (!$actor->hasRole('Superadmin') && $validatedData['role'] === 'Superadmin') {
            abort(403, 'No autorizado.');
        }

        if (!$this->isUnidadDelegaciones($validatedData['unidad_id'] ?? null)) {
            $validatedData['delegacion_id'] = null;
        }

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
            ]);

            $user->assignRole($validatedData['role']);

            $unidadesExtra = $validatedData['unidades_ids'] ?? [];
            if (!empty($unidadesExtra)) {
                $user->unidades()->sync($unidadesExtra);
            }

            Log::info("Usuario creado exitosamente: {$user->name}");

            return redirect()->route('users.index')->with('success', 'Usuario creado correctamente.');
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

        $user = User::query()
            ->visibleFor($actor)
            ->with(['roles', 'unidad', 'turno', 'patrulla', 'unidades', 'delegacion'])
            ->findOrFail($id);

        return view('admin.settings.users.show', compact('user'));
    }

    public function edit($id)
    {
        $actor = Auth::user();

        $user = User::query()
            ->visibleFor($actor)
            ->with(['roles', 'unidad', 'turno', 'patrulla', 'unidades', 'delegacion'])
            ->findOrFail($id);

        $roles = Role::query()
            ->when(!$actor->hasRole('Superadmin'), function ($q) {
                $q->where('name', '!=', 'Superadmin');
            })
            ->get();

        $unidades = Unidad::query()->orderBy('nombre')->get();
        $turnos = Turno::query()->orderBy('nombre')->get();
        $patrullas = Patrulla::query()->orderBy('numero_economico')->get();

        $delegaciones = Delegacion::query()
            ->where('activa', 1)
            ->orderBy('nombre')
            ->get();

        $unidadDelegacionesId = $this->unidadDelegacionesId();

        $unidadesExtraSeleccionadas = $user->unidades->pluck('id')->all();

        return view('admin.settings.users.edit', compact(
            'user',
            'roles',
            'unidades',
            'turnos',
            'patrullas',
            'delegaciones',
            'unidadDelegacionesId',
            'unidadesExtraSeleccionadas'
        ));
    }

    public function update(Request $request, $id)
    {
        $actor = Auth::user();

        $user = User::query()
            ->visibleFor($actor)
            ->with('roles')
            ->findOrFail($id);

        $unidadDelegacionesId = $this->unidadDelegacionesId();

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'area' => 'nullable|string|max:30',
            'role' => 'required|exists:roles,name',
            'password' => 'nullable|min:6|confirmed',

            'unidad_id' => 'nullable|exists:unidades,id',
            'turno_id' => 'nullable|exists:turnos,id',
            'patrulla_id' => 'nullable|exists:patrullas,id',

            'delegacion_id' => [
                Rule::requiredIf(fn () => $unidadDelegacionesId !== null && (int)($request->input('unidad_id')) === (int)$unidadDelegacionesId),
                'nullable',
                'integer',
                'exists:delegaciones,id',
            ],

            'unidades_ids' => 'nullable|array',
            'unidades_ids.*' => 'integer|exists:unidades,id',
        ]);

        if (!$actor->hasRole('Superadmin') && $validatedData['role'] === 'Superadmin') {
            abort(403, 'No autorizado.');
        }

        if ($user->hasRole('Superadmin') && $validatedData['role'] !== 'Superadmin') {
            $superadmins = User::role('Superadmin')->count();
            if ($superadmins <= 1) {
                throw ValidationException::withMessages([
                    'role' => 'No puedes dejar el sistema sin Superadmin.',
                ]);
            }
        }

        if (!$this->isUnidadDelegaciones($validatedData['unidad_id'] ?? null)) {
            $validatedData['delegacion_id'] = null;
        }

        try {
            $user->update([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'area' => $validatedData['area'] ?? null,

                'unidad_id' => $validatedData['unidad_id'] ?? null,
                'turno_id' => $validatedData['turno_id'] ?? null,
                'patrulla_id' => $validatedData['patrulla_id'] ?? null,

                'delegacion_id' => $validatedData['delegacion_id'] ?? null,
            ]);

            if (!empty($validatedData['password'])) {
                $user->password = Hash::make($validatedData['password']);
                $user->save();
            }

            $user->syncRoles([$validatedData['role']]);

            $unidadesExtra = $validatedData['unidades_ids'] ?? [];
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
            $user = User::query()->visibleFor($actor)->findOrFail($id);

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
            return redirect()->back()->withErrors(['current_password' => 'La contraseña actual no coincide.']);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()->route('profile')->with('success', '¡Contraseña actualizada correctamente!');
    }
}
