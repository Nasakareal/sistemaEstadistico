<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperativoDispositivo extends Model
{
    protected $table = 'operativo_dispositivos';

    protected $fillable = [
        'operativo_id',
        'operativo_dispositivo_catalogo_id',
        'fecha',
        'hora',
        'unidad_org_id',
        'delegacion_id',
        'destacamento_id',
        'user_id',
        'lugar',
        'descripcion',
        'cantidad',
        'vehiculos_inspeccionados',
        'personas_inspeccionadas',
        'vehiculos_impactados',
        'personas_impactadas',
        'estado_fuerza_participante',
        'kilometros_recorridos',
        'crps_participantes',
        'acompanamientos',
        'abanderamientos',
        'auxilios_viales',
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
        'observaciones',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha' => 'date',
        'kilometros_recorridos' => 'decimal:2',
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
        return $this->hasMany(OperativoDispositivoFoto::class, 'operativo_dispositivo_id');
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
}
