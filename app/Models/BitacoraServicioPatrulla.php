<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BitacoraServicioPatrulla extends Model
{
    use HasFactory;

    protected $table = 'bitacora_servicio_patrullas';

    protected $fillable = [
        'patrulla_id',
        'turno_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'capturado_por_user_id',
        'capturado_por_nombre',
        'kilometraje_inicio',
        'kilometraje_fin',
        'combustible_inicio',
        'combustible_fin',
        'observaciones',
        'estatus',
        'cerrada_at',
    ];

    protected $casts = [
        'patrulla_id' => 'integer',
        'turno_id' => 'integer',
        'capturado_por_user_id' => 'integer',
        'fecha' => 'date',
        'kilometraje_inicio' => 'integer',
        'kilometraje_fin' => 'integer',
        'combustible_inicio' => 'decimal:2',
        'combustible_fin' => 'decimal:2',
        'cerrada_at' => 'datetime',
    ];

    public function patrulla(): BelongsTo
    {
        return $this->belongsTo(Patrulla::class, 'patrulla_id');
    }

    public function turno(): BelongsTo
    {
        return $this->belongsTo(Turno::class, 'turno_id');
    }

    public function capturadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'capturado_por_user_id');
    }

    public function scopeDePatrulla($query, int $patrullaId)
    {
        return $query->where('patrulla_id', $patrullaId);
    }

    public function scopeDeFecha($query, $fecha)
    {
        return $query->whereDate('fecha', $fecha);
    }

    public function scopeAbiertas($query)
    {
        return $query->where('estatus', 'abierta');
    }

    public function scopeCerradas($query)
    {
        return $query->where('estatus', 'cerrada');
    }

    public function getEstaCerradaAttribute(): bool
    {
        return $this->estatus === 'cerrada';
    }

    public function getEstaAbiertaAttribute(): bool
    {
        return $this->estatus === 'abierta';
    }

    public function getKilometrosRecorridosAttribute(): ?int
    {
        if (
            $this->kilometraje_inicio === null ||
            $this->kilometraje_fin === null
        ) {
            return null;
        }

        return max(
            0,
            (int) $this->kilometraje_fin - (int) $this->kilometraje_inicio
        );
    }
}
