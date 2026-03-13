<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Actividad extends Model
{
    use HasFactory;

    protected $table = 'actividades';

    protected $fillable = [
        'actividad_categoria_id',
        'actividad_subcategoria_id',
        'nombre',
        'cantidad',
        'foto_path',
        'foto_nombre_original',
        'foto_hash',
        'created_by',
        'updated_by',
        'unidad_org_id',
        'delegacion_id',
    ];

    protected $casts = [
        'cantidad' => 'integer',
    ];

    public function categoria()
    {
        return $this->belongsTo(ActividadCategoria::class, 'actividad_categoria_id');
    }

    public function subcategoria()
    {
        return $this->belongsTo(ActividadSubcategoria::class, 'actividad_subcategoria_id');
    }

    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'unidad_org_id');
    }
}
