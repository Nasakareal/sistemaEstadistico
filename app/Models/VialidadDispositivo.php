<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VialidadDispositivo extends Model
{
    use HasFactory;

    protected $table = 'vialidad_dispositivos';

    protected $fillable = [
        'client_uuid',
        'sync_status',
        'sync_error',
        'synced_at',
        'vialidad_dispositivo_catalogo_id',
        'unidad_id',
        'delegacion_id',
        'user_id',
        'created_by',
        'updated_by',
        'asunto',
        'fecha',
        'hora',
        'municipio',
        'lugar',
        'evento',
        'objetivo',
        'descripcion',
        'narrativa',
        'acciones_realizadas',
        'observaciones',
        'elementos',
        'crp',
        'motopatrullas',
        'fenix',
        'unidades_motorizadas',
        'patrullas',
        'gruas',
        'otros_apoyos',
        'supervision',
        'responsable_nombre',
        'responsable_cargo',
        'revisado',
        'revisado_por',
        'revisado_en',
    ];

    protected $casts = [
        'fecha' => 'date',
        'synced_at' => 'datetime',
        'revisado' => 'boolean',
        'revisado_en' => 'datetime',
        'elementos' => 'integer',
        'crp' => 'integer',
        'motopatrullas' => 'integer',
        'fenix' => 'integer',
        'unidades_motorizadas' => 'integer',
        'patrullas' => 'integer',
        'gruas' => 'integer',
        'otros_apoyos' => 'integer',
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

    public function catalogo()
    {
        return $this->belongsTo(VialidadDispositivoCatalogo::class, 'vialidad_dispositivo_catalogo_id');
    }

    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'unidad_id');
    }

    public function delegacion()
    {
        return $this->belongsTo(Delegacion::class, 'delegacion_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
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

    public function detalles()
    {
        return $this->hasMany(VialidadDispositivoDetalle::class, 'vialidad_dispositivo_id')->orderBy('orden');
    }

    public function fotos()
    {
        return $this->hasMany(VialidadDispositivoFoto::class, 'vialidad_dispositivo_id')->orderBy('orden');
    }

    public function fotoPortada()
    {
        return $this->hasOne(VialidadDispositivoFoto::class, 'vialidad_dispositivo_id')->where('portada', true);
    }
}
