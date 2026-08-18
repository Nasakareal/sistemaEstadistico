<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComunicacionDestinatario extends Model
{
    use HasFactory;

    protected $table = 'comunicacion_destinatarios';

    protected $fillable = [
        'comunicacion_id',
        'user_id',
        'leido_at',
        'enterado_at',
    ];

    protected $casts = [
        'leido_at' => 'datetime',
        'enterado_at' => 'datetime',
    ];

    public function comunicacion()
    {
        return $this->belongsTo(Comunicacion::class, 'comunicacion_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function estaLeido()
    {
        return !is_null($this->leido_at);
    }

    public function estaEnterado()
    {
        return !is_null($this->enterado_at);
    }
}
