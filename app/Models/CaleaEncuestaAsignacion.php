<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CaleaEncuestaAsignacion extends Model
{
    protected $table = 'calea_encuesta_asignaciones';
    protected $guarded = [];
    protected $casts = ['todos' => 'boolean'];
}
