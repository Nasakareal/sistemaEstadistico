<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperativoDispositivo extends Model
{
    protected $table = 'operativo_dispositivos';

    public const REVISION_PENDIENTE = 'pendiente';
    public const REVISION_APROBADO = 'aprobado';
    public const REVISION_RECHAZADO = 'rechazado';

    protected $fillable = [
        'client_uuid',
        'sync_status',
        'sync_error',
        'synced_at',
        'operativo_id',
        'operativo_dispositivo_catalogo_id',
        'tipo_reporte',
        'asunto',
        'fecha',
        'hora',
        'hora_inicio',
        'hora_fin',
        'unidad_org_id',
        'delegacion_id',
        'destacamento_id',
        'user_id',
        'lugar',
        'carretera',
        'tramo',
        'kilometro',
        'lat',
        'lng',
        'coordenadas_texto',
        'descripcion',
        'narrativa',
        'acciones_realizadas',
        'frase_institucional',
        'nombre_conductor',
        'ocupacion_conductor',
        'acompanantes_cantidad',
        'vehiculo_descripcion',
        'placas_apoyado',
        'procedencia',
        'destino',
        'motivo_apoyo',
        'cantidad',
        'vehiculos_inspeccionados',
        'personas_inspeccionadas',
        'vehiculos_impactados',
        'personas_impactadas',
        'estado_fuerza_participante',
        'kilometros_recorridos',
        'crps_participantes',
        'elementos_participantes_texto',
        'cargo_responsable',
        'nombre_responsable',
        'destacamento_nombre_snapshot',
        'acompanamientos',
        'abanderamientos',
        'auxilios_viales',
        'tipo_acompanamiento',
        'tipo_abanderamiento',
        'tipo_auxilio_vial',
        'folio_atendido',
        'motivo_folio',
        'prox_empresas',
        'prox_tiendas_conveniencia',
        'prox_escuelas',
        'prox_hospitales',
        'antecedentes_personas',
        'antecedentes_vehiculos',
        'antecedentes_motos',
        'antecedentes_camiones',
        'puestas_disposicion',
        'vehiculos_recuperados',
        'armas_aseguradas',
        'mercancia_recuperada',
        'decomiso_drogas',
        'requiere_evidencia',
        'compartido_whatsapp',
        'compartido_whatsapp_at',
        'observaciones',
        'created_by',
        'updated_by',
        'estado_revision',
        'revisado_por',
        'revisado_at',
        'observacion_revision',
    ];

    protected $casts = [
        'fecha' => 'date',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'kilometros_recorridos' => 'decimal:2',
        'requiere_evidencia' => 'boolean',
        'compartido_whatsapp' => 'boolean',
        'synced_at' => 'datetime',
        'compartido_whatsapp_at' => 'datetime',
        'revisado_at' => 'datetime',
    ];

    public function operativo()
    {
        return $this->belongsTo(Operativo::class, 'operativo_id');
    }

    public function catalogo()
    {
        return $this->belongsTo(OperativoDispositivoCatalogo::class, 'operativo_dispositivo_catalogo_id');
    }

    public function fotos()
    {
        return $this->hasMany(OperativoDispositivoFoto::class, 'operativo_dispositivo_id')->orderBy('orden');
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

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function detallesConsolidado()
    {
        return $this->hasMany(OperativoConsolidadoDetalle::class, 'operativo_dispositivo_id');
    }

    public function revisadoPor()
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }

    public function vehiculos()
    {
        return $this->belongsToMany(
            Vehiculo::class,
            'operativo_dispositivo_vehiculo',
            'operativo_dispositivo_id',
            'vehiculo_id'
        )->withPivot('rol', 'observaciones')->withTimestamps();
    }

    public function personas()
    {
        return $this->hasMany(OperativoDispositivoPersona::class, 'operativo_dispositivo_id');
    }

    public function scopeAprobados($query)
    {
        return $query->where('estado_revision', self::REVISION_APROBADO);
    }

    public function scopePendientesRevision($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('estado_revision')
                ->orWhere('estado_revision', self::REVISION_PENDIENTE);
        });
    }

    public function estaAprobado(): bool
    {
        return $this->estado_revision === self::REVISION_APROBADO;
    }
}
