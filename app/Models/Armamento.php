<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Armamento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'armamentos';

    protected $fillable = [
        'unidad_id','tipo','clase','marca','modelo','matricula','serie','calibre','estatus','observaciones',
    ];

    protected $casts = [
        'unidad_id' => 'integer',
    ];

    public function unidad()
    {
        return $this->belongsTo(\App\Models\Unidad::class, 'unidad_id');
    }

    public function asignaciones()
    {
        return $this->hasMany(\App\Models\PersonalAsignacion::class,'armamento_id');
    }
}
