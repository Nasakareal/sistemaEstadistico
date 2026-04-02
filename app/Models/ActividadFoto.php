<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActividadFoto extends Model
{
    use HasFactory;

    protected $table = 'actividad_fotos';

    protected $fillable = [
        'actividad_id',
        'foto_path',
        'foto_nombre_original',
        'foto_hash',
        'orden',
        'created_by',
        'updated_by',
    ];

    public function actividad()
    {
        return $this->belongsTo(Actividad::class, 'actividad_id');
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
