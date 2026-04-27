<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DelegacionesHechosPruebaSeeder extends Seeder
{
    public function run(): void
    {
        $fecha = '2026-04-23';

        $delegaciones = DB::table('delegaciones')
            ->where('activa', 1)
            ->orderBy('id')
            ->get();

        if ($delegaciones->isEmpty()) {
            return;
        }

        $tiposHecho = [
            'VOLCADURA',
            'SALIDA DE SUPERFICIE DE RODAMIENTO',
            'SUBIDA AL CAMELLÓN',
            'CAIDA DE MOTOCICLETA',
            'COLISIÓN CON PEATÓN',
            'COLISIÓN POR ALCANCE',
            'COLISIÓN POR NO RESPETAR SEMÁFORO',
            'COLISIÓN POR INVASIÓN DE CARRIL',
            'COLISIÓN POR CAMBIO DE CARRIL',
            'COLISIÓN POR CORTE DE CIRCULACIÓN',
            'COLISIÓN POR MANIOBRA DE REVERSA',
            'COLISIÓN CONTRA OBJETO FIJO',
            'CAIDA ACUATICA DE VEHÍCULO',
            'DESBARRANCAMIENTO',
            'INCENDIO',
            'EXPLOSIÓN',
            'Otro',
        ];

        $situaciones = ['RESUELTO', 'PENDIENTE', 'TURNADO', 'REPORTE'];

        $vehiculosBase = [
            ['marca' => 'NISSAN', 'modelo' => '2022', 'tipo' => 'SEDAN', 'linea' => 'VERSA', 'tipo_servicio' => 'PARTICULAR', 'monto_danos' => 2500],
            ['marca' => 'CHEVROLET', 'modelo' => '2021', 'tipo' => 'SUV', 'linea' => 'TRACKER', 'tipo_servicio' => 'PARTICULAR', 'monto_danos' => 3200],
            ['marca' => 'FORD', 'modelo' => '2020', 'tipo' => 'PICK-UP', 'linea' => 'RANGER', 'tipo_servicio' => 'PARTICULAR', 'monto_danos' => 4500],
            ['marca' => 'KIA', 'modelo' => '2023', 'tipo' => 'HATCHBACK', 'linea' => 'RIO', 'tipo_servicio' => 'PARTICULAR', 'monto_danos' => 1900],
            ['marca' => 'ITALIKA', 'modelo' => '2022', 'tipo' => 'MOTOCICLETA', 'linea' => 'FT150', 'tipo_servicio' => 'PARTICULAR', 'monto_danos' => 900],
            ['marca' => 'HONDA', 'modelo' => '2021', 'tipo' => 'MOTOCICLETA', 'linea' => 'CB190', 'tipo_servicio' => 'PARTICULAR', 'monto_danos' => 1200],
            ['marca' => 'INTERNATIONAL', 'modelo' => '2018', 'tipo' => 'CAJA SECA', 'linea' => '4300', 'tipo_servicio' => 'PÚBLICO', 'monto_danos' => 6000],
            ['marca' => 'FREIGHTLINER', 'modelo' => '2019', 'tipo' => 'TRACTOCAMION', 'linea' => 'CASCADIA', 'tipo_servicio' => 'SERV. PUB. FED.', 'monto_danos' => 8000],
        ];

        $conductoresBase = [
            ['nombre' => 'JUAN CARLOS MARTINEZ LOPEZ', 'edad' => 34, 'sexo' => 'MASCULINO'],
            ['nombre' => 'MARIA FERNANDA GARCIA PEREZ', 'edad' => 29, 'sexo' => 'FEMENINO'],
            ['nombre' => 'PEDRO RAMIREZ SANCHEZ', 'edad' => 45, 'sexo' => 'MASCULINO'],
            ['nombre' => 'ANA LAURA HERNANDEZ TORRES', 'edad' => 17, 'sexo' => 'FEMENINO'],
            ['nombre' => 'JOSE MIGUEL FLORES VARGAS', 'edad' => 52, 'sexo' => 'MASCULINO'],
            ['nombre' => 'LUIS ANGEL MORALES CRUZ', 'edad' => 16, 'sexo' => 'MASCULINO'],
        ];

        DB::transaction(function () use ($fecha, $delegaciones, $tiposHecho, $situaciones, $vehiculosBase, $conductoresBase) {
            foreach ($delegaciones as $index => $delegacion) {
                for ($i = 0; $i < 3; $i++) {
                    $hora = Carbon::create(2026, 4, 23, rand(7, 21), rand(0, 59), rand(0, 59), 'America/Mexico_City');
                    $tipoHecho = $tiposHecho[($index + $i) % count($tiposHecho)];
                    $situacion = $situaciones[($index + $i) % count($situaciones)];

                    $hechoId = DB::table('hechos')->insertGetId([
                        'folio_c5i' => 'DEL-' . $delegacion->id . '-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                        'perito' => 'SEEDER DELEGACIONES',
                        'unidad' => 'D-' . $delegacion->id,
                        'unidad_org_id' => 2,
                        'hora' => $hora->format('H:i:s'),
                        'fecha' => $fecha,
                        'sector' => mb_strtoupper($delegacion->nombre),
                        'calle' => 'VIALIDAD PRINCIPAL',
                        'calle_norm' => 'VIALIDAD PRINCIPAL',
                        'colonia' => 'CENTRO',
                        'entre_calles' => 'CALLE UNO Y CALLE DOS',
                        'municipio' => mb_strtoupper($delegacion->municipio ?: $delegacion->nombre),
                        'delegacion_id' => $delegacion->id,
                        'tipo_hecho' => $tipoHecho,
                        'superficie_via' => 'ASFALTO',
                        'tiempo' => 'Día',
                        'clima' => 'Bueno',
                        'condiciones' => 'Bueno',
                        'control_transito' => 'NINGUNO',
                        'checaron_antecedentes' => 1,
                        'causas' => 'FALTA DE PRECAUCIÓN',
                        'colision_camino' => 'VEHÍCULO EN TRÁNSITO',
                        'danos_patrimoniales' => 'DAÑOS MATERIALES',
                        'propiedades_afectadas' => 'NO APLICA',
                        'monto_danos_patrimoniales' => rand(0, 1) ? rand(1000, 12000) : 0,
                        'oficio_mp' => $situacion === 'TURNADO' ? 'OF-' . rand(100, 999) . '/2026' : null,
                        'vehiculos_mp' => $situacion === 'TURNADO' ? rand(1, 2) : 0,
                        'personas_mp' => $situacion === 'TURNADO' ? rand(0, 2) : 0,
                        'vehiculos_esperados' => 0,
                        'conductores_esperados' => 0,
                        'lesionados_esperados' => 0,
                        'vehiculos_capturados' => 0,
                        'conductores_capturados' => 0,
                        'lesionados_capturados' => 0,
                        'captura_completa' => 0,
                        'created_by' => null,
                        'updated_by' => null,
                        'situacion' => $situacion,
                        'es_relevante' => 0,
                        'estado_revision' => 'aprobado',
                        'created_at' => $hora->format('Y-m-d H:i:s'),
                        'updated_at' => $hora->format('Y-m-d H:i:s'),
                    ]);

                    $cantidadVehiculos = $i === 0 ? 1 : 2;

                    for ($v = 0; $v < $cantidadVehiculos; $v++) {
                        $vehiculoData = $vehiculosBase[($index + $i + $v) % count($vehiculosBase)];

                        $vehiculoId = DB::table('vehiculos')->insertGetId([
                            'marca' => $vehiculoData['marca'],
                            'modelo' => $vehiculoData['modelo'],
                            'tipo' => $vehiculoData['tipo'],
                            'linea' => $vehiculoData['linea'],
                            'color' => 'BLANCO',
                            'placas' => 'DEL' . rand(100, 999),
                            'estado_placas' => 'MICHOACAN',
                            'serie' => null,
                            'capacidad_personas' => 5,
                            'tipo_servicio' => $vehiculoData['tipo_servicio'],
                            'tarjeta_circulacion_nombre' => 'REGISTRO DE PRUEBA',
                            'grua' => 'N/A',
                            'corralon' => null,
                            'aseguradora' => 'SIN SEGURO',
                            'fotos' => null,
                            'antecedente_vehiculo' => 0,
                            'monto_danos' => $vehiculoData['monto_danos'],
                            'partes_danadas' => 'DAÑOS DE PRUEBA',
                            'created_at' => $hora->format('Y-m-d H:i:s'),
                            'updated_at' => $hora->format('Y-m-d H:i:s'),
                        ]);

                        DB::table('hecho_vehiculo')->insert([
                            'hecho_id' => $hechoId,
                            'vehiculo_id' => $vehiculoId,
                            'created_at' => $hora->format('Y-m-d H:i:s'),
                            'updated_at' => $hora->format('Y-m-d H:i:s'),
                        ]);

                        $conductorData = $conductoresBase[($index + $i + $v) % count($conductoresBase)];

                        $conductorId = DB::table('conductores')->insertGetId([
                            'nombre' => $conductorData['nombre'],
                            'edad' => $conductorData['edad'],
                            'domicilio' => 'DOMICILIO DE PRUEBA',
                            'telefono' => null,
                            'sexo' => $conductorData['sexo'],
                            'ocupacion' => 'CONDUCTOR',
                            'cinturon' => 1,
                            'antecedentes' => 0,
                            'certificado_lesiones' => 0,
                            'certificado_alcoholemia' => 0,
                            'aliento_etilico' => 0,
                            'estado_licencia' => 'MICHOACAN',
                            'vigencia_licencia' => null,
                            'permanente' => 0,
                            'numero_licencia' => null,
                            'tipo_licencia' => 'AUTOMOVILISTA',
                            'created_at' => $hora->format('Y-m-d H:i:s'),
                            'updated_at' => $hora->format('Y-m-d H:i:s'),
                        ]);

                        DB::table('vehiculo_conductor')->insert([
                            'vehiculo_id' => $vehiculoId,
                            'conductor_id' => $conductorId,
                            'created_at' => $hora->format('Y-m-d H:i:s'),
                            'updated_at' => $hora->format('Y-m-d H:i:s'),
                        ]);
                    }

                    if ($i === 1 || $i === 2) {
                        DB::table('lesionados')->insert([
                            'hecho_id' => $hechoId,
                            'nombre' => 'LESIONADO DE PRUEBA',
                            'edad' => rand(15, 60),
                            'sexo' => rand(0, 1) ? 'Masculino' : 'Femenino',
                            'tipo_lesion' => $i === 2 ? 'Fallecido' : 'Moderada',
                            'hospitalizado' => 1,
                            'hospital' => 'HOSPITAL DE PRUEBA',
                            'atencion_en_sitio' => 1,
                            'ambulancia' => 'AMBULANCIA DE PRUEBA',
                            'paramedico' => 'PARAMÉDICO DE PRUEBA',
                            'observaciones' => null,
                            'created_at' => $hora->format('Y-m-d H:i:s'),
                            'updated_at' => $hora->format('Y-m-d H:i:s'),
                        ]);
                    }
                }
            }
        });
    }
}
