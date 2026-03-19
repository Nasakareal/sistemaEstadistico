<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperativoConsolidado extends Model
{
    protected $table = 'operativo_consolidados';

    protected $fillable = [
        'client_uuid',
        'operativo_id',
        'fecha',
        'unidad_org_id',
        'delegacion_id',
        'destacamento_id',
        'destacamento_nombre_snapshot',
        'asunto',
        'descripcion_general',
        'municipios_tramos',
        'total_dispositivos',
        'total_vehiculos_inspeccionados',
        'total_personas_inspeccionadas',
        'total_vehiculos_impactados',
        'total_personas_impactadas',
        'total_estado_fuerza',
        'total_kilometros_recorridos',
        'total_acompanamientos',
        'total_abanderamientos',
        'total_auxilios_viales',
        'total_prox_empresas',
        'total_prox_tiendas_conveniencia',
        'total_prox_escuelas',
        'total_prox_hospitales',
        'total_antecedentes_personas',
        'total_antecedentes_vehiculos',
        'total_antecedentes_motos',
        'total_antecedentes_camiones',
        'total_puestas_disposicion',
        'total_vehiculos_recuperados',
        'total_armas_aseguradas',
        'total_mercancia_recuperada',
        'total_decomiso_drogas',
        'crps_consolidados',
        'texto_generado',
        'json_resumen',
        'estatus',
        'cerrado_por',
        'cerrado_at',
        'compartido_whatsapp',
        'compartido_whatsapp_at',
        'sync_status',
        'sync_error',
        'synced_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha' => 'date',
        'total_kilometros_recorridos' => 'decimal:2',
        'json_resumen' => 'array',
        'compartido_whatsapp' => 'boolean',
        'cerrado_at' => 'datetime',
        'compartido_whatsapp_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    public function operativo()
    {
        return $this->belongsTo(Operativo::class, 'operativo_id');
    }

    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'unidad_org_id');
    }

    public function delegacion()
    {
        return $this->belongsTo(Delegacion::class, 'delegacion_id');
    }

    public function destacamento()
    {
        return $this->belongsTo(Destacamento::class, 'destacamento_id');
    }

    public function detalles()
    {
        return $this->hasMany(OperativoConsolidadoDetalle::class, 'operativo_consolidado_id');
    }

    public function cerrador()
    {
        return $this->belongsTo(User::class, 'cerrado_por');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
