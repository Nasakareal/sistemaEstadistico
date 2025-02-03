<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HechoVehiculo extends Model
{
    use HasFactory;

    protected $table = 'hecho_vehiculo';

    protected $fillable = [
        'hecho_id',
        'vehiculo_id',
    ];
}
