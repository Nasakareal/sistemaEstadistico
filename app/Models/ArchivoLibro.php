<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArchivoLibro extends Model
{
    use SoftDeletes;

    protected $table = 'archivo_libros';

    protected $fillable = [
        'codigo',
        'qr_uuid',
        'titulo',
        'numero_libro',
        'anio_inicio',
        'anio_fin',
        'folio_inicial',
        'folio_final',
        'fecha_inicio',
        'fecha_fin',
        'total_paginas',
        'paginas_digitalizadas',
        'estado_digitalizacion',
        'estado_fisico',
        'bloque_destino_id',
        'posicion_destino',
        'area_actual_id',
        'bloque_actual_id',
        'posicion_actual',
        'observaciones',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'total_paginas' => 'integer',
        'paginas_digitalizadas' => 'integer',
        'posicion_destino' => 'integer',
        'posicion_actual' => 'integer',
    ];

    public function bloqueDestino()
    {
        return $this->belongsTo(ArchivoBloque::class, 'bloque_destino_id');
    }

    public function bloqueActual()
    {
        return $this->belongsTo(ArchivoBloque::class, 'bloque_actual_id');
    }

    public function areaActual()
    {
        return $this->belongsTo(ArchivoArea::class, 'area_actual_id');
    }

    public function documentos()
    {
        return $this->hasMany(ArchivoDocumento::class, 'libro_id');
    }

    public function folios()
    {
        return $this->hasMany(ArchivoFolio::class, 'libro_id');
    }

    public function movimientos()
    {
        return $this->hasMany(ArchivoMovimiento::class, 'libro_id');
    }
}
