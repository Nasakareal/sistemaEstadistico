<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonalEmergencia extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'personal_emergencias';

    protected $fillable = [
        'personal_id',
        'nombre',
        'parentesco',
        'telefono',
        'telefono_2',
        'direccion',
        'observaciones',
    ];

    protected $casts = [
        'personal_id' => 'integer',
    ];

    public function personal()
    {
        return $this->belongsTo(\App\Models\Personal::class, 'personal_id');
    }
}
