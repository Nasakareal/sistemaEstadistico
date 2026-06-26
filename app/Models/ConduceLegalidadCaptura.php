<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConduceLegalidadCaptura extends Model
{
    use HasFactory;

    protected $table = 'conduce_legalidad_capturas';

    protected $fillable = [
        'operativo_id',
        'created_by',
        'unidad_id',
        'delegacion_id',
        'fecha',
        'hora',
        'municipio',
        'lugar',
        'lat',
        'lng',
        'coordenadas_texto',
        'narrativa',
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
    ];

    public function operativo()
    {
        return $this->belongsTo(ConduceLegalidadOperativo::class, 'operativo_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'unidad_id');
    }

    public function delegacion()
    {
        return $this->belongsTo(Delegacion::class, 'delegacion_id');
    }

    public function vehiculos()
    {
        return $this->hasMany(ConduceLegalidadVehiculo::class, 'captura_id');
    }

    public function personas()
    {
        return $this->hasMany(ConduceLegalidadPersona::class, 'captura_id');
    }
}
