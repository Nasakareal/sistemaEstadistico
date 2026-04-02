<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Actividad extends Model
{
    use HasFactory;

    protected $table = 'actividades';

    protected $fillable = [
        'client_uuid',
        'sync_status',
        'sync_error',
        'synced_at',
        'actividad_categoria_id',
        'actividad_subcategoria_id',
        'nombre',
        'cantidad',
        'foto_path',
        'foto_nombre_original',
        'foto_hash',
        'created_by',
        'updated_by',
        'unidad_org_id',
        'delegacion_id',
        'destacamento_id',
        'fecha',
        'hora',
        'lugar',
        'municipio',
        'carretera',
        'tramo',
        'kilometro',
        'lat',
        'lng',
        'coordenadas_texto',
        'fuente_ubicacion',
        'nota_geo',
        'motivo',
        'narrativa',
        'acciones_realizadas',
        'observaciones',
        'personas_alcanzadas',
        'personas_participantes',
        'personas_detenidas',
        'elementos_participantes_texto',
        'patrullas_participantes_texto',
        'estado_revision',
        'revisado_por',
        'revisado_at',
        'observacion_revision',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'personas_alcanzadas' => 'integer',
        'personas_participantes' => 'integer',
        'personas_detenidas' => 'integer',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'fecha' => 'date',
        'hora' => 'datetime:H:i:s',
        'synced_at' => 'datetime',
        'revisado_at' => 'datetime',
    ];

    public function categoria()
    {
        return $this->belongsTo(ActividadCategoria::class, 'actividad_categoria_id');
    }

    public function subcategoria()
    {
        return $this->belongsTo(ActividadSubcategoria::class, 'actividad_subcategoria_id');
    }

    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'unidad_org_id');
    }

    public function delegacion()
    {
        return $this->belongsTo(Delegacion::class, 'delegacion_id');
    }

    public function destacamento()
    {
        return $this->belongsTo(Destacamento::class, 'destacamento_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actualizador()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function revisor()
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }

    public function fotos()
    {
        return $this->hasMany(ActividadFoto::class, 'actividad_id')->orderBy('orden');
    }
}
