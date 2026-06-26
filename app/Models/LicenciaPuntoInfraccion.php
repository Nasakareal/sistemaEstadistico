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
        'puntos',
        'multa_uma_min',
        'multa_uma_max',
        'retencion_vehiculo',
        'descripcion',
        'fundamento_legal',
        'activa',
    ];

    protected $casts = [
        'puntos' => 'integer',
        'multa_uma_min' => 'integer',
        'multa_uma_max' => 'integer',
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

        if ($puntos > 0) {
            $sanciones[] = '-' . $puntos . ' ' . ($puntos === 1 ? 'punto' : 'puntos');
        }

        if ((bool) $this->retencion_vehiculo) {
            $sanciones[] = 'retiro de vehiculo';
        }

        if ($sanciones === []) {
            $sanciones[] = 'sin descuento de puntos';
        }

        return implode(' + ', $sanciones);
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
