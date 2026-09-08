<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CaleaEncuestaRespuesta extends Model
{
    protected $table = 'calea_encuesta_respuestas';
    protected $guarded = [];
    protected $casts = ['correcta' => 'boolean'];
}
