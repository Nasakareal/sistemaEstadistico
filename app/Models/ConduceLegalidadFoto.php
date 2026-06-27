<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConduceLegalidadFoto extends Model
{
    use HasFactory;

    protected $table = 'conduce_legalidad_fotos';

    protected $fillable = [
        'captura_id',
        'foto_path',
        'foto_thumbnail_path',
        'foto_nombre_original',
        'foto_hash',
        'orden',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'orden' => 'integer',
    ];

    public function captura()
    {
        return $this->belongsTo(ConduceLegalidadCaptura::class, 'captura_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
