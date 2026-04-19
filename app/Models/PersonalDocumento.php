<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonalDocumento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'personal_documentos';

    protected $fillable = [
        'personal_id',
        'documento_tipo_id',
        'numero',
        'fecha_emision',
        'fecha_vencimiento',
        'archivo_path',
        'archivo_nombre',
        'archivo_mime',
        'archivo_size',
        'hash_sha256',
        'activo',
        'observaciones',
        'oficio_comision_secretario',
        'fecha_oficio',
        'titular_firma_oficio',
        'oficio_asignacion',
        'fecha_asignacion',
        'titular_firma_asignacion',
        'archivo_oficio_comision',
        'archivo_oficio_asignacion',
    ];

    protected $casts = [
        'personal_id' => 'integer',
        'documento_tipo_id' => 'integer',
        'archivo_size' => 'integer',
        'activo' => 'boolean',
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_oficio' => 'date',
        'fecha_asignacion' => 'date',
    ];

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }

    public function documentoTipo()
    {
        return $this->belongsTo(DocumentoTipo::class, 'documento_tipo_id');
    }

    public function asignaciones()
    {
        return $this->hasMany(PersonalAsignacion::class, 'documento_id');
    }
}
