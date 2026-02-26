<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalAsignacion extends Model
{
    use HasFactory;

    protected $table = 'personal_asignacions';

    protected $fillable = [
        'personal_id',
        'armamento_id',
        'fecha_asignacion',
        'fecha_fin',
        'folio',
        'documento_id',
        'observaciones',
        'activo',
    ];

    protected $casts = [
        'personal_id' => 'integer',
        'armamento_id' => 'integer',
        'documento_id' => 'integer',
        'activo' => 'boolean',
        'fecha_asignacion' => 'date',
        'fecha_fin' => 'date',
    ];

    public function personal()
    {
        return $this->belongsTo(\App\Models\Personal::class, 'personal_id');
    }

    public function armamento()
    {
        return $this->belongsTo(\App\Models\Armamento::class, 'armamento_id');
    }
}
