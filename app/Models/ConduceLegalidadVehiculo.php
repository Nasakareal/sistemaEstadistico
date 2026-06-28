<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConduceLegalidadVehiculo extends Model
{
    use HasFactory;

    protected $table = 'conduce_legalidad_vehiculos';

    protected $fillable = [
        'captura_id',
        'marca',
        'modelo',
        'tipo_general',
        'tipo',
        'linea',
        'color',
        'placas',
        'estado_placas',
        'serie',
        'capacidad_personas',
        'tipo_servicio',
        'tarjeta_circulacion_nombre',
        'grua_id',
        'corralon_id',
        'grua',
        'corralon',
        'servicio_unidad_id',
        'servicio_delegacion_id',
        'servicio_created_by',
        'aseguradora',
        'monto_danos',
        'partes_danadas',
        'antecedente_vehiculo',
        'raw_tarjeta_qr',
        'licencia_punto_infraccion_id',
        'infraccion_codigo',
        'fundamento_legal',
        'retencion_vehiculo',
        'motivo_retencion',
        'persona_habilitada_resguardo',
        'observaciones',
    ];

    protected $casts = [
        'capacidad_personas' => 'integer',
        'monto_danos' => 'decimal:2',
        'antecedente_vehiculo' => 'boolean',
        'retencion_vehiculo' => 'boolean',
        'persona_habilitada_resguardo' => 'boolean',
        'servicio_unidad_id' => 'integer',
        'servicio_delegacion_id' => 'integer',
        'servicio_created_by' => 'integer',
    ];

    public function captura()
    {
        return $this->belongsTo(ConduceLegalidadCaptura::class, 'captura_id');
    }

    public function infraccion()
    {
        return $this->belongsTo(LicenciaPuntoInfraccion::class, 'licencia_punto_infraccion_id');
    }

    public function gruaRelacion()
    {
        return $this->belongsTo(Grua::class, 'grua_id');
    }

    public function corralonRelacion()
    {
        return $this->belongsTo(Grua::class, 'corralon_id');
    }

    public function servicioUnidad()
    {
        return $this->belongsTo(Unidad::class, 'servicio_unidad_id');
    }

    public function servicioDelegacion()
    {
        return $this->belongsTo(Delegacion::class, 'servicio_delegacion_id');
    }

    public function servicioUsuario()
    {
        return $this->belongsTo(User::class, 'servicio_created_by');
    }
}
