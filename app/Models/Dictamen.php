<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Dictamen extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero_dictamen',
        'anio',
        'nombre_policia',
        'nombre_mp',
        'area',
        'archivo_dictamen',
        'created_by',
        'updated_by',
    ];

    protected $table = 'dictamens';

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function hecho(): BelongsTo
    {
        return $this->belongsTo(Hechos::class, 'hecho_id');
    }
}
