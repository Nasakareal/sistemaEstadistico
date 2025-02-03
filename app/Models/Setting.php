<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',       // La clave de configuración (ej: 'sitio_web')
        'value',     // El valor correspondiente (ej: 'https://mi-sitio.com')
        'description' // Una breve descripción del propósito de la configuración
    ];

    /**
     * Los atributos que deben estar ocultos al serializar (si es necesario).
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * Los atributos que se deben castear (si hay campos específicos con formatos).
     *
     * @var array<string, string>
     */
    protected $casts = [
        // Aquí puedes castear ciertos campos a tipos, por ejemplo:
        // 'is_enabled' => 'boolean',
    ];
}
