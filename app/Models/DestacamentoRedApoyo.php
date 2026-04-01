<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DestacamentoRedApoyo extends Model
{
    use HasFactory;

    protected $table = 'destacamento_red_apoyos';

    protected $fillable = [
        'destacamento_id',
        'tipo_apoyo',
        'institucion',
        'contacto',
        'cargo',
        'telefono',
        'telefono_secundario',
        'direccion',
        'municipio',
        'observaciones',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function destacamento()
    {
        return $this->belongsTo(Destacamento::class, 'destacamento_id');
    }
}
