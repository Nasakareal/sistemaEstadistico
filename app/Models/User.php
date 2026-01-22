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

    /**
     * Campos asignables
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'estado',
        'foto_perfil',
        'area',

        // Organización SSP
        'unidad_id',
        'turno_id',
        'patrulla_id',

        // 🔴 Control de ubicación (clave para jefes de grupo)
        'compartir_ubicacion',
    ];

    /**
     * Campos ocultos
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts
     */
    protected $casts = [
        'email_verified_at'   => 'datetime',
        'compartir_ubicacion' => 'boolean',
    ];

    /* =====================================================
     | RELACIONES ORGANIZACIONALES
     ===================================================== */

    /**
     * Unidad base del usuario
     */
    public function unidad()
    {
        return $this->belongsTo(Unidad::class);
    }

    /**
     * Unidades adicionales (Coordinador)
     */
    public function unidades()
    {
        return $this->belongsToMany(Unidad::class, 'unidad_user')->withTimestamps();
    }

    /**
     * Turno del usuario
     */
    public function turno()
    {
        return $this->belongsTo(Turno::class);
    }

    /**
     * Patrulla asignada
     */
    public function patrulla()
    {
        return $this->belongsTo(Patrulla::class);
    }

    /* =====================================================
     | HELPERS DE ROLES
     ===================================================== */

    public function isSuperadmin(): bool
    {
        return $this->hasRole('Superadmin');
    }

    public function isAdministrador(): bool
    {
        return $this->hasRole('Administrador') && !$this->isSuperadmin();
    }

    /* =====================================================
     | SCOPES DE VISIBILIDAD
     ===================================================== */

    /**
     * Usuarios visibles según el actor
     */
    public function scopeVisibleFor($query, ?self $actor)
    {
        if ($actor && $actor->isSuperadmin()) {
            return $query;
        }

        return $query->whereDoesntHave('roles', function ($q) {
            $q->where('name', 'Superadmin');
        });
    }

    /* =====================================================
     | PROTECCIÓN LÓGICA
     ===================================================== */

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
}
