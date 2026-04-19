<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalFoto extends Model
{
    use HasFactory;

    protected $table = 'personal_fotos';

    protected $fillable = [
        'personal_id',
        'ruta',
        'nombre_original',
        'mime_type',
        'tamano',
    ];

    protected $casts = [
        'personal_id' => 'integer',
        'tamano' => 'integer',
    ];

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }
}
