<?php

namespace App\Models;

use DateTimeInterface;
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
        'foto_blob_path',
        'foto_nombre_original',
        'foto_hash',
        'foto_thumbnail_path',
        'foto_thumbnail_blob_path',
        'foto_blob_copiada_at',
        'foto_archivo_zip_path',
        'foto_archivada_at',
        'foto_eliminada_at',
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
        'km_recorridos',
        'coordenadas_texto',
        'fuente_ubicacion',
        'nota_geo',
        'motivo',
        'narrativa',
        'acciones_realizadas',
        'observaciones',
        'infracciones_actividad',
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
        'km_recorridos' => 'decimal:2',
        'fecha' => 'date',
        'synced_at' => 'datetime',
        'revisado_at' => 'datetime',
        'foto_blob_copiada_at' => 'datetime',
        'foto_archivada_at' => 'datetime',
        'foto_eliminada_at' => 'datetime',
        'infracciones_actividad' => 'array',
    ];

    public function getHoraAttribute($value)
    {
        return self::normalizarHora($value);
    }

    public function setHoraAttribute($value)
    {
        $this->attributes['hora'] = self::normalizarHora($value);
    }

    private static function normalizarHora($value)
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('H:i:s');
        }

        if ($value === null) {
            return null;
        }

        $hora = trim((string) $value);
        if ($hora === '') {
            return null;
        }

        if (preg_match('/([01]?\d|2[0-3]):([0-5]\d)(?::([0-5]\d))?/', $hora, $matches)) {
            return str_pad($matches[1], 2, '0', STR_PAD_LEFT) . ':' . $matches[2] . ':' . ($matches[3] ?? '00');
        }

        return null;
    }

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
        return $this->hasMany(ActividadFoto::class, 'actividad_id')
            ->whereNull('foto_eliminada_at')
            ->where(function ($q) {
                $q->whereNull('foto_archivada_at')
                    ->orWhereNotNull('foto_thumbnail_path');
            })
            ->orderBy('orden')
            ->orderBy('id');
    }

    public function fotosTodas()
    {
        return $this->hasMany(ActividadFoto::class, 'actividad_id')
            ->orderBy('orden')
            ->orderBy('id');
    }

    public function vehiculos()
    {
        return $this->belongsToMany(\App\Models\Vehiculo::class, 'actividad_vehiculo', 'actividad_id', 'vehiculo_id')->withTimestamps();
    }

    public function conduceLegalidadCaptura()
    {
        return $this->hasOne(ConduceLegalidadCaptura::class, 'actividad_id');
    }

    public function puestaDisposicion()
    {
        return $this->hasOne(PuestaDisposicion::class, 'actividad_id')->latestOfMany();
    }

    public function fomentoCulturaVialDetalle()
    {
        return $this->hasOne(FomentoCulturaVialDetalle::class, 'actividad_id');
    }
}
