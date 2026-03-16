<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PuestasDisposicionCarreterasDemoSeeder extends Seeder
{
    public function run(): void
    {
        $unidadId = 4;
        $area = 'PROTECCIÓN A CARRETERAS';
        $anio = now()->year;
        $createdBy = 68;

        DB::transaction(function () use ($unidadId, $area, $anio, $createdBy) {

            /*
            |--------------------------------------------------------------
            | LIMPIAR SOLO DATOS DE CARRETERAS
            |--------------------------------------------------------------
            */
            $idsCarreteras = DB::table('puestas_disposicion')
                ->where('unidad_id', $unidadId)
                ->pluck('id')
                ->toArray();

            if (!empty($idsCarreteras)) {
                DB::table('puestas_disposicion_personas')
                    ->whereIn('puesta_disposicion_id', $idsCarreteras)
                    ->delete();

                DB::table('puestas_disposicion_vehiculos')
                    ->whereIn('puesta_disposicion_id', $idsCarreteras)
                    ->delete();

                DB::table('puestas_disposicion_objetos')
                    ->whereIn('puesta_disposicion_id', $idsCarreteras)
                    ->delete();

                DB::table('puestas_disposicion')
                    ->whereIn('id', $idsCarreteras)
                    ->delete();
            }

            /*
            |--------------------------------------------------------------
            | CATÁLOGOS DEMO
            |--------------------------------------------------------------
            */
            $motivos = [
                'DELITOS CONTRA LA SALUD',
                'ROBO DE VEHÍCULO',
                'RECUPERACIÓN DE VEHÍCULO CON REPORTE DE ROBO',
                'POSESIÓN DE SUSTANCIAS ILÍCITAS',
                'PORTACIÓN DE ARMA DE FUEGO',
                'PORTACIÓN DE ARMA BLANCA',
                'ALTERACIÓN DEL ORDEN PÚBLICO',
                'HECHOS POSIBLEMENTE CONSTITUTIVOS DE DELITO',
                'PRESENTACIÓN DE PERSONA',
                'ASEGURAMIENTO DE VEHÍCULO',
                'ASEGURAMIENTO DE OBJETOS',
                'RESISTENCIA DE PARTICULARES',
                'ULTRAJES A LA AUTORIDAD',
                'DAÑOS EN LAS COSAS',
                'HECHO DE TRÁNSITO CON PUESTA A DISPOSICIÓN',
                'ENCUBRIMIENTO',
                'POSESIÓN DE CARTUCHOS ÚTILES',
                'CONDUCCIÓN DE VEHÍCULO CON REPORTE DE ROBO',
                'LOCALIZACIÓN DE UNIDAD ABANDONADA',
                'ASEGURAMIENTO POR HECHO DELICTIVO',
                'POSESIÓN DE OBJETOS PROHIBIDOS',
                'DETENCIÓN EN FILTRO DE INSPECCIÓN',
                'ALTERACIÓN DE MEDIOS DE IDENTIFICACIÓN VEHICULAR',
                'RECUPERACIÓN DE OBJETOS ROBADOS',
                'OMISIÓN DE AUXILIO',
                'ASEGURAMIENTO DERIVADO DE REPORTE CIUDADANO',
                'REVISIÓN PREVENTIVA POSITIVA',
                'HALLAZGO DE INDICIOS',
                'PUESTA A DISPOSICIÓN DE VEHÍCULO ABANDONADO',
            ];

            $tiposPuesta = ['PERSONA', 'VEHICULO', 'OBJETO', 'MIXTA'];

            $policias = [
                'MARIO BAUTISTA',
                'PEDRO RAMÍREZ',
                'JUAN CARLOS MEDINA',
                'LUIS FERNANDO ORTIZ',
                'ROBERTO GARCÍA',
                'JOSÉ MANUEL FLORES',
                'MIGUEL ÁNGEL TORRES',
                'CARLOS ALBERTO REYES',
                'EDUARDO SÁNCHEZ',
                'RAFAEL HERNÁNDEZ',
                'ALBERTO MENDOZA',
                'ERNESTO SALAZAR',
            ];

            $mps = [
                'LIC. ADRIANA MENDOZA',
                'LIC. MARIO ALBERTO LUNA',
                'LIC. JORGE RANGEL',
                'LIC. ELENA VARGAS',
                'LIC. PATRICIA SOLÍS',
                'LIC. FERNANDO SALGADO',
                'LIC. ROSAURA CAMPOS',
            ];

            $autoridades = [
                'FISCALÍA GENERAL DEL ESTADO',
                'AGENCIA DEL MINISTERIO PÚBLICO',
                'FISCALÍA REGIONAL',
                'MINISTERIO PÚBLICO EN TURNO',
            ];

            $lugares = [
                'KM 12+500 AUTOPISTA MORELIA - SALAMANCA',
                'KM 145 CARRETERA MORELIA - ZINAPÉCUARO',
                'KM 78 AUTOPISTA DE OCCIDENTE',
                'ENTRONQUE A CHARO',
                'CASETA DE PEAJE PANINDÍCUARO',
                'KM 201 CARRETERA MORELIA - PÁTZCUARO',
                'TRAMO MARAVATÍO - ATLACOMULCO',
                'ENTRONQUE A COPÁNDARO',
                'KM 56 CARRETERA FEDERAL',
                'LATERAL SALIDA A SALAMANCA',
                'KM 33 CARRETERA MORELIA - QUIROGA',
                'TRAMO CUITZEO - MORELIA',
                'CASETA DE ECUANDUREO',
                'ENTRONQUE A TARÍMBARO',
                'LIBRAMIENTO NORTE',
            ];

            $narrativas = [
                'PERSONA ASEGURADA DURANTE RECORRIDO DE VIGILANCIA EN TRAMO CARRETERO, SIENDO TRASLADADA ANTE LA AUTORIDAD COMPETENTE.',
                'VEHÍCULO LOCALIZADO DURANTE PATRULLAJE PREVENTIVO, PRESENTANDO IRREGULARIDADES EN SUS MEDIOS DE IDENTIFICACIÓN.',
                'OBJETOS ASEGURADOS EN INSPECCIÓN PREVENTIVA, MISMOS QUE FUERON PUESTOS A DISPOSICIÓN DE LA AUTORIDAD COMPETENTE.',
                'DURANTE OPERATIVO DE SEGURIDAD EN CARRETERA SE REALIZÓ ASEGURAMIENTO RELACIONADO CON HECHOS POSIBLEMENTE CONSTITUTIVOS DE DELITO.',
                'EN PUNTO DE REVISIÓN SE DETECTÓ CONDUCTA IRREGULAR, PROCEDIENDO AL ASEGURAMIENTO Y PUESTA A DISPOSICIÓN CORRESPONDIENTE.',
                'EN ATENCIÓN A REPORTE CIUDADANO SE REALIZÓ INTERVENCIÓN POLICIAL Y TRASLADO ANTE LA AUTORIDAD COMPETENTE.',
            ];

            $observaciones = [
                'SIN NOVEDAD',
                'SE ANEXA INFORME POLICIAL HOMOLOGADO',
                'SE ANEXA CADENA DE CUSTODIA',
                'PUESTA A DISPOSICIÓN REALIZADA SIN INCIDENTES',
                'SE ENTREGA DOCUMENTACIÓN COMPLEMENTARIA',
                'QUEDA A DISPOSICIÓN DE LA AUTORIDAD CORRESPONDIENTE',
            ];

            $nombres = [
                'JUAN PÉREZ LÓPEZ',
                'CARLOS MARTÍNEZ RUIZ',
                'MIGUEL ÁNGEL SOTO',
                'ROBERTO HERNÁNDEZ DÍAZ',
                'LUIS FERNANDO GARCÍA',
                'JOSÉ MANUEL VARGAS',
                'EDUARDO RAMÍREZ PÉREZ',
                'RAFAEL CAMPOS TORRES',
                'ANTONIO FLORES LUNA',
                'PEDRO GUTIÉRREZ MORA',
                'MARIO ALBERTO SALAS',
                'OSCAR DANIEL REYES',
                'ALEJANDRO MORENO CRUZ',
                'JULIO CÉSAR NAVARRO',
                'DAVID MENDOZA HERRERA',
            ];

            $aliases = ['EL GÜERO', 'EL FLACO', 'EL NEGRO', 'EL TAZ', 'EL PELÓN', 'EL CHINO', null, null];
            $calidadesPersona = ['DETENIDO', 'PRESENTADO', 'IMPUTADO', 'CONDUCTOR', 'POSEEDOR'];
            $sexos = ['MASCULINO', 'FEMENINO'];
            $delitosPersona = [
                'ROBO',
                'POSESIÓN DE SUSTANCIAS',
                'PORTACIÓN DE ARMA',
                'ALTERACIÓN DEL ORDEN',
                'RESISTENCIA DE PARTICULARES',
                'ENCUBRIMIENTO',
            ];

            $tiposVehiculo = ['AUTOMÓVIL', 'CAMIONETA', 'MOTOCICLETA', 'TRACTOCAMIÓN', 'REMOLQUE', 'PICK UP'];
            $marcas = ['NISSAN', 'CHEVROLET', 'FORD', 'TOYOTA', 'VOLKSWAGEN', 'HONDA', 'KIA', 'MAZDA'];
            $lineas = ['SENTRA', 'VERSA', 'NP300', 'AVEO', 'SPARK', 'RANGER', 'HILUX', 'JETTA', 'VENTO', 'CIVIC'];
            $colores = ['BLANCO', 'NEGRO', 'GRIS', 'ROJO', 'AZUL', 'PLATA', 'VERDE'];
            $calidadesVehiculo = ['ASEGURADO', 'PUESTO A DISPOSICIÓN', 'INVOLUCRADO', 'ABANDONADO'];
            $motivosVehiculo = [
                'RELACIONADO CON HECHO DELICTIVO',
                'CON REPORTE DE ROBO',
                'ALTERACIÓN DE SERIES',
                'ABANDONO EN TRAMO CARRETERO',
                'ASEGURADO EN FILTRO DE INSPECCIÓN',
            ];

            $tiposObjeto = [
                'ARMA DE FUEGO',
                'ARMA BLANCA',
                'TELÉFONO CELULAR',
                'BOLSA PLÁSTICA',
                'MOCHILA',
                'DOCUMENTO',
                'CARTUCHOS',
                'HERRAMIENTA',
                'SUSTANCIA',
                'DINERO EN EFECTIVO',
            ];

            $descripcionesObjeto = [
                'OBJETO ASEGURADO RELACIONADO CON EL HECHO',
                'INDICIO EMBALADO PARA PUESTA A DISPOSICIÓN',
                'ARTÍCULO LOCALIZADO DURANTE INSPECCIÓN',
                'OBJETO ASEGURADO EN INTERVENCIÓN POLICIAL',
                'INDICIO ASEGURADO EN FILTRO DE REVISIÓN',
            ];

            $unidadesMedida = ['PIEZA', 'BOLSA', 'CAJA', 'ENVOLTORIO', 'UNIDAD'];
            $cadenasCustodia = ['CC-2026-001', 'CC-2026-002', 'CC-2026-003', 'CC-2026-004', 'CC-2026-005'];

            /*
            |--------------------------------------------------------------
            | CREAR 40 PUESTAS COMPLETAS
            |--------------------------------------------------------------
            */
            for ($i = 1; $i <= 40; $i++) {
                $fecha = Carbon::now()
                    ->subDays(rand(0, 25))
                    ->setTime(rand(0, 23), rand(0, 59), 0);

                $tipoPuesta = $tiposPuesta[array_rand($tiposPuesta)];

                $puestaId = DB::table('puestas_disposicion')->insertGetId([
                    'hecho_id'              => null,
                    'numero_puesta'         => $i,
                    'anio'                  => $anio,
                    'tipo_puesta'           => $tipoPuesta,
                    'motivo'                => $motivos[array_rand($motivos)],
                    'estatus'               => 'ACTIVA',
                    'nombre_policia'        => $policias[array_rand($policias)],
                    'nombre_mp'             => $mps[array_rand($mps)],
                    'autoridad_receptora'   => $autoridades[array_rand($autoridades)],
                    'area'                  => $area,
                    'carpeta_investigacion' => 'CI/' . $anio . '/' . str_pad((string)$i, 4, '0', STR_PAD_LEFT),
                    'oficio'                => 'OF-' . $anio . '-' . str_pad((string)$i, 4, '0', STR_PAD_LEFT),
                    'fecha_puesta'          => $fecha->toDateString(),
                    'hora_puesta'           => $fecha->format('H:i:s'),
                    'lugar_puesta'          => $lugares[array_rand($lugares)],
                    'narrativa'             => $narrativas[array_rand($narrativas)],
                    'observaciones'         => $observaciones[array_rand($observaciones)],
                    'unidad_id'             => $unidadId,
                    'delegacion_id'         => null,
                    'destacamento_id'       => rand(0, 1) ? 1 : null,
                    'archivo_puesta'        => null,
                    'created_by'            => $createdBy,
                    'updated_by'            => rand(0, 1) ? $createdBy : null,
                    'created_at'            => $fecha,
                    'updated_at'            => $fecha,
                ]);

                /*
                |----------------------------------------------------------
                | PERSONAS
                |----------------------------------------------------------
                */
                if (in_array($tipoPuesta, ['PERSONA', 'MIXTA'])) {
                    $totalPersonas = rand(1, 3);

                    for ($p = 1; $p <= $totalPersonas; $p++) {
                        $nombre = $nombres[array_rand($nombres)];
                        $edad = rand(18, 57);
                        $sexo = $sexos[array_rand($sexos)];

                        DB::table('puestas_disposicion_personas')->insert([
                            'puesta_disposicion_id' => $puestaId,
                            'nombre_completo'       => $nombre,
                            'alias'                 => $aliases[array_rand($aliases)],
                            'edad'                  => $edad,
                            'sexo'                  => $sexo,
                            'fecha_nacimiento'      => Carbon::now()->subYears($edad)->subDays(rand(0, 364))->toDateString(),
                            'curp'                  => 'DEMO' . strtoupper(substr(md5($nombre . $p . $i), 0, 14)),
                            'rfc'                   => strtoupper(substr(md5('RFC' . $nombre . $i), 0, 13)),
                            'domicilio'             => 'DOMICILIO CONOCIDO EN MORELIA, MICHOACÁN',
                            'calidad'               => $calidadesPersona[array_rand($calidadesPersona)],
                            'delito_o_motivo'       => $delitosPersona[array_rand($delitosPersona)],
                            'orden_aprehension'     => rand(0, 1),
                            'mandamiento_judicial'  => rand(0, 1) ? 'OFICIO JUDICIAL ' . rand(100, 999) : null,
                            'observaciones'         => 'REGISTRO DEMO DE PERSONA RELACIONADA CON LA PUESTA',
                            'created_at'            => $fecha,
                            'updated_at'            => $fecha,
                        ]);
                    }
                }

                /*
                |----------------------------------------------------------
                | VEHÍCULOS
                |----------------------------------------------------------
                */
                if (in_array($tipoPuesta, ['VEHICULO', 'MIXTA'])) {
                    $totalVehiculos = rand(1, 2);

                    for ($v = 1; $v <= $totalVehiculos; $v++) {
                        $marca = $marcas[array_rand($marcas)];
                        $linea = $lineas[array_rand($lineas)];
                        $modelo = (string)rand(2008, 2024);

                        DB::table('puestas_disposicion_vehiculos')->insert([
                            'puesta_disposicion_id' => $puestaId,
                            'vehiculo_id'           => null,
                            'tipo'                  => $tiposVehiculo[array_rand($tiposVehiculo)],
                            'marca'                 => $marca,
                            'submarca'              => $linea,
                            'modelo'                => $modelo,
                            'color'                 => $colores[array_rand($colores)],
                            'placas'                => strtoupper(substr(md5('PLACA' . $i . $v), 0, 3)) . '-' . rand(100, 999),
                            'serie'                 => strtoupper(substr(md5('SERIE' . $i . $v), 0, 17)),
                            'calidad'               => $calidadesVehiculo[array_rand($calidadesVehiculo)],
                            'motivo_relacion'       => $motivosVehiculo[array_rand($motivosVehiculo)],
                            'con_reporte_robo'      => rand(0, 1),
                            'numero_reporte_robo'   => rand(0, 1) ? 'RR-' . $anio . '-' . rand(1000, 9999) : null,
                            'observaciones'         => 'VEHÍCULO REGISTRADO PARA DEMOSTRACIÓN',
                            'created_at'            => $fecha,
                            'updated_at'            => $fecha,
                        ]);
                    }
                }

                /*
                |----------------------------------------------------------
                | OBJETOS
                |----------------------------------------------------------
                */
                if (in_array($tipoPuesta, ['OBJETO', 'MIXTA'])) {
                    $totalObjetos = rand(1, 4);

                    for ($o = 1; $o <= $totalObjetos; $o++) {
                        DB::table('puestas_disposicion_objetos')->insert([
                            'puesta_disposicion_id' => $puestaId,
                            'tipo_objeto'           => $tiposObjeto[array_rand($tiposObjeto)],
                            'descripcion'           => $descripcionesObjeto[array_rand($descripcionesObjeto)],
                            'cantidad'              => rand(1, 12),
                            'unidad_medida'         => $unidadesMedida[array_rand($unidadesMedida)],
                            'cadena_custodia'       => $cadenasCustodia[array_rand($cadenasCustodia)],
                            'observaciones'         => 'OBJETO ASEGURADO PARA DEMOSTRACIÓN',
                            'created_at'            => $fecha,
                            'updated_at'            => $fecha,
                        ]);
                    }
                }

                if ($tipoPuesta === 'PERSONA' && rand(0, 1)) {
                    DB::table('puestas_disposicion_vehiculos')->insert([
                        'puesta_disposicion_id' => $puestaId,
                        'vehiculo_id'           => null,
                        'tipo'                  => $tiposVehiculo[array_rand($tiposVehiculo)],
                        'marca'                 => $marcas[array_rand($marcas)],
                        'submarca'              => $lineas[array_rand($lineas)],
                        'modelo'                => (string)rand(2008, 2024),
                        'color'                 => $colores[array_rand($colores)],
                        'placas'                => strtoupper(substr(md5('EXTRA' . $i), 0, 3)) . '-' . rand(100, 999),
                        'serie'                 => strtoupper(substr(md5('EXTRASERIE' . $i), 0, 17)),
                        'calidad'               => $calidadesVehiculo[array_rand($calidadesVehiculo)],
                        'motivo_relacion'       => $motivosVehiculo[array_rand($motivosVehiculo)],
                        'con_reporte_robo'      => rand(0, 1),
                        'numero_reporte_robo'   => rand(0, 1) ? 'RR-' . $anio . '-' . rand(1000, 9999) : null,
                        'observaciones'         => 'VEHÍCULO EXTRA ASOCIADO A PERSONA',
                        'created_at'            => $fecha,
                        'updated_at'            => $fecha,
                    ]);
                }

                if ($tipoPuesta === 'VEHICULO' && rand(0, 1)) {
                    DB::table('puestas_disposicion_personas')->insert([
                        'puesta_disposicion_id' => $puestaId,
                        'nombre_completo'       => $nombres[array_rand($nombres)],
                        'alias'                 => $aliases[array_rand($aliases)],
                        'edad'                  => rand(18, 57),
                        'sexo'                  => $sexos[array_rand($sexos)],
                        'fecha_nacimiento'      => Carbon::now()->subYears(rand(18, 57))->toDateString(),
                        'curp'                  => 'DEMO' . strtoupper(substr(md5('EXTRAPER' . $i), 0, 14)),
                        'rfc'                   => strtoupper(substr(md5('RFCPER' . $i), 0, 13)),
                        'domicilio'             => 'DOMICILIO CONOCIDO EN MICHOACÁN',
                        'calidad'               => 'CONDUCTOR',
                        'delito_o_motivo'       => $delitosPersona[array_rand($delitosPersona)],
                        'orden_aprehension'     => 0,
                        'mandamiento_judicial'  => null,
                        'observaciones'         => 'PERSONA ASOCIADA A VEHÍCULO',
                        'created_at'            => $fecha,
                        'updated_at'            => $fecha,
                    ]);
                }
            }
        });
    }
}
