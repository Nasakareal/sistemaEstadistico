<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VialidadDispositivoDetalle extends Model
{
    use HasFactory;

    protected $table = 'vialidad_dispositivo_detalles';

    protected $fillable = [
        'vialidad_dispositivo_id',
        'created_by',
        'orden',
        'tipo',
        'titulo',
        'contenido',
        'ubicacion',
        'hora',
    ];

    protected $casts = [
        'orden' => 'integer',
    ];

    public function getHoraAttribute($value)
    {
        return self::normalizarHora($value);
    }

    public function setHoraAttribute($value)
    {
        $this->attributes['hora'] = self::normalizarHora($value);
    }

    public function dispositivo()
    {
        return $this->belongsTo(VialidadDispositivo::class, 'vialidad_dispositivo_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
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
}
