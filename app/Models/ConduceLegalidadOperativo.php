<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConduceLegalidadOperativo extends Model
{
    use HasFactory;

    protected $table = 'conduce_legalidad_operativos';

    protected $fillable = [
        'client_uuid',
        'nombre',
        'tipo_operativo',
        'fecha',
        'hora_inicio',
        'hora_cierre',
        'municipio',
        'lugar',
        'numero',
        'colonia',
        'codigo_postal',
        'lat',
        'lng',
        'coordenadas_texto',
        'objetivo',
        'narrativa',
        'observaciones',
        'estado',
        'created_by',
        'updated_by',
        'closed_by',
    ];

    protected $casts = [
        'fecha' => 'date',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
    ];

    public function capturas()
    {
        return $this->hasMany(ConduceLegalidadCaptura::class, 'operativo_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actualizador()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function cerrador()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
