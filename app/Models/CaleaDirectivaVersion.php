<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaleaDirectivaVersion extends Model
{
    use HasFactory;

    protected $table = 'calea_directiva_versiones';

    protected $fillable = [
        'calea_directiva_id',
        'numero_version',
        'nombre_version',
        'fecha_emision',
        'mes_emision',
        'anio_emision',
        'fecha_revision',
        'area_responsable',
        'autoriza',
        'realizado_por',
        'leyenda_documento',
        'archivo_disk',
        'archivo_path',
        'archivo_nombre_original',
        'archivo_mime',
        'archivo_size',
        'archivo_sha256',
        'numero_paginas',
        'texto_busqueda',
        'vigente',
        'publicada',
        'created_by',
    ];

    protected $casts = [
        'numero_version' => 'integer',
        'fecha_emision' => 'date',
        'mes_emision' => 'integer',
        'anio_emision' => 'integer',
        'fecha_revision' => 'date',
        'archivo_size' => 'integer',
        'numero_paginas' => 'integer',
        'vigente' => 'boolean',
        'publicada' => 'boolean',
    ];

    public function directiva()
    {
        return $this->belongsTo(
            CaleaDirectiva::class,
            'calea_directiva_id'
        );
    }

    public function secciones()
    {
        return $this->hasMany(
            CaleaDirectivaSeccion::class,
            'calea_directiva_version_id'
        )->orderBy('orden');
    }

    public function creador()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function scopeVigentes($query)
    {
        return $query->where('vigente', true);
    }

    public function scopePublicadas($query)
    {
        return $query->where('publicada', true);
    }
}
