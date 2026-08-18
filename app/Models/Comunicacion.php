<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comunicacion extends Model
{
    use HasFactory;

    protected $table = 'comunicaciones';

    protected $fillable = [
        'remitente_user_id',
        'tipo',
        'asunto',
        'contenido',
        'alcance',
        'unidad_id',
        'turno_id',
        'role_id',
        'destinatario_user_id',
        'requiere_enterado',
        'enviado_at',
    ];

    protected $casts = [
        'requiere_enterado' => 'boolean',
        'enviado_at' => 'datetime',
    ];

    public function remitente()
    {
        return $this->belongsTo(User::class, 'remitente_user_id');
    }

    public function destinatario()
    {
        return $this->belongsTo(User::class, 'destinatario_user_id');
    }

    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'unidad_id');
    }

    public function turno()
    {
        return $this->belongsTo(Turno::class, 'turno_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function destinatarios()
    {
        return $this->hasMany(ComunicacionDestinatario::class, 'comunicacion_id');
    }

    public function usuariosDestinatarios()
    {
        return $this->belongsToMany(
            User::class,
            'comunicacion_destinatarios',
            'comunicacion_id',
            'user_id'
        )
        ->withPivot([
            'leido_at',
            'enterado_at',
        ])
        ->withTimestamps();
    }

    public function adjuntos()
    {
        return $this->hasMany(
            ComunicacionAdjunto::class,
            'comunicacion_id'
        )->orderBy('orden');
    }
}
