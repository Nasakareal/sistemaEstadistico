<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lesionado extends Model
{
    use HasFactory;

    protected $fillable = [
        'hecho_id',
        'nombre',
        'edad',
        'sexo',
        'tipo_lesion',
        'hospitalizado',
        'hospital',
        'atencion_en_sitio',
        'ambulancia',
        'paramedico',
        'observaciones',
    ];

    protected $casts = [
        'hospitalizado' => 'boolean',
        'atencion_en_sitio' => 'boolean',
    ];

    public function hecho(): BelongsTo
    {
        return $this->belongsTo(Hechos::class, 'hecho_id');
    }
}
