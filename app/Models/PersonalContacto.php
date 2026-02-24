<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonalContacto extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'personal_contactos';

    protected $fillable = [
        'personal_id',
        'tipo',
        'valor',
        'es_principal',
        'observaciones',
    ];

    protected $casts = [
        'personal_id' => 'integer',
        'es_principal' => 'boolean',
    ];

    public function personal()
    {
        return $this->belongsTo(\App\Models\Personal::class, 'personal_id');
    }
}
