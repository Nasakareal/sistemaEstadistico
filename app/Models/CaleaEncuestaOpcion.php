<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CaleaEncuestaOpcion extends Model
{
    protected $table = 'calea_encuesta_opciones';
    protected $guarded = [];
    protected $casts = ['es_correcta' => 'boolean'];
}
