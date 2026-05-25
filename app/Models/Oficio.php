<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Oficio extends Model
{
    use HasFactory;

    public const TIPOS = [
        'amparo' => 'Amparo',
        'memorandum' => 'Memorándum',
        'oficio' => 'Oficio',
        'circular' => 'Circular',
    ];

    public const SENTIDOS = [
        'entrada' => 'Entrada',
        'salida' => 'Salida',
    ];

    public const PREFIJOS_UNIDAD = [
        'siniestros' => 'UAS',
        'delegaciones' => 'UD',
        'seguridad-vial' => 'SV',
        'carreteras' => 'UPC',
        'vialidades-urbanas' => 'UPVU',
        'fomento-cultura-vial' => 'UFCV',
    ];

    protected $fillable = [
        'numero_oficio',
        'tipo',
        'sentido',
        'unidad_id',
        'fecha_documento',
        'remitente',
        'destinatario',
        'asunto',
        'descripcion',
        'pdf_path',
        'fotos',
        'contesta_a_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fotos' => 'array',
        'fecha_documento' => 'date',
        'unidad_id' => 'integer',
        'contesta_a_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'unidad_id');
    }

    public function contestaA()
    {
        return $this->belongsTo(self::class, 'contesta_a_id');
    }

    public function contestaciones()
    {
        return $this->hasMany(self::class, 'contesta_a_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actualizador()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeVisibleFor(Builder $query, ?User $actor): Builder
    {
        if ($actor && $actor->hasRole('Superadmin')) {
            return $query;
        }

        $unidadId = (int) ($actor->unidad_id ?? 0);

        return $unidadId > 0
            ? $query->where('unidad_id', $unidadId)
            : $query->whereRaw('1 = 0');
    }

    public function getTipoLabelAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? ucfirst((string) $this->tipo);
    }

    public function getSentidoLabelAttribute(): string
    {
        return self::SENTIDOS[$this->sentido] ?? ucfirst((string) $this->sentido);
    }

    public function getNumeroCortoAttribute(): string
    {
        $numero = (string) $this->numero_oficio;

        return strlen($numero) > 42 ? substr($numero, 0, 39) . '...' : $numero;
    }

    public static function prefijoParaUnidad(?Unidad $unidad): string
    {
        if (!$unidad) {
            return 'OF';
        }

        $slug = (string) ($unidad->slug ?? '');

        if ($slug !== '' && isset(self::PREFIJOS_UNIDAD[$slug])) {
            return self::PREFIJOS_UNIDAD[$slug];
        }

        return self::prefijoDesdeTexto($slug !== '' ? $slug : (string) $unidad->nombre);
    }

    private static function prefijoDesdeTexto(string $texto): string
    {
        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N',
        ]);

        $partes = array_values(array_filter(
            preg_split('/[^A-Za-z0-9]+/', strtoupper($texto)) ?: [],
            function ($parte) {
                return $parte !== '' && !in_array($parte, ['DE', 'DEL', 'LA', 'EL', 'Y', 'A', 'EN'], true);
            }
        ));

        if (empty($partes)) {
            return 'OF';
        }

        if (count($partes) === 1) {
            return substr($partes[0], 0, 4) ?: 'OF';
        }

        return substr(implode('', array_map(fn ($parte) => substr($parte, 0, 1), $partes)), 0, 4) ?: 'OF';
    }
}
