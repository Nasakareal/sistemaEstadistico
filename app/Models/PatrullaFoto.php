<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatrullaFoto extends Model
{
    protected $table = 'patrulla_fotos';

    protected $fillable = [
        'patrulla_id',
        'foto',
    ];

    public function patrulla()
    {
        return $this->belongsTo(Patrulla::class);
    }
}
