<?php

namespace App\Services;

use App\Models\Hechos;
use App\Models\PuestaDisposicion;

class IphPuestaDisposicionService
{
    private $croquisPreviewService;

    public function __construct(CroquisPreviewService $croquisPreviewService)
    {
        $this->croquisPreviewService = $croquisPreviewService;
    }

    public function puedeGenerar($usuario): bool
    {
        return $usuario
            && (
                $usuario->hasRole('Superadmin')
                || (int) ($usuario->unidad_id ?? 0) === 2
            );
    }

    public function mapearDesdeHecho(Hechos $hecho): array
    {
        $hecho->loadMissing([
            'creator',
            'unidadOrganizacional',
            'delegacion',
            'croquis',
            'vehiculos.conductores',
            'vehiculos.gruaAsignada',
            'lesionados',
            'puestaDisposicion.personas',
            'puestaDisposicion.vehiculos',
            'puestaDisposicion.objetos',
            'puestaDisposicion.unidad',
            'puestaDisposicion.delegacion',
            'puestaDisposicion.destacamento',
            'puestaDisposicion.creador',
        ]);

        $puesta = $hecho->puestaDisposicion;
        $croquisPreview = $hecho->croquis
            ? $this->croquisPreviewService->ensure($hecho->croquis, $hecho)
            : null;

        return [
            'hecho' => $this->mapearHecho($hecho),
            'puesta_disposicion' => $puesta ? $this->mapearPuesta($puesta) : null,
            'vehiculos_hecho' => $this->mapearVehiculosHecho($hecho),
            'conductores_hecho' => $this->mapearConductoresHecho($hecho),
            'lesionados_hecho' => $this->mapearLesionadosHecho($hecho),
            'personas' => $puesta ? $this->mapearPersonas($puesta) : [],
            'vehiculos' => $puesta ? $this->mapearVehiculos($puesta) : [],
            'objetos' => $puesta ? $this->mapearObjetos($puesta) : [],
            'anexos' => [
                'foto_lugar' => $hecho->foto_lugar,
                'foto_lugar_2' => $hecho->foto_lugar_2,
                'foto_situacion' => $hecho->foto_situacion,
                'iph_delegaciones_path' => $hecho->iph_delegaciones_path,
                'archivo_puesta' => $puesta ? $puesta->archivo_puesta : null,
                'croquis_preview' => $croquisPreview,
            ],
        ];
    }

    private function mapearHecho(Hechos $hecho): array
    {
        $fallecidos = $hecho->lesionados
            ->filter(fn ($lesionado) => mb_strtoupper(trim((string) $lesionado->tipo_lesion), 'UTF-8') === 'FALLECIDO')
            ->count();
        $lesionados = $hecho->lesionados->count() - $fallecidos;

        return [
            'id' => $hecho->id,
            'folio_c5i' => $hecho->folio_c5i,
            'fecha' => $this->fecha($hecho->fecha),
            'hora' => $this->hora($hecho->hora),
            'situacion' => $hecho->situacion,
            'perito' => $hecho->perito,
            'creador_nombre' => $hecho->creator ? $hecho->creator->name : null,
            'unidad_numero_economico' => $hecho->unidad,
            'unidad_org_id' => $hecho->unidad_org_id,
            'unidad_org_nombre' => $hecho->unidadOrganizacional ? $hecho->unidadOrganizacional->nombre : null,
            'delegacion_id' => $hecho->delegacion_id,
            'delegacion_nombre' => $hecho->delegacion ? $hecho->delegacion->nombre : null,
            'oficio_mp' => $hecho->oficio_mp,
            'vehiculos_mp' => (int) ($hecho->vehiculos_mp ?? 0),
            'personas_mp' => (int) ($hecho->personas_mp ?? 0),
            'lesionados_count' => $lesionados,
            'fallecidos_count' => $fallecidos,
            'tipo_hecho' => $hecho->tipo_hecho,
            'tiempo' => $hecho->tiempo,
            'clima' => $hecho->clima,
            'condiciones' => $hecho->condiciones,
            'causas' => $hecho->causas,
            'colision_camino' => $hecho->colision_camino,
            'ubicacion' => [
                'calle' => $hecho->calle,
                'colonia' => $hecho->colonia,
                'entre_calles' => $hecho->entre_calles,
                'municipio' => $hecho->municipio,
                'codigo_postal' => $hecho->codigo_postal,
                'lat' => $hecho->lat,
                'lng' => $hecho->lng,
                'ubicacion_formateada' => $hecho->ubicacion_formateada,
                'place_id' => $hecho->place_id,
            ],
        ];
    }

    private function mapearVehiculosHecho(Hechos $hecho): array
    {
        return $hecho->vehiculos->map(function ($vehiculo) {
            return [
                'id' => $vehiculo->id,
                'tipo' => $vehiculo->tipo,
                'marca' => $vehiculo->marca,
                'linea' => $vehiculo->linea,
                'modelo' => $vehiculo->modelo,
                'color' => $vehiculo->color,
                'placas' => $vehiculo->placas,
                'estado_placas' => $vehiculo->estado_placas,
                'serie' => $vehiculo->serie,
                'capacidad_personas' => $vehiculo->capacidad_personas,
                'tipo_servicio' => $vehiculo->tipo_servicio,
                'tarjeta_circulacion_nombre' => $vehiculo->tarjeta_circulacion_nombre,
                'foto' => $vehiculo->fotos,
                'grua' => $vehiculo->grua,
                'grua_id' => $vehiculo->grua_id,
                'grua_nombre' => $vehiculo->gruaAsignada ? $vehiculo->gruaAsignada->nombre : null,
                'grua_direccion' => $vehiculo->gruaAsignada ? $vehiculo->gruaAsignada->direccion : null,
                'grua_ubicacion_corralon' => $vehiculo->gruaAsignada ? $vehiculo->gruaAsignada->ubicacion_corralon : null,
                'corralon' => $vehiculo->corralon,
                'monto_danos' => $vehiculo->monto_danos,
                'partes_danadas' => $vehiculo->partes_danadas,
                'aseguradora' => $vehiculo->aseguradora,
                'antecedente_vehiculo' => (bool) $vehiculo->antecedente_vehiculo,
                'conductores' => $vehiculo->conductores->map(function ($conductor) {
                    return [
                        'nombre' => $conductor->nombre,
                        'edad' => $conductor->edad,
                        'sexo' => $conductor->sexo,
                        'domicilio' => $conductor->domicilio,
                        'ocupacion' => $conductor->ocupacion,
                        'numero_licencia' => $conductor->numero_licencia,
                        'tipo_licencia' => $conductor->tipo_licencia,
                        'estado_licencia' => $conductor->estado_licencia,
                        'vigencia_licencia' => $this->fecha($conductor->vigencia_licencia),
                    ];
                })->values()->all(),
            ];
        })->values()->all();
    }

    private function mapearLesionadosHecho(Hechos $hecho): array
    {
        return $hecho->lesionados->map(function ($lesionado) {
            return [
                'nombre' => $lesionado->nombre,
                'edad' => $lesionado->edad,
                'sexo' => $lesionado->sexo,
                'tipo_lesion' => $lesionado->tipo_lesion,
                'tipo_victima' => $lesionado->tipo_victima,
                'hospitalizado' => (bool) $lesionado->hospitalizado,
                'hospital' => $lesionado->hospital,
                'atencion_en_sitio' => (bool) $lesionado->atencion_en_sitio,
                'ambulancia' => $lesionado->ambulancia,
                'paramedico' => $lesionado->paramedico,
                'observaciones' => $lesionado->observaciones,
            ];
        })->values()->all();
    }

    private function mapearConductoresHecho(Hechos $hecho): array
    {
        return $hecho->vehiculos
            ->flatMap(function ($vehiculo) {
                return $vehiculo->conductores->map(function ($conductor) use ($vehiculo) {
                    return [
                        'vehiculo_id' => $vehiculo->id,
                        'vehiculo_label' => trim(collect([
                            $vehiculo->marca,
                            $vehiculo->linea,
                            $vehiculo->placas ? 'PLACAS ' . $vehiculo->placas : null,
                            $vehiculo->serie ? 'SERIE ' . $vehiculo->serie : null,
                        ])->filter()->implode(' / ')),
                        'nombre' => $conductor->nombre,
                        'edad' => $conductor->edad,
                        'sexo' => $conductor->sexo,
                        'domicilio' => $conductor->domicilio,
                        'ocupacion' => $conductor->ocupacion,
                        'numero_licencia' => $conductor->numero_licencia,
                        'tipo_licencia' => $conductor->tipo_licencia,
                        'estado_licencia' => $conductor->estado_licencia,
                        'vigencia_licencia' => $this->fecha($conductor->vigencia_licencia),
                        'antecedentes' => (bool) $conductor->antecedentes,
                        'certificado_lesiones' => $conductor->certificado_lesiones,
                        'certificado_alcoholemia' => $conductor->certificado_alcoholemia,
                        'aliento_etilico' => $conductor->aliento_etilico,
                    ];
                });
            })
            ->values()
            ->all();
    }

    private function mapearPuesta(PuestaDisposicion $puesta): array
    {
        return [
            'id' => $puesta->id,
            'folio' => trim((string) $puesta->numero_puesta) . '/' . trim((string) $puesta->anio),
            'numero_puesta' => $puesta->numero_puesta,
            'anio' => $puesta->anio,
            'tipo_puesta' => $puesta->tipo_puesta,
            'motivo' => $puesta->motivo,
            'estatus' => $puesta->estatus,
            'nombre_policia' => $puesta->nombre_policia,
            'nombre_mp' => $puesta->nombre_mp,
            'autoridad_receptora' => $puesta->autoridad_receptora,
            'area' => $puesta->area,
            'carpeta_investigacion' => $puesta->carpeta_investigacion,
            'oficio' => $puesta->oficio,
            'fecha_puesta' => $this->fecha($puesta->fecha_puesta),
            'hora_puesta' => $this->hora($puesta->hora_puesta),
            'lugar_puesta' => $puesta->lugar_puesta,
            'narrativa' => $puesta->narrativa,
            'observaciones' => $puesta->observaciones,
            'unidad_id' => $puesta->unidad_id,
            'unidad_nombre' => $puesta->unidad ? $puesta->unidad->nombre : null,
            'delegacion_id' => $puesta->delegacion_id,
            'delegacion_nombre' => $puesta->delegacion ? $puesta->delegacion->nombre : null,
            'destacamento_id' => $puesta->destacamento_id,
            'destacamento_nombre' => $puesta->destacamento ? $puesta->destacamento->nombre : null,
        ];
    }

    private function mapearPersonas(PuestaDisposicion $puesta): array
    {
        return $puesta->personas->map(function ($persona) {
            return [
                'nombre_completo' => $persona->nombre_completo,
                'alias' => $persona->alias,
                'edad' => $persona->edad,
                'sexo' => $persona->sexo,
                'fecha_nacimiento' => $this->fecha($persona->fecha_nacimiento),
                'curp' => $persona->curp,
                'rfc' => $persona->rfc,
                'domicilio' => $persona->domicilio,
                'calidad' => $persona->calidad,
                'delito_o_motivo' => $persona->delito_o_motivo,
                'orden_aprehension' => (bool) $persona->orden_aprehension,
                'mandamiento_judicial' => $persona->mandamiento_judicial,
                'observaciones' => $persona->observaciones,
            ];
        })->values()->all();
    }

    private function mapearVehiculos(PuestaDisposicion $puesta): array
    {
        return $puesta->vehiculos->map(function ($vehiculo) {
            return [
                'vehiculo_id' => $vehiculo->vehiculo_id,
                'tipo' => $vehiculo->tipo,
                'marca' => $vehiculo->marca,
                'submarca' => $vehiculo->submarca,
                'modelo' => $vehiculo->modelo,
                'color' => $vehiculo->color,
                'placas' => $vehiculo->placas,
                'serie' => $vehiculo->serie,
                'calidad' => $vehiculo->calidad,
                'motivo_relacion' => $vehiculo->motivo_relacion,
                'con_reporte_robo' => (bool) $vehiculo->con_reporte_robo,
                'numero_reporte_robo' => $vehiculo->numero_reporte_robo,
                'observaciones' => $vehiculo->observaciones,
            ];
        })->values()->all();
    }

    private function mapearObjetos(PuestaDisposicion $puesta): array
    {
        return $puesta->objetos->map(function ($objeto) {
            return [
                'tipo_objeto' => $objeto->tipo_objeto,
                'descripcion' => $objeto->descripcion,
                'cantidad' => $objeto->cantidad,
                'unidad_medida' => $objeto->unidad_medida,
                'cadena_custodia' => $objeto->cadena_custodia,
                'observaciones' => $objeto->observaciones,
            ];
        })->values()->all();
    }

    private function fecha($valor): ?string
    {
        if (empty($valor)) {
            return null;
        }

        return is_object($valor) && method_exists($valor, 'format')
            ? $valor->format('Y-m-d')
            : substr((string) $valor, 0, 10);
    }

    private function hora($valor): ?string
    {
        if (empty($valor)) {
            return null;
        }

        return is_object($valor) && method_exists($valor, 'format')
            ? $valor->format('H:i')
            : substr((string) $valor, 0, 5);
    }
}
