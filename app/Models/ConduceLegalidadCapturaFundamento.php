<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConduceLegalidadCapturaFundamento extends Model
{
    use HasFactory;

    protected $table = 'conduce_legalidad_captura_fundamentos';

    protected $fillable = [
        'captura_id',
        'licencia_punto_infraccion_id',
        'orden',
        'infraccion_codigo',
        'fundamento_legal',
    ];

    protected $casts = [
        'orden' => 'integer',
    ];

    public function captura()
    {
        return $this->belongsTo(
            ConduceLegalidadCaptura::class,
            'captura_id'
        );
    }

    public function infraccion()
    {
        return $this->belongsTo(
            LicenciaPuntoInfraccion::class,
            'licencia_punto_infraccion_id'
        );
    }
}
