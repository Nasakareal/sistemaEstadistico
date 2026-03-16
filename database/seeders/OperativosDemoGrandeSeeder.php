<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Operativo;
use App\Models\OperativoCatalogo;
use App\Models\Unidad;
use App\Models\Delegacion;

class OperativosDemoGrandeSeeder extends Seeder
{
    public function run()
    {
        $catalogos = OperativoCatalogo::query()
            ->where('activo', 1)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        if ($catalogos->isEmpty()) {
            $this->command->error('No hay registros en operativo_catalogos.');
            return;
        }

        $unidades = Unidad::query()->get();
        if ($unidades->isEmpty()) {
            $this->command->error('No hay registros en unidades.');
            return;
        }

        $delegaciones = Delegacion::query()->get();

        $tz = 'America/Mexico_City';
        $hoy = Carbon::now($tz)->startOfDay();

        $lugares = [
            'MORELIA - SALIDA A QUIROGA',
            'MORELIA - SALIDA A PATZCUARO',
            'AUTOPISTA SIGLO XXI TRAMO MORELIA-PATZCUARO',
            'AUTOPISTA DE OCCIDENTE KM 245',
            'LIBRAMIENTO NORTE',
            'SALIDA A CHARO',
            'TRAMO AEROPUERTO - ZINAPECUARO',
            'INDAPARAPEO - QUERENDARO',
            'TARÍMBARO - MORELIA',
            'SALIDA A SALAMANCA',
            'MORELIA - MIL CUMBRES',
            'TRAMO CAPULA - QUIROGA',
            'MORELIA - TOLUCA',
            'URUAPAN - TARETAN',
            'LA PIEDAD - YURÉCUARO',
        ];

        $descripciones = [
            'OPERATIVO DE VIGILANCIA Y PREVENCIÓN DEL DELITO',
            'OPERATIVO INTERINSTITUCIONAL DE SEGURIDAD',
            'OPERATIVO DE INSPECCIÓN Y PRESENCIA POLICIAL',
            'OPERATIVO DE DISUASIÓN Y PREVENCIÓN',
            'OPERATIVO DE RECORRIDOS Y PUNTOS DE OBSERVACIÓN',
            'OPERATIVO DE REFORZAMIENTO DE SEGURIDAD EN TRAMO CARRETERO',
            'OPERATIVO DE PROXIMIDAD Y VIGILANCIA',
        ];

        $crpsPool = [
            'CRP-01','CRP-02','CRP-03','CRP-04','CRP-05',
            'CRP-06','CRP-07','CRP-08','CRP-09','CRP-10',
            'CRP-11','CRP-12','CRP-13','CRP-14','CRP-15',
            'CRP-16','CRP-17','CRP-18','CRP-19','CRP-20'
        ];

        $totalCapturas = 0;
        $totalFilas = 0;

        for ($diaOffset = 7; $diaOffset >= 0; $diaOffset--) {

            $fecha = $hoy->copy()->subDays($diaOffset)->toDateString();
            $capturasDelDia = rand(3,6);

            for ($c = 1; $c <= $capturasDelDia; $c++) {

                $capturaUuid = (string) Str::uuid();
                $hora = Carbon::createFromTime(rand(6,22),rand(0,59),0,$tz)->format('H:i:s');

                $unidad = $unidades->random();
                $delegacion = $delegaciones->isNotEmpty() ? $delegaciones->random() : null;

                $descripcion = $descripciones[array_rand($descripciones)];
                $lugar = $lugares[array_rand($lugares)];

                $catalogosSeleccionados = $catalogos->shuffle()->take(rand(2,min(5,$catalogos->count())));
                $primerRegistro = true;

                foreach ($catalogosSeleccionados as $catalogo) {

                    $nombreCatalogo = mb_strtolower((string)$catalogo->nombre);

                    $dispositivosRealizados = rand(1,4);
                    $vehiculosInspeccionados = rand(4,60);
                    $personasInspeccionadas = rand(4,90);
                    $vehiculosImpactados = rand(0,14);
                    $personasImpactadas = rand(0,12);
                    $estadoFuerza = rand(4,22);
                    $kilometros = rand(10,220);
                    $acompanamientos = 0;
                    $abanderamientos = 0;
                    $auxiliosViales = 0;

                    if (strpos($nombreCatalogo,'caballeros') !== false) {
                        $vehiculosInspeccionados = 0;
                        $personasInspeccionadas = 0;
                        $vehiculosImpactados = 0;
                        $personasImpactadas = 0;
                        $acompanamientos = rand(1,8);
                        $abanderamientos = rand(0,6);
                        $auxiliosViales = rand(0,7);
                    }
                    elseif (strpos($nombreCatalogo,'carrusel') !== false) {
                        $vehiculosInspeccionados = rand(5,30);
                        $personasInspeccionadas = rand(0,10);
                        $vehiculosImpactados = rand(1,12);
                        $personasImpactadas = rand(0,4);
                    }
                    elseif (
                        strpos($nombreCatalogo,'puesto') !== false ||
                        strpos($nombreCatalogo,'rsv') !== false ||
                        strpos($nombreCatalogo,'patrullaje') !== false ||
                        strpos($nombreCatalogo,'vigilancia') !== false
                    ) {
                        $vehiculosInspeccionados = rand(10,70);
                        $personasInspeccionadas = rand(10,110);
                        $vehiculosImpactados = rand(0,5);
                        $personasImpactadas = rand(0,5);
                    }

                    shuffle($crpsPool);
                    $crps = implode(', ',array_slice($crpsPool,0,rand(2,4)));

                    Operativo::create([
                        'captura_uuid' => $capturaUuid,
                        'fecha' => $fecha,
                        'hora' => $hora,
                        'operativo_catalogo_id' => $catalogo->id,
                        'unidad_org_id' => $unidad->id,
                        'delegacion_id' => $delegacion ? $delegacion->id : null,
                        'destacamento_id' => null,
                        'lugar' => $lugar,
                        'descripcion' => $descripcion,
                        'dispositivos_realizados' => $dispositivosRealizados,
                        'vehiculos_inspeccionados' => $vehiculosInspeccionados,
                        'personas_inspeccionadas' => $personasInspeccionadas,
                        'vehiculos_impactados' => $vehiculosImpactados,
                        'personas_impactadas' => $personasImpactadas,
                        'antecedentes_personas' => $primerRegistro ? rand(0,6) : 0,
                        'antecedentes_vehiculos' => $primerRegistro ? rand(0,6) : 0,
                        'antecedentes_motos' => $primerRegistro ? rand(0,4) : 0,
                        'antecedentes_camiones' => $primerRegistro ? rand(0,3) : 0,
                        'estado_fuerza_participante' => $estadoFuerza,
                        'kilometros_recorridos' => $kilometros,
                        'acompanamientos' => $acompanamientos,
                        'abanderamientos' => $abanderamientos,
                        'auxilios_viales' => $auxiliosViales,
                        'puestas_disposicion' => $primerRegistro ? rand(0,3) : 0,
                        'vehiculos_recuperados' => $primerRegistro ? rand(0,2) : 0,
                        'armas_aseguradas' => $primerRegistro ? rand(0,1) : 0,
                        'mercancia_recuperada' => $primerRegistro ? rand(0,1) : 0,
                        'decomiso_drogas' => $primerRegistro ? rand(0,1) : 0,
                        'crps_participantes' => $crps,
                        'observaciones' => $this->generarObservacion($nombreCatalogo),
                        'created_by' => 1,
                        'updated_by' => 1,
                        'created_at' => Carbon::parse($fecha.' '.$hora,$tz),
                        'updated_at' => Carbon::parse($fecha.' '.$hora,$tz)
                    ]);

                    $primerRegistro = false;
                    $totalFilas++;
                }

                $totalCapturas++;
            }
        }

        $this->command->info("Seeder completado: {$totalCapturas} capturas y {$totalFilas} filas de operativos creadas.");
    }

    private function generarObservacion(string $nombreCatalogo): string
    {
        if (strpos($nombreCatalogo,'caballeros') !== false) {
            $opciones = [
                'SIN NOVEDAD RELEVANTE DURANTE EL AUXILIO VIAL.',
                'SE BRINDÓ APOYO A USUARIOS DE LA VÍA SIN INCIDENTES.',
                'SE MANTUVO PRESENCIA Y APOYO CARRETERO EN EL TRAMO.'
            ];
            return $opciones[array_rand($opciones)];
        }

        $opciones = [
            'SIN NOVEDAD RELEVANTE.',
            'OPERATIVO REALIZADO SIN INCIDENTES MAYORES.',
            'SE MANTUVO PRESENCIA POLICIAL Y RECORRIDOS PREVENTIVOS.',
            'SE REALIZÓ INSPECCIÓN VISUAL Y DISUASIÓN EN EL TRAMO.',
            'RESULTADO OPERATIVO DENTRO DE LA NORMALIDAD.'
        ];

        return $opciones[array_rand($opciones)];
    }
}
