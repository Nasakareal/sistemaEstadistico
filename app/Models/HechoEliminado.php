<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HechoEliminado extends Model
{
    protected $table = 'hechos_eliminados';

    protected $fillable = [
        'hecho_id_original',
        'unidad_org_id',
        'datos_hecho',
        'lesionados',
        'croquis',
        'dictamen_id',
        'eliminado_por',
        'eliminado_at',
        'hecho_id_restaurado',
        'restaurado_por',
        'restaurado_at',
    ];

    protected $casts = [
        'datos_hecho' => 'array',
        'lesionados' => 'array',
        'croquis' => 'array',
        'eliminado_at' => 'datetime',
        'restaurado_at' => 'datetime',
    ];

    public function vehiculos()
    {
        return $this->hasMany(HechoEliminadoVehiculo::class, 'hecho_eliminado_id');
    }

    public function eliminadoPor()
    {
        return $this->belongsTo(User::class, 'eliminado_por');
    }

    public function restauradoPor()
    {
        return $this->belongsTo(User::class, 'restaurado_por');
    }
}
