<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Croquis extends Model
{
    use HasFactory;

    protected $table = 'croquis';

    protected $fillable = [
        'hecho_id',
        'client_uuid',
        'titulo',
        'plantilla',
        'orientacion',
        'escala',
        'json_dibujo',
        'imagen_preview',
        'pdf_path',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'json_dibujo' => 'array',
        'escala' => 'decimal:2'
    ];

    public function hecho()
    {
        return $this->belongsTo(Hechos::class, 'hecho_id');
    }
}
