<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchivoMovimiento extends Model
{
    protected $table = 'archivo_movimientos';

    protected $fillable = [
        'libro_id',
        'tipo',
        'origen_area_id',
        'origen_bloque_id',
        'origen_posicion',
        'destino_area_id',
        'destino_bloque_id',
        'destino_posicion',
        'usuario_id',
        'responsable_externo',
        'motivo',
        'observaciones',
        'fecha_movimiento',
    ];

    protected $casts = [
        'origen_posicion' => 'integer',
        'destino_posicion' => 'integer',
        'fecha_movimiento' => 'datetime',
    ];

    public function libro()
    {
        return $this->belongsTo(ArchivoLibro::class, 'libro_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function areaOrigen()
    {
        return $this->belongsTo(ArchivoArea::class, 'origen_area_id');
    }

    public function bloqueOrigen()
    {
        return $this->belongsTo(ArchivoBloque::class, 'origen_bloque_id');
    }

    public function areaDestino()
    {
        return $this->belongsTo(ArchivoArea::class, 'destino_area_id');
    }

    public function bloqueDestino()
    {
        return $this->belongsTo(ArchivoBloque::class, 'destino_bloque_id');
    }
}
