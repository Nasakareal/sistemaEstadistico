<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConstanciaManejo extends Model
{
    protected $table = 'constancias_manejo';

    protected $fillable = [
        'folio',
        'folio_qr',
        'modulo_id',
        'delegacion_id',
        'user_id',
        'perito_activador_id',
        'nombre_solicitante',
        'curp',
        'telefono',
        'tipo_licencia',
        'tipo_examen',
        'estatus',
        'fecha_impresion',
        'fecha_activacion',
        'fecha_expiracion',
        'pdf_path',
        'qr_token',
        'acceso_examen_token',
        'acceso_examen_expira',
    ];

    protected $casts = [
        'fecha_impresion' => 'datetime',
        'fecha_activacion' => 'datetime',
        'fecha_expiracion' => 'datetime',
        'acceso_examen_expira' => 'datetime',
    ];

    public function modulo()
    {
        return $this->belongsTo(ConstanciaModulo::class, 'modulo_id');
    }

    public function folioRegistro()
    {
        return $this->hasOne(ConstanciaFolio::class, 'constancia_id');
    }

    public function examen()
    {
        return $this->hasOne(ConstanciaExamen::class, 'constancia_id');
    }

    public function activaciones()
    {
        return $this->hasMany(ConstanciaActivacion::class, 'constancia_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function peritoActivador()
    {
        return $this->belongsTo(User::class, 'perito_activador_id');
    }

    public function getEstaVigenteAttribute()
    {
        return $this->estatus === 'ACTIVA' && $this->fecha_expiracion && now()->lessThanOrEqualTo($this->fecha_expiracion);
    }

    public function getEstaExpiradaAttribute()
    {
        return $this->fecha_expiracion && now()->greaterThan($this->fecha_expiracion);
    }
}
