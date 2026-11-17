<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchivoFolio extends Model
{
    protected $table = 'archivo_folios';

    protected $fillable = [
        'libro_id',
        'anio',
        'folio',
        'fecha_siniestro',
        'numero_pagina',
        'documento_id',
        'pagina_documento',
        'estado_verificacion',
    ];

    protected $casts = [
        'anio' => 'integer',
        'fecha_siniestro' => 'date',
        'numero_pagina' => 'integer',
        'pagina_documento' => 'integer',
    ];

    public function libro()
    {
        return $this->belongsTo(ArchivoLibro::class, 'libro_id');
    }

    public function documento()
    {
        return $this->belongsTo(ArchivoDocumento::class, 'documento_id');
    }
}
