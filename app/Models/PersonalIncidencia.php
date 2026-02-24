<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalIncidencia extends Model
{
    use HasFactory;

    protected $table = 'personal_incidencias';

    protected $fillable = [
        'personal_id',
        'incidencia_tipo_id',
        'fecha_inicio',
        'fecha_fin',
        'hora_inicio',
        'hora_fin',
        'folio',
        'motivo',
        'observaciones',
        'documento_id',
        'activo',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
        'activo'       => 'boolean',
    ];

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }

    public function tipo()
    {
        return $this->belongsTo(IncidenciaTipo::class, 'incidencia_tipo_id');
    }

    // Si documento_id apunta a una tabla documentos:
    public function documento()
    {
        return $this->belongsTo(Documento::class, 'documento_id');
    }
}
