<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LicenciaPuntoAlerta extends Model
{
    protected $table = 'licencia_punto_alertas';

    protected $fillable = [
        'cuenta_id',
        'movimiento_id',
        'tipo',
        'nivel',
        'saldo_disparador',
        'mensaje',
        'atendida_at',
        'created_by',
    ];

    protected $casts = [
        'saldo_disparador' => 'integer',
        'atendida_at' => 'datetime',
    ];

    public function cuenta()
    {
        return $this->belongsTo(LicenciaPuntoCuenta::class, 'cuenta_id');
    }

    public function movimiento()
    {
        return $this->belongsTo(LicenciaPuntoMovimiento::class, 'movimiento_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
