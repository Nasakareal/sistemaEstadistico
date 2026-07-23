<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BoquillaDotacion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'boquilla_dotaciones';

    protected $fillable = [
        'fecha_recepcion',
        'cantidad',
        'observaciones',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha_recepcion' => 'date',
        'cantidad' => 'integer',
    ];

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actualizador()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
