<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PuestaDisposicion extends Model
{
    use HasFactory;

    protected $table = 'puestas_disposicion';

    protected $fillable = [
        'hecho_id',
        'numero_puesta',
        'anio',
        'tipo_puesta',
        'motivo',
        'estatus',
        'nombre_policia',
        'nombre_mp',
        'autoridad_receptora',
        'area',
        'carpeta_investigacion',
        'oficio',
        'fecha_puesta',
        'hora_puesta',
        'lugar_puesta',
        'narrativa',
        'observaciones',
        'unidad_id',
        'delegacion_id',
        'destacamento_id',
        'archivo_puesta',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha_puesta' => 'date',
        'hora_puesta' => 'datetime:H:i',
        'anio' => 'integer',
        'numero_puesta' => 'integer',
        'hecho_id' => 'integer',
        'unidad_id' => 'integer',
        'delegacion_id' => 'integer',
        'destacamento_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    public function hecho()
    {
        return $this->belongsTo(Hechos::class, 'hecho_id');
    }

    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'unidad_id');
    }

    public function delegacion()
    {
        return $this->belongsTo(Delegacion::class, 'delegacion_id');
    }

    public function destacamento()
    {
        return $this->belongsTo(Destacamento::class, 'destacamento_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actualizador()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function personas()
    {
        return $this->hasMany(PuestaDisposicionPersona::class, 'puesta_disposicion_id');
    }

    public function vehiculos()
    {
        return $this->hasMany(PuestaDisposicionVehiculo::class, 'puesta_disposicion_id');
    }

    public function objetos()
    {
        return $this->hasMany(PuestaDisposicionObjeto::class, 'puesta_disposicion_id');
    }

    public function fotos()
    {
        return $this->hasMany(PuestaDisposicionFoto::class, 'puesta_disposicion_id')
            ->orderBy('orden')
            ->orderBy('id');
    }
}
