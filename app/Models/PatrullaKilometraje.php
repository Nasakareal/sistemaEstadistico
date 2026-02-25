<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatrullaKilometraje extends Model
{
    use HasFactory;

    protected $table = 'patrulla_kilometrajes';

    protected $fillable = [
        'patrulla_id',
        'fecha',
        'kilometraje_reportado',
        'kilometros_recorridos',
        'usuario_id',
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
        'kilometraje_reportado' => 'integer',
        'kilometros_recorridos' => 'integer',
        'usuario_id' => 'integer',
        'patrulla_id' => 'integer',
    ];

    public function patrulla()
    {
        return $this->belongsTo(Patrulla::class, 'patrulla_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
