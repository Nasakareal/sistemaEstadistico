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

        'compartir_ubicacion',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at'            => 'datetime',
        'compartir_ubicacion'          => 'boolean',
        'last_seen_at'                 => 'datetime',
        'disconnected_alert_sent_at'   => 'datetime',
    ];

    /* =====================================================
     | RELACIONES ORGANIZACIONALES
     ===================================================== */

    public function unidad()
    {
        return $this->belongsTo(Unidad::class);
    }

    public function unidades()
    {
        return $this->belongsToMany(Unidad::class, 'unidad_user')->withTimestamps();
    }

    public function turno()
    {
        return $this->belongsTo(Turno::class);
    }

    public function patrulla()
    {
        return $this->belongsTo(Patrulla::class);
    }

    // ✅ Delegación fija del usuario (cuando aplique)
    public function delegacion()
    {
        return $this->belongsTo(Delegacion::class, 'delegacion_id');
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
