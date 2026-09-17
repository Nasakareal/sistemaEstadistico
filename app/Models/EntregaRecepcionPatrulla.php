<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntregaRecepcionPatrulla extends Model
{
    use HasFactory;

    protected $table = 'entrega_recepcion_patrullas';

    protected $fillable = [
        'patrulla_id',
        'fecha',
        'hora_programada',
        'hora_real',
        'entrega_user_id',
        'entrega_nombre',
        'recibe_user_id',
        'recibe_nombre',
        'kilometraje',
        'nivel_combustible',
        'estado_carroceria',
        'estado_interiores',
        'estado_llantas',
        'estado_luces',
        'estado_torreta',
        'estado_sirena',
        'estado_radio',
        'estado_mecanico',
        'trae_refaccion',
        'trae_gato',
        'trae_llave_cruz',
        'trae_extintor',
        'trae_botiquin',
        'equipo_adicional',
        'danos_existentes',
        'novedades',
        'observaciones',
        'foto_frontal',
        'foto_trasera',
        'foto_lateral_izquierdo',
        'foto_lateral_derecho',
        'foto_tablero',
        'aceptada_entrega',
        'aceptada_recepcion',
        'entrega_confirmada_at',
        'recepcion_confirmada_at',
    ];

    protected $casts = [
        'patrulla_id' => 'integer',
        'fecha' => 'date',
        'entrega_user_id' => 'integer',
        'recibe_user_id' => 'integer',
        'kilometraje' => 'integer',
        'nivel_combustible' => 'decimal:2',
        'trae_refaccion' => 'boolean',
        'trae_gato' => 'boolean',
        'trae_llave_cruz' => 'boolean',
        'trae_extintor' => 'boolean',
        'trae_botiquin' => 'boolean',
        'aceptada_entrega' => 'boolean',
        'aceptada_recepcion' => 'boolean',
        'entrega_confirmada_at' => 'datetime',
        'recepcion_confirmada_at' => 'datetime',
    ];

    public function patrulla(): BelongsTo
    {
        return $this->belongsTo(Patrulla::class, 'patrulla_id');
    }

    public function entregaUsuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entrega_user_id');
    }

    public function recibeUsuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recibe_user_id');
    }

    public function scopeDePatrulla($query, int $patrullaId)
    {
        return $query->where('patrulla_id', $patrullaId);
    }

    public function scopeDeFecha($query, $fecha)
    {
        return $query->whereDate('fecha', $fecha);
    }

    public function scopePendientes($query)
    {
        return $query->where(function ($q) {
            $q->where('aceptada_entrega', false)
                ->orWhere('aceptada_recepcion', false);
        });
    }

    public function scopeCompletadas($query)
    {
        return $query
            ->where('aceptada_entrega', true)
            ->where('aceptada_recepcion', true);
    }

    public function getEstaCompletaAttribute(): bool
    {
        return $this->aceptada_entrega && $this->aceptada_recepcion;
    }

    public function getTieneNovedadesAttribute(): bool
    {
        return filled($this->novedades)
            || filled($this->danos_existentes)
            || filled($this->observaciones);
    }
}
