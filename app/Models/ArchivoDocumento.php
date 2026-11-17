<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchivoDocumento extends Model
{
    protected $table = 'archivo_documentos';

    protected $fillable = [
        'libro_id',
        'nombre',
        'nombre_original',
        'disco',
        'ruta_archivo',
        'mime_type',
        'tamano_bytes',
        'sha256',
        'pagina_inicio',
        'pagina_fin',
        'estado_revision',
        'usuario_carga_id',
    ];

    protected $casts = [
        'tamano_bytes' => 'integer',
        'pagina_inicio' => 'integer',
        'pagina_fin' => 'integer',
    ];

    public function libro()
    {
        return $this->belongsTo(ArchivoLibro::class, 'libro_id');
    }

    public function folios()
    {
        return $this->hasMany(ArchivoFolio::class, 'documento_id');
    }

    public function usuarioCarga()
    {
        return $this->belongsTo(User::class, 'usuario_carga_id');
    }
}
