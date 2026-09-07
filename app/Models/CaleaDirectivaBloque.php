<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaleaDirectivaBloque extends Model
{
    use HasFactory;

    protected $table = 'calea_directiva_bloques';

    protected $fillable = [
        'calea_directiva_seccion_id',
        'parent_id',
        'orden',
        'tipo',
        'numero',
        'titulo',
        'contenido',
        'pagina_inicio',
        'pagina_fin',
        'buscable',
        'citable',
    ];

    protected $casts = [
        'orden' => 'integer',
        'pagina_inicio' => 'integer',
        'pagina_fin' => 'integer',
        'buscable' => 'boolean',
        'citable' => 'boolean',
    ];

    public function seccion()
    {
        return $this->belongsTo(
            CaleaDirectivaSeccion::class,
            'calea_directiva_seccion_id'
        );
    }

    public function padre()
    {
        return $this->belongsTo(
            CaleaDirectivaBloque::class,
            'parent_id'
        );
    }

    public function hijos()
    {
        return $this->hasMany(
            CaleaDirectivaBloque::class,
            'parent_id'
        )->orderBy('orden');
    }

    public function hijosRecursivos()
    {
        return $this->hijos()->with('hijosRecursivos');
    }

    public function scopeBuscables($query)
    {
        return $query->where('buscable', true);
    }

    public function scopeCitables($query)
    {
        return $query->where('citable', true);
    }
}
