<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Personal extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'personals';

    protected $fillable = [
        'unidad_id',
        'turno_id',
        'patrulla_id',
        'user_id',
        'numero_empleado',
        'numero_placa',
        'nombre',
        'ap_paterno',
        'ap_materno',
        'curp',
        'rfc',
        'cuip',
        'cup',
        'grado',
        'puesto',
        'adscripcion',
        'area',
        'categoria',
        'foto',
        'estatus',
        'fecha_ingreso',
        'fecha_baja',
    ];

    protected $casts = [
        'unidad_id' => 'integer',
        'turno_id' => 'integer',
        'patrulla_id' => 'integer',
        'user_id' => 'integer',
        'fecha_ingreso' => 'date',
        'fecha_baja' => 'date',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $personal) {
            if (!$personal->user_id || !$personal->wasChanged(['unidad_id', 'user_id'])) {
                return;
            }

            User::query()
                ->whereKey($personal->user_id)
                ->where(function ($query) use ($personal) {
                    $query->whereNull('unidad_id')
                        ->orWhere('unidad_id', '!=', $personal->unidad_id);
                })
                ->update(['unidad_id' => $personal->unidad_id]);
        });
    }

    public static function formarNombreCompleto($nombre, $apPaterno, $apMaterno): string
    {
        return trim(implode(' ', array_filter([
            $apPaterno,
            $apMaterno,
            $nombre,
        ], fn ($value) => trim((string) $value) !== '')));
    }

    public function nombreCompleto(): string
    {
        return self::formarNombreCompleto(
            $this->nombre,
            $this->ap_paterno,
            $this->ap_materno
        );
    }

    public function nombreCompletoConGrado(): string
    {
        return trim(implode(' ', array_filter([
            $this->grado,
            $this->nombreCompleto(),
        ], fn ($value) => trim((string) $value) !== '')));
    }

    public function getNombreCompletoAttribute(): string
    {
        return $this->nombreCompleto();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'unidad_id');
    }

    public function turno()
    {
        return $this->belongsTo(Turno::class, 'turno_id');
    }

    public function patrulla()
    {
        return $this->belongsTo(Patrulla::class, 'patrulla_id');
    }

    public function rolesServicio()
    {
        return $this->hasMany(PersonalRol::class, 'personal_id');
    }

    public function incidencias()
    {
        return $this->hasMany(PersonalIncidencia::class, 'personal_id');
    }

    public function documentos()
    {
        return $this->hasMany(PersonalDocumento::class, 'personal_id');
    }

    public function licencias()
    {
        return $this->hasMany(PersonalLicencia::class, 'personal_id');
    }

    public function asignaciones()
    {
        return $this->hasMany(PersonalAsignacion::class, 'personal_id');
    }

    public function contactos()
    {
        return $this->hasMany(PersonalContacto::class, 'personal_id');
    }

    public function emergencias()
    {
        return $this->hasMany(PersonalEmergencia::class, 'personal_id');
    }

    public function domicilios()
    {
        return $this->hasMany(PersonalDomicilio::class, 'personal_id');
    }

    public function fotos()
    {
        return $this->hasMany(PersonalFoto::class, 'personal_id');
    }

    public function domicilioActual()
    {
        return $this->hasOne(PersonalDomicilio::class, 'personal_id')->where('es_actual', true);
    }

    public function contactoPrincipal()
    {
        return $this->hasOne(PersonalContacto::class, 'personal_id')->where('es_principal', true);
    }

    public function fotoPrincipal()
    {
        return $this->hasOne(PersonalFoto::class, 'personal_id')->latestOfMany();
    }
}
