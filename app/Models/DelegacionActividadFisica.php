<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DelegacionActividadFisica extends Model
{
    use HasFactory;

    protected $table = 'delegacion_actividades_fisicas';

    protected $fillable = [
        'delegacion_id',
        'fecha',
        'hora',
        'tipo_ejercicio',
        'elementos_participantes',
        'foto_path',
        'foto_nombre_original',
        'foto_hash',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha' => 'date',
        'elementos_participantes' => 'integer',
    ];

    public function delegacion()
    {
        return $this->belongsTo(Delegacion::class, 'delegacion_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actualizador()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
