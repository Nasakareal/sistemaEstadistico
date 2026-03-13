<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'email',
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
        'email_verified_at'          => 'datetime',
        'compartir_ubicacion'        => 'boolean',
        'last_seen_at'               => 'datetime',
        'disconnected_alert_sent_at' => 'datetime',
    ];

    public function personal()
    {
        return $this->hasOne(\App\Models\Personal::class, 'user_id');
    }

    public function unidad()
    {
        return $this->belongsTo(\App\Models\Unidad::class, 'unidad_id');
    }

    public function unidades()
    {
        return $this->belongsToMany(\App\Models\Unidad::class, 'unidad_user', 'user_id', 'unidad_id')
            ->withTimestamps();
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

    public function destacamento()
    {
        return $this->belongsTo(\App\Models\Destacamento::class, 'destacamento_id');
    }
}
