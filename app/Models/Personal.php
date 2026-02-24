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
        'nombre',
        'ap_paterno',
        'ap_materno',
        'curp',
        'rfc',
        'cuip',
        'grado',
        'puesto',
        'adscripcion',
        'area',
        'estatus',
        'fecha_ingreso',
        'fecha_baja',
    ];

    protected $casts = [
        'unidad_id' => 'integer',
        'turno_id' => 'integer',
        'patrulla_id' => 'integer',
        'fecha_ingreso' => 'date',
        'fecha_baja' => 'date',
    ];

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

    public function rolesServicio()
    {
        return $this->hasMany(\App\Models\PersonalRol::class, 'personal_id');
    }

    public function incidencias()
    {
        return $this->hasMany(\App\Models\PersonalIncidencia::class, 'personal_id');
    }

    public function documentos()
    {
        return $this->hasMany(\App\Models\PersonalDocumento::class, 'personal_id');
    }

    public function asignaciones()
    {
        return $this->hasMany(\App\Models\PersonalAsignacion::class, 'personal_id');
    }

    public function contactos()
    {
        return $this->hasMany(\App\Models\PersonalContacto::class, 'personal_id');
    }

    public function emergencias()
    {
        return $this->hasMany(\App\Models\PersonalEmergencia::class, 'personal_id');
    }

    public function domicilios()
    {
        return $this->hasMany(\App\Models\PersonalDomicilio::class, 'personal_id');
    }

    public function domicilioActual()
    {
        return $this->hasOne(\App\Models\PersonalDomicilio::class, 'personal_id')
            ->where('es_actual', true);
    }

    public function contactoPrincipal()
    {
        return $this->hasOne(\App\Models\PersonalContacto::class, 'personal_id')
            ->where('es_principal', true);
    }
}
