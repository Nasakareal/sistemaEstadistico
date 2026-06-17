<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LicenciaPuntoCuenta extends Model
{
    public const SALDO_INICIAL = 8;
    public const SALDO_MAXIMO = 8;
    public const MESES_RECUPERACION_TIEMPO = 18;

    public const ESTADO_VIGENTE = 'vigente';
    public const ESTADO_PROCEDIMIENTO = 'procedimiento_administrativo';
    public const ESTADO_SUSPENDIDA = 'suspendida';
    public const ESTADO_CANCELADA = 'cancelada';

    protected $table = 'licencia_punto_cuentas';

    protected $fillable = [
        'conductor_id',
        'numero_licencia',
        'tipo_licencia',
        'titular_nombre',
        'curp',
        'telefono',
        'fecha_emision',
        'fecha_vencimiento',
        'saldo_actual',
        'estado',
        'fecha_ultima_infraccion',
        'fecha_agotamiento',
        'reincidencias_cero',
        'expediente_folio',
        'oficio_folio',
        'finanzas_notificado_at',
        'titular_notificado_at',
        'token_consulta',
        'observaciones',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_ultima_infraccion' => 'datetime',
        'fecha_agotamiento' => 'datetime',
        'finanzas_notificado_at' => 'datetime',
        'titular_notificado_at' => 'datetime',
        'saldo_actual' => 'integer',
        'reincidencias_cero' => 'integer',
    ];

    public function conductor()
    {
        return $this->belongsTo(Conductor::class, 'conductor_id');
    }

    public function movimientos()
    {
        return $this->hasMany(LicenciaPuntoMovimiento::class, 'cuenta_id');
    }

    public function alertas()
    {
        return $this->hasMany(LicenciaPuntoAlerta::class, 'cuenta_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actualizador()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeCriticas(Builder $query): Builder
    {
        return $query->where('saldo_actual', '<=', 2);
    }

    public function scopeEnAdvertencia(Builder $query): Builder
    {
        return $query->where('saldo_actual', '<=', 4);
    }

    public function getEstadoLabelAttribute(): string
    {
        return [
            self::ESTADO_VIGENTE => 'Vigente',
            self::ESTADO_PROCEDIMIENTO => 'Procedimiento administrativo',
            self::ESTADO_SUSPENDIDA => 'Suspendida',
            self::ESTADO_CANCELADA => 'Cancelada',
        ][$this->estado] ?? ucfirst(str_replace('_', ' ', (string) $this->estado));
    }

    public function getNivelSaldoAttribute(): string
    {
        $saldo = (int) $this->saldo_actual;

        if ($saldo <= 0) {
            return 'agotado';
        }

        if ($saldo <= 2) {
            return 'critico';
        }

        if ($saldo <= 4) {
            return 'advertencia';
        }

        return 'normal';
    }

    public function getFechaRecuperacionAttribute(): ?Carbon
    {
        if ((int) $this->saldo_actual >= self::SALDO_MAXIMO) {
            return null;
        }

        $base = $this->fecha_ultima_infraccion
            ?: $this->fecha_emision
            ?: $this->created_at;

        return $base ? Carbon::parse($base)->copy()->addMonthsNoOverflow(self::MESES_RECUPERACION_TIEMPO) : null;
    }

    public function puedeRecuperarPorTiempo(?Carbon $fecha = null): bool
    {
        if ((int) $this->saldo_actual >= self::SALDO_MAXIMO) {
            return false;
        }

        $recuperacion = $this->fecha_recuperacion;

        if (!$recuperacion) {
            return false;
        }

        return ($fecha ?: Carbon::now())->greaterThanOrEqualTo($recuperacion);
    }
}
