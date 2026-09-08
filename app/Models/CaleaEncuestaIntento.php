<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CaleaEncuestaIntento extends Model
{
    protected $table = 'calea_encuesta_intentos';
    protected $guarded = [];
    protected $casts = ['iniciado_at' => 'datetime', 'expira_at' => 'datetime', 'finalizado_at' => 'datetime', 'aprobado' => 'boolean', 'calificacion' => 'float'];
    public function encuesta() { return $this->belongsTo(CaleaEncuesta::class, 'encuesta_id'); }
    public function respuestas() { return $this->hasMany(CaleaEncuestaRespuesta::class, 'intento_id'); }
}
