<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CaleaEncuestaRespuesta extends Model
{
    protected $table = 'calea_encuesta_respuestas';
    protected $guarded = [];
    protected $casts = ['correcta' => 'boolean'];
    public function pregunta() { return $this->belongsTo(CaleaEncuestaPregunta::class, 'pregunta_id'); }
    public function opcion() { return $this->belongsTo(CaleaEncuestaOpcion::class, 'opcion_id'); }
}
