<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DestacamentoRedApoyo extends Model
{
    use HasFactory;

    public const NIVELES_GOBIERNO = [
        'Federal' => 'Federal',
        'Estatal' => 'Estatal',
        'Municipal' => 'Municipal',
        'Otro' => 'Otro',
    ];

    public const TIPOS_APOYO = [
        'Seguridad publica' => 'Seguridad publica',
        'Procuracion de justicia' => 'Procuracion de justicia',
        'Proteccion civil' => 'Proteccion civil',
        'Salud' => 'Salud',
        'Transito y vialidad' => 'Transito y vialidad',
        'Gobierno municipal' => 'Gobierno municipal',
        'Gruas y corralones' => 'Gruas y corralones',
        'Otro' => 'Otro',
    ];

    public const TIPOS_APOYO_LABELS = [
        'Seguridad publica' => 'Seguridad publica',
        'Procuracion de justicia' => 'Procuracion de justicia',
        'Proteccion civil' => 'Proteccion civil',
        'Salud' => 'Salud',
        'Transito y vialidad' => 'Transito y vialidad',
        'Gobierno municipal' => 'Gobierno municipal',
        'Gruas y corralones' => 'Gruas y corralones',
        'Otro' => 'Otro',
    ];

    protected $table = 'destacamento_red_apoyos';

    protected $fillable = [
        'destacamento_id',
        'delegacion_id',
        'region',
        'tipo_apoyo',
        'nivel_gobierno',
        'institucion',
        'contacto',
        'cargo',
        'telefono',
        'telefono_secundario',
        'direccion',
        'municipio',
        'observaciones',
        'activo',
        'orden',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    public function destacamento()
    {
        return $this->belongsTo(Destacamento::class, 'destacamento_id');
    }

    public function delegacion()
    {
        return $this->belongsTo(Delegacion::class, 'delegacion_id');
    }
}
