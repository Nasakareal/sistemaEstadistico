<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonalDomicilio extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'personal_domicilios';

    protected $fillable = [
        'personal_id',
        'calle',
        'numero_ext',
        'numero_int',
        'colonia',
        'municipio',
        'estado',
        'cp',
        'referencias',
        'es_actual',
    ];

    protected $casts = [
        'personal_id' => 'integer',
        'es_actual' => 'boolean',
    ];

    public function personal()
    {
        return $this->belongsTo(\App\Models\Personal::class, 'personal_id');
    }
}
