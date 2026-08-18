<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'web';

    protected $fillable = [
        'name',
        'apellido_paterno',
        'apellido_materno',
        'nombres',
        'email',
        'telefono',
        'telefono_whatsapp_operativo',
        'telefono_whatsapp_operativo_secundario',
        'password',
        'estado',
        'foto_perfil',
        'area',
        'unidad_id',
        'turno_id',
        'patrulla_id',
        'delegacion_id',
        'destacamento_id',
        'compartir_ubicacion',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'compartir_ubicacion' => 'boolean',
        'last_seen_at' => 'datetime',
        'disconnected_alert_sent_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::saving(function (self $user) {
            $user->sincronizarNombreCompleto();
        });

        static::saved(function (self $user) {
            if (!$user->wasChanged('unidad_id') || empty($user->unidad_id)) {
                return;
            }

            Personal::query()
                ->where('user_id', $user->id)
                ->where('unidad_id', '!=', $user->unidad_id)
                ->update(['unidad_id' => $user->unidad_id]);
        });
    }

    public static function nombreCompleto(?string $nombres, ?string $apellidoPaterno = null, ?string $apellidoMaterno = null): string
    {
        return collect([$nombres, $apellidoPaterno, $apellidoMaterno])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->implode(' ');
    }

    public function getNombreCompletoAttribute(): string
    {
        return static::nombreCompleto($this->nombres, $this->apellido_paterno, $this->apellido_materno)
            ?: (string) $this->name;
    }

    private function sincronizarNombreCompleto(): void
    {
        $partesEditadas = $this->isDirty('nombres')
            || $this->isDirty('apellido_paterno')
            || $this->isDirty('apellido_materno');

        if ($partesEditadas) {
            $this->attributes['name'] = static::nombreCompleto(
                $this->nombres,
                $this->apellido_paterno,
                $this->apellido_materno
            );
            return;
        }

        if ($this->isDirty('name') || empty($this->attributes['nombres'])) {
            $this->attributes['nombres'] = $this->attributes['name'] ?? null;
            $this->attributes['apellido_paterno'] = null;
            $this->attributes['apellido_materno'] = null;
        }
    }

    public function personal()
    {
        return $this->hasOne(\App\Models\Personal::class, 'user_id');
    }

    public function unidad()
    {
        return $this->belongsTo(\App\Models\Unidad::class, 'unidad_id');
    }

    public function turno()
    {
        return $this->belongsTo(\App\Models\Turno::class, 'turno_id');
    }

    public function patrulla()
    {
        return $this->belongsTo(\App\Models\Patrulla::class, 'patrulla_id');
    }

    public function delegacion()
    {
        return $this->belongsTo(\App\Models\Delegacion::class, 'delegacion_id');
    }

    public function destacamento()
    {
        return $this->belongsTo(\App\Models\Destacamento::class, 'destacamento_id');
    }

    public function licenciasPuntos()
    {
        return $this->belongsToMany(
            \App\Models\LicenciaPuntoCuenta::class,
            'licencia_punto_cuenta_user',
            'user_id',
            'cuenta_id'
        )->withPivot(['verified_at', 'last_accessed_at'])->withTimestamps();
    }

    public function notes()
    {
        return $this->hasMany(UserNote::class);
    }

    public function isSuperadmin(): bool
    {
        return $this->hasRole('Superadmin');
    }

    public function isAdministrador(): bool
    {
        return $this->hasRole('Administrador') && !$this->isSuperadmin();
    }

    public function tieneUnidad(): bool
    {
        return !is_null($this->unidad_id);
    }

    public function perteneceAUnidad(?string $slug): bool
    {
        if ($this->isSuperadmin()) {
            return true;
        }

        if (!$this->unidad || !$slug) {
            return false;
        }

        return $this->unidad->slug === $slug;
    }

    public function perteneceAAlgunaUnidad(array $slugs): bool
    {
        if ($this->isSuperadmin()) {
            return true;
        }

        if (!$this->unidad || empty($slugs)) {
            return false;
        }

        return in_array($this->unidad->slug, $slugs, true);
    }

    public function scopeVisibleFor($query, ?self $actor)
    {
        if ($actor && $actor->isSuperadmin()) {
            return $query;
        }

        return $query->whereDoesntHave('roles', function ($q) {
            $q->where('name', 'Superadmin');
        });
    }

    public function canBeDemotedFromSuperadmin(): bool
    {
        if (!$this->isSuperadmin()) {
            return true;
        }

        return self::role('Superadmin')->count() > 1;
    }

    public function canBeDeleted(): bool
    {
        return $this->canBeDemotedFromSuperadmin();
    }

    public function puedeVerRol(Role $role): bool
    {
        if ($this->isSuperadmin()) {
            return true;
        }

        if ($role->name === 'Superadmin') {
            return false;
        }

        $unidadRolId = $role->unidadIdEfectiva();

        if (is_null($unidadRolId)) {
            return true;
        }

        return !is_null($this->unidad_id) && (int) $unidadRolId === (int) $this->unidad_id;
    }

    public function rolesVisiblesQuery()
    {
        return Role::query()
            ->when(!$this->isSuperadmin(), function ($q) {
                $unidadId = $this->unidad_id ? (int) $this->unidad_id : null;
                $rolesExclusivosDeOtrasUnidades = Role::nombresExclusivosParaOtrasUnidades($unidadId);

                $q->where('name', '!=', 'Superadmin')
                    ->where(function ($sub) {
                        $sub->whereNull('unidad_id')
                            ->orWhere('unidad_id', $this->unidad_id);
                    })
                    ->whereNotIn('name', $rolesExclusivosDeOtrasUnidades);
            });
    }

    public function rolesVisibles(): Collection
    {
        return $this->rolesVisiblesQuery()
            ->orderBy('name')
            ->get()
            ->filter(fn (Role $role) => $this->puedeVerRol($role))
            ->values();
    }

    public function setTelefonoAttribute($value)
    {
        $this->attributes['telefono'] = $this->normalizarTelefonoWhatsApp($value);
    }

    public function setTelefonoWhatsappOperativoAttribute($value)
    {
        $this->attributes['telefono_whatsapp_operativo'] = $this->normalizarTelefonoWhatsApp($value);
    }

    public function setTelefonoWhatsappOperativoSecundarioAttribute($value)
    {
        $this->attributes['telefono_whatsapp_operativo_secundario'] = $this->normalizarTelefonoWhatsApp($value);
    }

    private function normalizarTelefonoWhatsApp($value): ?string
    {
        if (!$value) {
            return null;
        }

        $numero = preg_replace('/\D/', '', $value);

        if ($numero === '') {
            return null;
        }

        if (strlen($numero) === 10) {
            return '521' . $numero;
        }

        if (strlen($numero) === 12 && str_starts_with($numero, '52')) {
            return '521' . substr($numero, 2);
        }

        return $numero;
    }

    public function comunicacionesEnviadas()
    {
        return $this->hasMany(Comunicacion::class, 'remitente_user_id');
    }

    public function comunicacionesDirectas()
    {
        return $this->hasMany(Comunicacion::class, 'destinatario_user_id');
    }

    public function comunicacionDestinatarios()
    {
        return $this->hasMany(ComunicacionDestinatario::class, 'user_id');
    }

    public function comunicacionesRecibidas()
    {
        return $this->belongsToMany(
            Comunicacion::class,
            'comunicacion_destinatarios',
            'user_id',
            'comunicacion_id'
        )
        ->withPivot([
            'leido_at',
            'enterado_at',
        ])
        ->withTimestamps();
    }
}
