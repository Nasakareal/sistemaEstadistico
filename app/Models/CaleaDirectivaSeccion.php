<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaleaDirectivaSeccion extends Model
{
    use HasFactory;

    public const TIPOS = [
        'proposito' => 'Propósito',
        'alcance' => 'Alcance',
        'marco_juridico' => 'Marco Jurídico',
        'directiva' => 'Directiva',
        'procedimiento' => 'Procedimiento',
        'definiciones' => 'Definiciones',
        'responsabilidades' => 'Responsabilidades',
        'supervision' => 'Supervisión',
        'anexos' => 'Anexos',
        'otro' => 'Otro',
    ];

    protected $table = 'calea_directiva_secciones';

    protected $fillable = [
        'calea_directiva_version_id',
        'orden',
        'numero',
        'tipo',
        'titulo',
        'contenido',
        'pagina_inicio',
        'pagina_fin',
    ];

    protected $casts = [
        'orden' => 'integer',
        'pagina_inicio' => 'integer',
        'pagina_fin' => 'integer',
    ];

    public function version()
    {
        return $this->belongsTo(
            CaleaDirectivaVersion::class,
            'calea_directiva_version_id'
        );
    }

    public function bloques()
    {
        return $this->hasMany(
            CaleaDirectivaBloque::class,
            'calea_directiva_seccion_id'
        )->orderBy('orden');
    }

    public function bloquesRaiz()
    {
        return $this->hasMany(
            CaleaDirectivaBloque::class,
            'calea_directiva_seccion_id'
        )
        ->whereNull('parent_id')
        ->orderBy('orden');
    }
}
