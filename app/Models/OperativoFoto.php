<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperativoFoto extends Model
{
    use HasFactory;

    protected $table = 'operativo_fotos';

    protected $fillable = [
        'operativo_id',
        'foto_path',
        'foto_nombre_original',
        'foto_hash',
        'created_by',
    ];

    protected $casts = [
        'operativo_id' => 'integer',
        'created_by' => 'integer',
    ];

    public function operativo()
    {
        return $this->belongsTo(Operativo::class, 'operativo_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
