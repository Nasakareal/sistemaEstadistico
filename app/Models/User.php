<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
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
        'email_verified_at' => 'datetime',
    ];

    /* =====================================================
     | HELPERS DE ROLES
     ===================================================== */

    /**
     * ¿Es Superadmin?
     */
    public function isSuperadmin(): bool
    {
        return $this->hasRole('Superadmin');
    }

    /**
     * ¿Es Administrador (pero no Superadmin)?
     */
    public function isAdministrador(): bool
    {
        return $this->hasRole('Administrador') && !$this->isSuperadmin();
    }

    /* =====================================================
     | SCOPES DE VISIBILIDAD
     ===================================================== */

    /**
     * Scope: usuarios visibles según quién consulta
     *
     * - Superadmin ve todo
     * - Cualquier otro NO ve usuarios Superadmin
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
     | PROTECCIÓN LÓGICA (APOYO A CONTROLLERS)
     ===================================================== */

    /**
     * ¿Este usuario puede ser degradado de Superadmin?
     * (no se permite si es el último)
     */
    public function canBeDemotedFromSuperadmin(): bool
    {
        if (!$this->isSuperadmin()) {
            return true;
        }

        return self::role('Superadmin')->count() > 1;
    }

    /**
     * ¿Este usuario puede ser eliminado?
     * (no se permite si es el último Superadmin)
     */
    public function canBeDeleted(): bool
    {
        return $this->canBeDemotedFromSuperadmin();
    }
}
