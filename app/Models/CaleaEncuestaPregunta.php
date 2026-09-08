<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CaleaEncuestaPregunta extends Model
{
    protected $table = 'calea_encuesta_preguntas';
    protected $guarded = [];
    protected $casts = ['obligatoria' => 'boolean'];
    public function opciones() { return $this->hasMany(CaleaEncuestaOpcion::class, 'pregunta_id')->orderBy('orden'); }
}
