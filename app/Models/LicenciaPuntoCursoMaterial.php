<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class LicenciaPuntoCursoMaterial extends Model
{
    public const TIPO_PDF = 'pdf';
    public const TIPO_LINK = 'link';
    public const TIPO_TEXTO = 'texto';

    protected $table = 'licencia_punto_curso_materiales';

    protected $fillable = [
        'curso_id',
        'titulo',
        'tipo',
        'archivo_path',
        'url',
        'contenido',
        'orden',
        'activo',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'orden' => 'integer',
        'activo' => 'boolean',
    ];

    public function curso()
    {
        return $this->belongsTo(LicenciaPuntoCurso::class, 'curso_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actualizador()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getTipoLabelAttribute(): string
    {
        return [
            self::TIPO_PDF => 'PDF',
            self::TIPO_LINK => 'Liga',
            self::TIPO_TEXTO => 'Texto',
        ][$this->tipo] ?? ucfirst((string) $this->tipo);
    }

    public function getArchivoUrlAttribute(): ?string
    {
        return $this->archivo_path ? Storage::disk('public')->url($this->archivo_path) : null;
    }
}
