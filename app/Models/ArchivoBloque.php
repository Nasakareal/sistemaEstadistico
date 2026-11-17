<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchivoBloque extends Model
{
    protected $table = 'archivo_bloques';

    protected $fillable = [
        'anaquel_id',
        'codigo',
        'fila',
        'columna',
        'capacidad_libros',
        'orden_apilado',
        'qr_uuid',
        'activo',
        'observaciones',
    ];

    protected $casts = [
        'fila' => 'integer',
        'columna' => 'integer',
        'capacidad_libros' => 'integer',
        'activo' => 'boolean',
    ];

    protected $appends = [
        'ocupacion',
        'espacios_disponibles',
    ];

    public function anaquel()
    {
        return $this->belongsTo(ArchivoAnaquel::class, 'anaquel_id');
    }

    public function libros()
    {
        return $this->hasMany(ArchivoLibro::class, 'bloque_actual_id');
    }

    public function librosAsignados()
    {
        return $this->hasMany(ArchivoLibro::class, 'bloque_destino_id');
    }

    public function getOcupacionAttribute()
    {
        return $this->libros()->count();
    }

    public function getEspaciosDisponiblesAttribute()
    {
        return max(0, $this->capacidad_libros - $this->ocupacion);
    }
}
