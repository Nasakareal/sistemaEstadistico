<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Operativo extends Model
{
    use HasFactory;

    protected $table = 'operativos';

    protected $fillable = [
        'fecha',
        'operativo_catalogo_id',
        'unidad_org_id',
        'delegacion_id',
        'lugar',
        'descripcion',
        'dispositivos_realizados',
        'vehiculos_inspeccionados',
        'personas_inspeccionadas',
        'vehiculos_impactados',
        'personas_impactadas',
        'antecedentes_personas',
        'antecedentes_vehiculos',
        'antecedentes_motos',
        'antecedentes_camiones',
        'estado_fuerza_participante',
        'kilometros_recorridos',
        'acompanamientos',
        'abanderamientos',
        'auxilios_viales',
        'puestas_disposicion',
        'vehiculos_recuperados',
        'armas_aseguradas',
        'mercancia_recuperada',
        'decomiso_drogas',
        'crps_participantes',
        'observaciones',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha' => 'date',
        'dispositivos_realizados' => 'integer',
        'vehiculos_inspeccionados' => 'integer',
        'personas_inspeccionadas' => 'integer',
        'vehiculos_impactados' => 'integer',
        'personas_impactadas' => 'integer',
        'antecedentes_personas' => 'integer',
        'antecedentes_vehiculos' => 'integer',
        'antecedentes_motos' => 'integer',
        'antecedentes_camiones' => 'integer',
        'estado_fuerza_participante' => 'integer',
        'kilometros_recorridos' => 'decimal:2',
        'acompanamientos' => 'integer',
        'abanderamientos' => 'integer',
        'auxilios_viales' => 'integer',
        'puestas_disposicion' => 'integer',
        'vehiculos_recuperados' => 'integer',
        'armas_aseguradas' => 'integer',
        'mercancia_recuperada' => 'integer',
        'decomiso_drogas' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    public function catalogo()
    {
        return $this->belongsTo(OperativoCatalogo::class, 'operativo_catalogo_id');
    }

    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'unidad_org_id');
    }

    public function delegacion()
    {
        return $this->belongsTo(Delegacion::class, 'delegacion_id');
    }

    public function fotos()
    {
        return $this->hasMany(OperativoFoto::class, 'operativo_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function destacamento()
    {
        return $this->belongsTo(\App\Models\Destacamento::class, 'destacamento_id');
    }
}
