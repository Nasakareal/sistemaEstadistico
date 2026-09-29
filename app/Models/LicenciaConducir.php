<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LicenciaConducir extends Model
{
    protected $table = 'licencias_conducir';

    protected $fillable = [
        'constancia_id',
        'examen_solicitud_id',
        'user_id',
        'numero',
        'qr_token',
        'curp',
        'apellido_paterno',
        'apellido_materno',
        'nombres',
        'fecha_nacimiento',
        'fecha_expedicion',
        'fecha_vencimiento',
        'fecha_antiguedad',
        'tipo_licencia',
        'genero',
        'tipo_sangre',
        'donador_organos',
        'restricciones',
        'oficina_emisora',
        'vehiculos_autorizados',
        'foto_path',
        'estatus',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_expedicion' => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_antiguedad' => 'date',
        'donador_organos' => 'boolean',
    ];

    public function constancia()
    {
        return $this->belongsTo(ConstanciaManejo::class, 'constancia_id');
    }

    public function examenSolicitud()
    {
        return $this->belongsTo(ConstanciaExamenSolicitud::class, 'examen_solicitud_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
