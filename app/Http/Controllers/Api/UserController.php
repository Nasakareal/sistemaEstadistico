<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delegacion;
use App\Models\Destacamento;
use App\Models\Patrulla;
use App\Models\Role;
use App\Models\Turno;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $actor = $request->user();
        $q = trim((string) $request->query('q', ''));
        $unidadId = (int) $request->query('unidad_id', 0);
        $perPage = max(1, min(100, (int) $request->query('per_page', 50)));

        $users = $this->queryUsuariosVisiblesParaActor($actor)
            ->with(['roles', 'unidad', 'turno', 'patrulla', 'delegacion', 'destacamento'])
            ->when($this->actorTieneVisibilidadGlobal($actor) && $unidadId > 0, function ($query) use ($unidadId) {
                $query->where('unidad_id', $unidadId);
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('nombres', 'like', "%{$q}%")
                        ->orWhere('apellido_paterno', 'like', "%{$q}%")
                        ->orWhere('apellido_materno', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('telefono', 'like', "%{$q}%")
                        ->orWhere('telefono_whatsapp_operativo', 'like', "%{$q}%")
                        ->orWhere('area', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json([
            'data' => $users->getCollection()->map(fn (User $user) => $this->serializeUser($user))->values(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function meta(Request $request)
    {
        $actor = $request->user();

        return response()->json([
            'roles' => $this->rolesDisponiblesParaActor($actor)
                ->map(fn (Role $role) => $this->serializeRole($role))
                ->values(),
            'unidades' => $this->unidadesDisponiblesParaActor($actor)
                ->map(fn (Unidad $unidad) => $this->serializeSimple($unidad, 'nombre', ['slug' => $unidad->slug]))
                ->values(),
            'turnos' => Turno::query()
                ->orderBy('nombre')
                ->get()
                ->map(fn (Turno $turno) => $this->serializeSimple($turno, 'nombre', ['slug' => $turno->slug]))
                ->values(),
            'patrullas' => $this->patrullasDisponiblesParaActor($actor)
                ->map(fn (Patrulla $patrulla) => $this->serializeSimple($patrulla, 'numero_economico', [
                    'unidad_id' => $patrulla->unidad_id,
                    'turno_id' => $patrulla->turno_id,
                    'activa' => (bool) $patrulla->activa,
                ]))
                ->values(),
            'delegaciones' => Delegacion::query()
                ->where('activa', 1)
                ->orderBy('nombre')
                ->get()
                ->map(fn (Delegacion $delegacion) => $this->serializeSimple($delegacion, 'nombre', [
                    'clave' => $delegacion->clave,
                    'municipio' => $delegacion->municipio,
                ]))
                ->values(),
            'destacamentos' => $this->destacamentosDisponiblesParaActor($actor)
                ->map(fn (Destacamento $destacamento) => $this->serializeSimple($destacamento, 'nombre', [
                    'clave' => $destacamento->clave,
                    'municipio' => $destacamento->municipio,
                    'unidad_id' => $destacamento->unidad_id,
                ]))
                ->values(),
        ]);
    }

    public function store(Request $request)
    {
        $actor = $request->user();
        $validated = $this->validatePayload($request);
        $validated = $this->normalizarNombreUsuario($validated);
        $role = $this->resolveAssignableRole($actor, (int) $validated['role_id']);
        $this->normalizeAndValidateAssignments($actor, $validated, $role);

        $user = DB::transaction(function () use ($validated, $role) {
            $user = User::create([
                'name' => $validated['name'],
                'apellido_paterno' => $validated['apellido_paterno'],
                'apellido_materno' => $validated['apellido_materno'],
                'nombres' => $validated['nombres'],
                'email' => $validated['email'],
                'telefono' => $validated['telefono'] ?? null,
                'telefono_whatsapp_operativo' => $validated['telefono_whatsapp_operativo'] ?? null,
                'password' => Hash::make($validated['password']),
                'estado' => $validated['estado'] ?? 'Activo',
                'area' => $validated['area'] ?? null,
                'unidad_id' => $validated['unidad_id'] ?? null,
                'turno_id' => $validated['turno_id'] ?? null,
                'patrulla_id' => $validated['patrulla_id'] ?? null,
                'delegacion_id' => $validated['delegacion_id'] ?? null,
                'destacamento_id' => $validated['destacamento_id'] ?? null,
                'compartir_ubicacion' => (bool) ($validated['compartir_ubicacion'] ?? true),
            ]);

            $user->assignRole($role->name);

            return $user;
        });

        $user->load(['roles', 'unidad', 'turno', 'patrulla', 'delegacion', 'destacamento']);

        return response()->json([
            'message' => 'Usuario creado correctamente.',
            'data' => $this->serializeUser($user),
        ], 201);
    }

    public function show(Request $request, User $user)
    {
        $actor = $request->user();
        abort_unless($this->queryUsuariosVisiblesParaActor($actor)->whereKey($user->id)->exists(), 404);

        $user->load(['roles', 'unidad', 'turno', 'patrulla', 'delegacion', 'destacamento']);

        return response()->json([
            'data' => $this->serializeUser($user),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $actor = $request->user();
        abort_unless($this->queryUsuariosVisiblesParaActor($actor)->whereKey($user->id)->exists(), 404);

        $validated = $this->validatePayload($request, $user);
        $validated = $this->normalizarNombreUsuario($validated);
        $role = $this->resolveAssignableRole($actor, (int) $validated['role_id']);

        if ($user->hasRole('Superadmin') && $role->name !== 'Superadmin' && User::role('Superadmin')->count() <= 1) {
            throw ValidationException::withMessages([
                'role_id' => ['No puedes dejar el sistema sin Superadmin.'],
            ]);
        }

        $this->normalizeAndValidateAssignments($actor, $validated, $role);

        DB::transaction(function () use ($user, $validated, $role, $request) {
            $updates = [
                'name' => $validated['name'],
                'apellido_paterno' => $validated['apellido_paterno'],
                'apellido_materno' => $validated['apellido_materno'],
                'nombres' => $validated['nombres'],
                'email' => $validated['email'],
                'telefono' => $validated['telefono'] ?? null,
                'telefono_whatsapp_operativo' => $validated['telefono_whatsapp_operativo'] ?? null,
                'estado' => $validated['estado'] ?? $user->estado ?? 'Activo',
                'area' => $validated['area'] ?? null,
                'unidad_id' => $validated['unidad_id'] ?? null,
                'turno_id' => $validated['turno_id'] ?? null,
                'patrulla_id' => $validated['patrulla_id'] ?? null,
                'delegacion_id' => $validated['delegacion_id'] ?? null,
                'destacamento_id' => $validated['destacamento_id'] ?? null,
                'compartir_ubicacion' => (bool) ($validated['compartir_ubicacion'] ?? false),
            ];

            if (!empty($validated['password'])) {
                $updates['password'] = Hash::make($validated['password']);
            }

            $user->update($updates);
            $user->syncRoles([$role->name]);
        });

        $user->load(['roles', 'unidad', 'turno', 'patrulla', 'delegacion', 'destacamento']);

        return response()->json([
            'message' => 'Usuario actualizado correctamente.',
            'data' => $this->serializeUser($user),
        ]);
    }

    private function validatePayload(Request $request, ?User $user = null): array
    {
        $emailRule = Rule::unique('users', 'email');
        if ($user) {
            $emailRule->ignore($user->id);
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'apellido_paterno' => ['nullable', 'string', 'max:255'],
            'apellido_materno' => ['nullable', 'string', 'max:255'],
            'nombres' => ['required_without:name', 'nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                $emailRule,
            ],
            'telefono' => ['nullable', 'string', 'max:30'],
            'telefono_whatsapp_operativo' => ['nullable', 'string', 'max:30'],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:6', 'confirmed'],
            'estado' => ['nullable', 'string', 'max:11'],
            'area' => ['nullable', 'string', 'max:120'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'unidad_id' => ['nullable', 'integer', 'exists:unidades,id'],
            'turno_id' => ['nullable', 'integer', 'exists:turnos,id'],
            'patrulla_id' => ['nullable', 'integer', 'exists:patrullas,id'],
            'delegacion_id' => ['nullable', 'integer', 'exists:delegaciones,id'],
            'destacamento_id' => ['nullable', 'integer', 'exists:destacamentos,id'],
            'compartir_ubicacion' => ['nullable', 'boolean'],
        ]);

        return $this->normalizarTelefonosWhatsAppParaActor($validated, $request->user(), $user);
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

    private function resolveAssignableRole(User $actor, int $roleId): Role
    {
        $role = Role::query()->with('unidad')->find($roleId);

        if (!$role) {
            throw ValidationException::withMessages([
                'role_id' => ['El rol seleccionado no existe.'],
            ]);
        }

        if ($actor->hasRole('Administrador') && !$actor->hasRole('Superadmin') && $role->name === 'Administrador') {
            throw ValidationException::withMessages([
                'role_id' => ['No puedes asignar ese rol.'],
            ]);
        }

        if (!$actor->puedeVerRol($role)) {
            throw ValidationException::withMessages([
                'role_id' => ['No puedes asignar ese rol.'],
            ]);
        }

        return $role;
    }

    private function normalizeAndValidateAssignments(User $actor, array &$validated, Role $role): void
    {
        $unidadRolId = $role->unidadIdEfectiva();

        if (!$this->actorEsSuperadmin($actor)) {
            $validated['unidad_id'] = $actor->unidad_id;
        } elseif (!is_null($unidadRolId)) {
            $validated['unidad_id'] = (int) $unidadRolId;
        }

        $unidadId = $validated['unidad_id'] ?? null;
        if (!is_null($unidadRolId) && (int) $unidadId !== (int) $unidadRolId) {
            throw ValidationException::withMessages([
                'unidad_id' => ['La unidad seleccionada no es compatible con el rol elegido.'],
            ]);
        }

        if (!$this->patrullaPerteneceAUnidad($validated['patrulla_id'] ?? null, $unidadId)) {
            throw ValidationException::withMessages([
                'patrulla_id' => ['La patrulla seleccionada no pertenece a la unidad permitida.'],
            ]);
        }

        if (!$this->isUnidadDelegaciones($unidadId)) {
            $validated['delegacion_id'] = null;
        } elseif (!$this->delegacionEstaActiva($validated['delegacion_id'] ?? null)) {
            throw ValidationException::withMessages([
                'delegacion_id' => ['La delegación seleccionada no está activa.'],
            ]);
        }

        if (!$this->isUnidadCarreteras($unidadId)) {
            $validated['destacamento_id'] = null;
        } elseif (!empty($validated['destacamento_id'])) {
            $ok = Destacamento::query()
                ->where('id', (int) $validated['destacamento_id'])
                ->where('unidad_id', (int) $this->unidadCarreterasId())
                ->exists();

            if (!$ok) {
                throw ValidationException::withMessages([
                    'destacamento_id' => ['El destacamento seleccionado no pertenece a CARRETERAS.'],
                ]);
            }
        }
    }

    private function serializeUser(User $user): array
    {
        $primaryRole = $user->roles->first();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'nombre_completo' => $user->nombre_completo,
            'apellido_paterno' => $user->apellido_paterno,
            'apellido_materno' => $user->apellido_materno,
            'nombres' => $user->nombres,
            'email' => $user->email,
            'telefono' => $user->telefono,
            'telefono_whatsapp_operativo' => $user->telefono_whatsapp_operativo,
            'estado' => $user->estado,
            'area' => $user->area,
            'unidad_id' => $user->unidad_id,
            'turno_id' => $user->turno_id,
            'patrulla_id' => $user->patrulla_id,
            'delegacion_id' => $user->delegacion_id,
            'destacamento_id' => $user->destacamento_id,
            'compartir_ubicacion' => (bool) ($user->compartir_ubicacion ?? false),
            'role_id' => $primaryRole ? $primaryRole->id : null,
            'role' => $primaryRole ? $this->serializeRole($primaryRole) : null,
            'roles' => $user->roles->map(fn (Role $role) => $this->serializeRole($role))->values(),
            'unidad' => $this->serializeNullableSimple($user->unidad, 'nombre'),
            'turno' => $this->serializeNullableSimple($user->turno, 'nombre'),
            'patrulla' => $this->serializeNullableSimple($user->patrulla, 'numero_economico'),
            'delegacion' => $this->serializeNullableSimple($user->delegacion, 'nombre'),
            'destacamento' => $this->serializeNullableSimple($user->destacamento, 'nombre'),
            'unidades' => $user->unidad
                ? [$this->serializeSimple($user->unidad, 'nombre', ['slug' => $user->unidad->slug])]
                : [],
            'created_at' => optional($user->created_at)->toIso8601String(),
            'updated_at' => optional($user->updated_at)->toIso8601String(),
        ];
    }

    private function serializeRole(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'unidad_id' => $role->unidad_id,
            'unidad_efectiva_id' => $role->unidadIdEfectiva(),
            'unidad_efectiva_nombre' => $role->unidadEfectivaNombre(),
        ];
    }

    private function serializeNullableSimple($model, string $labelKey): ?array
    {
        if (!$model) {
            return null;
        }

        return $this->serializeSimple($model, $labelKey);
    }

    private function serializeSimple($model, string $labelKey, array $extra = []): array
    {
        return array_merge([
            'id' => $model->id,
            'nombre' => $model->{$labelKey},
        ], $extra);
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

    private function unidadDelegacionesId(): ?int
    {
        $id = Unidad::query()->where('slug', 'delegaciones')->value('id');
        return $id ? (int) $id : null;
    }

    private function unidadCarreterasId(): ?int
    {
        $id = Unidad::query()->where('slug', 'carreteras')->value('id');
        return $id ? (int) $id : null;
    }

    private function isUnidadDelegaciones(?int $unidadId): bool
    {
        $delegacionesId = $this->unidadDelegacionesId();
        return $delegacionesId !== null && (int) $unidadId === (int) $delegacionesId;
    }

    private function isUnidadCarreteras(?int $unidadId): bool
    {
        $carreterasId = $this->unidadCarreterasId();
        return $carreterasId !== null && (int) $unidadId === (int) $carreterasId;
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

    private function queryUsuariosVisiblesParaActor(User $actor)
    {
        return User::query()
            ->when(!$this->actorEsSuperadmin($actor), function ($query) use ($actor) {
                $query->whereDoesntHave('roles', function ($roles) {
                    $roles->where('name', 'Superadmin');
                });

                if ($this->actorTieneVisibilidadGlobal($actor)) {
                    return;
                }

                $query->where('unidad_id', $actor->unidad_id);
            });
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

    private function unidadesDisponiblesParaActor(User $actor)
    {
        return Unidad::query()
            ->when(!$this->actorTieneVisibilidadGlobal($actor), function ($query) use ($actor) {
                $query->where('id', $actor->unidad_id);
            })
            ->orderBy('nombre')
            ->get();
    }

    private function patrullasDisponiblesParaActor(User $actor)
    {
        return Patrulla::query()
            ->when(!$this->actorEsSuperadmin($actor), function ($query) use ($actor) {
                $query->where('unidad_id', $actor->unidad_id);
            })
            ->orderBy('numero_economico')
            ->get();
    }

    private function destacamentosDisponiblesParaActor(User $actor)
    {
        $carreterasId = $this->unidadCarreterasId();

        return Destacamento::query()
            ->where('activo', 1)
            ->when($carreterasId === null, function ($query) {
                $query->whereRaw('1=0');
            })
            ->when($carreterasId !== null, function ($query) use ($actor, $carreterasId) {
                $query->where('unidad_id', $carreterasId);

                if (!$this->actorEsSuperadmin($actor) && (int) ($actor->unidad_id ?? 0) !== (int) $carreterasId) {
                    $query->whereRaw('1=0');
                }
            })
            ->orderBy('nombre')
            ->get();
    }

    private function normalizarTelefonosWhatsAppParaActor(
        array $validated,
        User $actor,
        ?User $user = null
    ): array {
        if (!$this->actorEsSuperadmin($actor)) {
            $validated['telefono'] = $user ? $user->telefono : null;
            $validated['telefono_whatsapp_operativo'] = $user
                ? $user->telefono_whatsapp_operativo
                : null;

            return $validated;
        }

        $campos = [
            'telefono' => 'El WhatsApp autorizado ya está registrado en otro usuario.',
            'telefono_whatsapp_operativo' => 'El WhatsApp operativo ya está registrado en otro usuario.',
        ];

        foreach ($campos as $campo => $mensaje) {
            $validated[$campo] = $this->normalizarTelefonoMx($validated[$campo] ?? null);

            if (is_null($validated[$campo])) {
                continue;
            }

            $exists = User::query()
                ->where($campo, $validated[$campo])
                ->when($user, fn ($query) => $query->where('id', '!=', $user->id))
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    $campo => [$mensaje],
                ]);
            }
        }

        return $validated;
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

        if (strlen($telefono) === 12 && substr($telefono, 0, 2) === '52') {
            return '521' . substr($telefono, 2);
        }

        if (strlen($telefono) === 13 && substr($telefono, 0, 3) === '521') {
            return $telefono;
        }

        return $telefono;
    }
}
