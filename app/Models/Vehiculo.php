<?php

namespace App\Models;

use App\Services\Fotos\HechoFotoStorage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    use HasFactory;

    protected $table = 'vehiculos';

    protected $appends = [
        'fotos_url',
        'foto_inventario_grua_url',
    ];

    protected $fillable = [
        'client_uuid',
        'marca',
        'modelo',
        'tipo',
        'linea',
        'color',
        'placas',
        'estado_placas',
        'permiso_circular',
        'serie',
        'capacidad_personas',
        'tipo_servicio',
        'tarjeta_circulacion_nombre',
        'grua',
        'grua_id',
        'numero_inventario_grua',
        'foto_inventario_grua',
        'fecha_inventario_grua',
        'corralon',
        'monto_danos',
        'partes_danadas',
        'fotos',
        'aseguradora',
        'antecedente_vehiculo',
        'reporte_robo',
    ];

    protected $casts = [
        'reporte_robo' => 'boolean',
    ];

    public function getFotosUrlAttribute(): ?string
    {
        return app(HechoFotoStorage::class)->url(
            $this->attributes['fotos'] ?? null
        );
    }

    public function getFotoInventarioGruaUrlAttribute(): ?string
    {
        return app(HechoFotoStorage::class)->url(
            $this->attributes['foto_inventario_grua'] ?? null
        );
    }

    public function hechos()
    {
        return $this->belongsToMany(Hechos::class, 'hecho_vehiculo', 'vehiculo_id', 'hecho_id')->withTimestamps();
    }

    public function conductores()
    {
        return $this->belongsToMany(Conductor::class, 'vehiculo_conductor', 'vehiculo_id', 'conductor_id')->withTimestamps();
    }

    public function servicios()
    {
        return $this->hasMany(Servicio::class, 'vehiculo_id');
    }

    public function servicio()
    {
        return $this->hasOne(Servicio::class, 'vehiculo_id')->latest();
    }

    public function actividades()
    {
        return $this->belongsToMany(\App\Models\Actividad::class, 'actividad_vehiculo', 'vehiculo_id', 'actividad_id')->withTimestamps();
    }

    public function operativoDispositivos()
    {
        return $this->belongsToMany(
            OperativoDispositivo::class,
            'operativo_dispositivo_vehiculo',
            'vehiculo_id',
            'operativo_dispositivo_id'
        )->withPivot('rol', 'observaciones')->withTimestamps();
    }

    public function puestasDisposicionVehiculos()
    {
        return $this->hasMany(PuestaDisposicionVehiculo::class, 'vehiculo_id');
    }

    public function gruaAsignada()
    {
        return $this->belongsTo(Grua::class, 'grua_id');
    }

    public function liberacionCorralon()
    {
        return $this->hasOne(LiberacionCorralon::class, 'vehiculo_id')->latest();
    }

    public function tieneCorralonValido(): bool
    {
        return self::corralonEsValido($this->corralon);
    }

    public function nombreCorralonValido(): ?string
    {
        $corralon = self::normalizarCorralonValor($this->corralon);

        return self::corralonEsValido($corralon) ? $corralon : null;
    }

    public static function corralonEsValido($value): bool
    {
        $corralon = self::normalizarCorralonValor($value);

        if ($corralon === null) {
            return false;
        }

        $normalizado = mb_strtoupper($corralon, 'UTF-8');
        $normalizado = preg_replace('/\s+/', ' ', $normalizado) ?: '';

        return !in_array($normalizado, self::corralonValoresInvalidos(), true);
    }

    public static function corralonValoresInvalidos(): array
    {
        return [
            'N/A',
            'NA',
            'NO',
            'NO APLICA',
            'NO APLICA.',
            'NINGUNO',
            'NULL',
            'SIN CORRALON',
            'SIN CORRALÓN',
            'NO TIENE CORRALON',
            'NO TIENE CORRALÓN',
            '-',
        ];
    }

    private static function normalizarCorralonValor($value): ?string
    {
        if (is_object($value)) {
            $value = $value->nombre ?? ($value->id ?? null);
        }

        if ($value === null) {
            return null;
        }

        $corralon = trim((string) $value);

        return $corralon !== '' ? $corralon : null;
    }
}
