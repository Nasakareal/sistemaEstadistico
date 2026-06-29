<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LicenciaPuntoInfraccion extends Model
{
    protected $table = 'licencia_punto_infracciones';

    protected $fillable = [
        'codigo',
        'nombre',
        'articulo',
        'fraccion',
        'inciso',
        'ambito_vehiculo',
        'puntos',
        'multa_uma_min',
        'multa_uma_max',
        'amonestacion',
        'arresto_persona',
        'suspension_licencia',
        'cancelacion_licencia',
        'deposito_si_sin_persona_habilitada',
        'retencion_vehiculo',
        'descripcion',
        'fundamento_legal',
        'activa',
    ];

    protected $casts = [
        'puntos' => 'integer',
        'multa_uma_min' => 'integer',
        'multa_uma_max' => 'integer',
        'amonestacion' => 'boolean',
        'arresto_persona' => 'boolean',
        'suspension_licencia' => 'boolean',
        'cancelacion_licencia' => 'boolean',
        'deposito_si_sin_persona_habilitada' => 'boolean',
        'retencion_vehiculo' => 'boolean',
        'activa' => 'boolean',
    ];

    public function movimientos()
    {
        return $this->hasMany(LicenciaPuntoMovimiento::class, 'infraccion_id');
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activa', true);
    }

    public static function ambitosVehiculo(): array
    {
        return [
            'general' => 'General',
            'automovil' => 'Automovil / carro',
            'motocicleta' => 'Motocicleta',
            'transporte_publico' => 'Transporte publico',
            'carga' => 'Carga',
            'no_motorizado' => 'No motorizado',
            'sustancias_peligrosas' => 'Sustancias peligrosas',
            'siniestro' => 'Siniestro / perito',
        ];
    }

    public function getReferenciaLegalCortaAttribute(): string
    {
        $partes = [];

        if ($this->textoLimpio($this->articulo) !== '') {
            $partes[] = 'Art. ' . $this->textoLimpio($this->articulo);
        }

        if ($this->textoLimpio($this->fraccion) !== '') {
            $partes[] = 'fracc. ' . $this->textoLimpio($this->fraccion);
        }

        if ($this->textoLimpio($this->inciso) !== '') {
            $partes[] = 'inciso ' . $this->incisoLegible();
        }

        return implode(', ', $partes);
    }

    public function getResumenSancionesAttribute(): string
    {
        $sanciones = [];
        $puntos = (int) $this->puntos;

        if ((bool) $this->amonestacion) {
            $sanciones[] = 'amonestacion';
        }

        if ((bool) $this->arresto_persona) {
            $sanciones[] = 'arresto de persona';
        }

        if ((bool) $this->suspension_licencia) {
            $sanciones[] = 'suspension de licencia';
        }

        if ((bool) $this->cancelacion_licencia) {
            $sanciones[] = 'cancelacion de licencia';
        }

        if ($puntos > 0) {
            $sanciones[] = '-' . $puntos . ' ' . ($puntos === 1 ? 'punto' : 'puntos');
        }

        if ((bool) $this->retencion_vehiculo) {
            $sanciones[] = 'deposito de vehiculo';
        } elseif ((bool) $this->deposito_si_sin_persona_habilitada) {
            $sanciones[] = 'deposito si no hay persona habilitada';
        }

        if ($sanciones === []) {
            $sanciones[] = 'sin descuento de puntos';
        }

        return implode(' + ', $sanciones);
    }

    public function getAmbitoVehiculoTextoAttribute(): string
    {
        $ambito = $this->textoLimpio($this->ambito_vehiculo);

        return self::ambitosVehiculo()[$ambito] ?? 'General';
    }

    public function getMultaUmaTextoAttribute(): ?string
    {
        $min = $this->multa_uma_min;
        $max = $this->multa_uma_max;

        if ($min && $max) {
            return $min === $max ? $min . ' UMAS' : $min . ' a ' . $max . ' UMAS';
        }

        if ($min) {
            return $min . ' UMAS';
        }

        if ($max) {
            return 'hasta ' . $max . ' UMAS';
        }

        return null;
    }

    public function getSancionPersonaTextoAttribute(): ?string
    {
        $sanciones = [];

        if ((bool) $this->amonestacion) {
            $sanciones[] = 'amonestacion';
        }

        if ((bool) $this->arresto_persona) {
            $sanciones[] = 'arresto de persona';
        }

        if ((bool) $this->suspension_licencia) {
            $sanciones[] = 'suspension de licencia';
        }

        if ((bool) $this->cancelacion_licencia) {
            $sanciones[] = 'cancelacion de licencia';
        }

        return $sanciones !== [] ? implode(' + ', $sanciones) : null;
    }

    public function getEtiquetaOperativaAttribute(): string
    {
        $base = $this->referencia_legal_corta ?: $this->codigo;

        return trim($base . ' - ' . $this->nombre . ' (' . $this->resumen_sanciones . ')');
    }

    private function incisoLegible(): string
    {
        $inciso = $this->textoLimpio($this->inciso);

        if ($inciso === '' || strpos($inciso, ')') !== false) {
            return $inciso;
        }

        if (strpos($inciso, ',') !== false || strpos($inciso, ' y ') !== false) {
            return $inciso;
        }

        return $inciso . ')';
    }

    private function textoLimpio($value): string
    {
        return trim((string) $value);
    }
}
