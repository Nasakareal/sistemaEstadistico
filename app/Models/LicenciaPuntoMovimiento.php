<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LicenciaPuntoMovimiento extends Model
{
    protected $table = 'licencia_punto_movimientos';

    protected $fillable = [
        'cuenta_id',
        'infraccion_id',
        'hecho_id',
        'user_id',
        'tipo',
        'puntos',
        'saldo_anterior',
        'saldo_nuevo',
        'fecha_movimiento',
        'referencia',
        'descripcion',
        'metadata',
    ];

    protected $casts = [
        'puntos' => 'integer',
        'saldo_anterior' => 'integer',
        'saldo_nuevo' => 'integer',
        'fecha_movimiento' => 'datetime',
        'metadata' => 'array',
    ];

    public function cuenta()
    {
        return $this->belongsTo(LicenciaPuntoCuenta::class, 'cuenta_id');
    }

    public function infraccion()
    {
        return $this->belongsTo(LicenciaPuntoInfraccion::class, 'infraccion_id');
    }

    public function hecho()
    {
        return $this->belongsTo(Hechos::class, 'hecho_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
