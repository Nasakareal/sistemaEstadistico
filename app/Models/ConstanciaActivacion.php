<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConstanciaActivacion extends Model
{
    protected $table = 'constancia_activaciones';

    protected $fillable = [
        'constancia_id',
        'user_id',
        'accion',
        'fecha',
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    public function constancia()
    {
        return $this->belongsTo(ConstanciaManejo::class, 'constancia_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
