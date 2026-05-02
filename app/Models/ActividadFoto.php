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
        'foto_thumbnail_path',
        'foto_archivo_zip_path',
        'foto_archivada_at',
        'foto_eliminada_at',
        'orden',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'foto_archivada_at' => 'datetime',
        'foto_eliminada_at' => 'datetime',
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
