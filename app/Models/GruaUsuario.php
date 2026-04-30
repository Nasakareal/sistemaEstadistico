<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GruaUsuario extends Model
{
    use HasFactory;

    protected $table = 'grua_usuarios';

    protected $fillable = [
        'grua_id',
        'nombre',
        'telefono',
        'email',
        'password',
        'activo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function grua()
    {
        return $this->belongsTo(Grua::class, 'grua_id');
    }

    public function liberacionesCorralon()
    {
        return $this->hasMany(LiberacionCorralon::class, 'grua_usuario_id');
    }
}
