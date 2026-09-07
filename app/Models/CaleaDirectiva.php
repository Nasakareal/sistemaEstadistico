<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaleaDirectiva extends Model
{
    use HasFactory;

    protected $table = 'calea_directivas';

    protected $fillable = [
        'codigo',
        'nivel_1',
        'nivel_2',
        'nivel_3',
        'titulo',
        'categoria',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'nivel_1' => 'integer',
        'nivel_2' => 'integer',
        'nivel_3' => 'integer',
        'activo' => 'boolean',
    ];

    public function versiones()
    {
        return $this->hasMany(
            CaleaDirectivaVersion::class,
            'calea_directiva_id'
        );
    }

    public function versionVigente()
    {
        return $this->hasOne(
            CaleaDirectivaVersion::class,
            'calea_directiva_id'
        )
        ->where('vigente', true)
        ->latestOfMany('numero_version');
    }
}
