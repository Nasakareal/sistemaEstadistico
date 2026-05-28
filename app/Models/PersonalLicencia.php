<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonalLicencia extends Model
{
    use HasFactory, SoftDeletes;

    public const PERMANENT_VIGENCIA = '2099-12-31';

    protected $table = 'personal_licencias';

    protected $fillable = [
        'personal_id',
        'tipo',
        'numero',
        'vigencia',
        'permanente',
        'activo',
        'vencimiento_notificado_at',
        'observaciones',
    ];

    protected $casts = [
        'personal_id' => 'integer',
        'vigencia' => 'date',
        'permanente' => 'boolean',
        'activo' => 'boolean',
        'vencimiento_notificado_at' => 'datetime',
    ];

    public static function tipos(): array
    {
        return [
            'AUTOMOVILISTA' => 'Automovilista',
            'CHOFER' => 'Chofer',
            'MOTOCICLISTA' => 'Motociclista',
            'SERVICIO_PUBLICO' => 'Servicio público',
            'PERMISO' => 'Permiso',
            'OTRO' => 'Otro',
        ];
    }

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }

    public function getTipoLabelAttribute(): string
    {
        return self::tipos()[$this->tipo] ?? str_replace('_', ' ', (string) $this->tipo);
    }

    public function estaVencida(?CarbonInterface $fecha = null): bool
    {
        if (!$this->activo || $this->permanente || !$this->vigencia) {
            return false;
        }

        $fecha = $fecha ?: now('America/Mexico_City');

        return $this->vigencia->lt($fecha->copy()->startOfDay());
    }
}
