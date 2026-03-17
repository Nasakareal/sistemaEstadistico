<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Operativo;
use App\Models\Delegacion;
use App\Models\Destacamento;
use App\Models\OperativoCatalogo;
use App\Models\OperativoDispositivo;
use App\Models\OperativoDispositivoCatalogo;

class GuardianesCaminoDemoSeeder extends Seeder
{
    public function run()
    {
        $tz = 'America/Mexico_City';

        $inicio = Carbon::now($tz)->startOfDay();
        $fin = Carbon::create($inicio->year, $inicio->month, 25, 23, 59, 59, $tz);

        $catalogoGuardianes = OperativoCatalogo::where('slug', 'guardianes-del-camino')->first();

        if (!$catalogoGuardianes) {
            $this->command->error("No existe el operativo_catalogo con slug 'guardianes-del-camino'.");
            return;
        }

        $delegaciones = Delegacion::pluck('id')->toArray();
        $destacamentos = Destacamento::pluck('id')->toArray();
        $usuarios = User::pluck('id')->toArray();

        $catalogosDispositivos = OperativoDispositivoCatalogo::where('activo', 1)
            ->orderBy('orden')
            ->pluck('id')
            ->toArray();

        if (empty($catalogosDispositivos)) {
            $this->command->error('No hay registros en operativo_dispositivo_catalogos.');
            return;
        }

        $lugares = [
            'MORELIA - SALIDA A QUIROGA',
            'MORELIA - SALIDA A SALAMANCA',
            'MORELIA - SALIDA A PATZCUARO',
            'AUTOPISTA SIGLO XXI KM 45',
            'AUTOPISTA SIGLO XXI KM 120',
            'CARRETERA MORELIA - ZINAPECUARO',
            'CARRETERA MORELIA - MIL CUMBRES',
            'CARRETERA MORELIA - CHARO',
            'CARRETERA MORELIA - TIRIPETIO',
        ];

        $observacionesOperativo = [
            'Sin novedad relevante.',
            'Se mantuvo presencia policial y vigilancia preventiva.',
            'Operativo desarrollado dentro de la normalidad.',
            'Se realizaron recorridos disuasivos y revisión preventiva.',
            'Se brindó apoyo vial y presencia institucional.',
        ];

        $observacionesDispositivo = [
            'Actividad preventiva.',
            'Sin incidencias mayores.',
            'Se mantuvo presencia institucional.',
            'Dispositivo efectuado con normalidad.',
            'Se realizaron acciones de prevención y vigilancia.',
        ];

        $fecha = $inicio->copy();

        while ($fecha->lte($fin)) {
            $operativosDia = rand(2, 5);

            for ($i = 0; $i < $operativosDia; $i++) {
                $operativo = Operativo::create([
                    'captura_uuid' => (string) Str::uuid(),
                    'fecha' => $fecha->format('Y-m-d'),
                    'hora' => Carbon::createFromTime(rand(6, 22), rand(0, 59), 0, $tz)->format('H:i'),
                    'operativo_catalogo_id' => $catalogoGuardianes->id,
                    'unidad_org_id' => 4,
                    'delegacion_id' => !empty($delegaciones) ? $delegaciones[array_rand($delegaciones)] : null,
                    'destacamento_id' => !empty($destacamentos) ? $destacamentos[array_rand($destacamentos)] : null,
                    'lugar' => $lugares[array_rand($lugares)],
                    'descripcion' => 'Operativo Guardianes del Camino',
                    'dispositivos_realizados' => rand(1, 4),
                    'vehiculos_inspeccionados' => rand(10, 120),
                    'personas_inspeccionadas' => rand(10, 200),
                    'vehiculos_impactados' => rand(0, 20),
                    'personas_impactadas' => rand(0, 40),
                    'antecedentes_personas' => rand(0, 10),
                    'antecedentes_vehiculos' => rand(0, 5),
                    'antecedentes_motos' => rand(0, 4),
                    'antecedentes_camiones' => rand(0, 3),
                    'estado_fuerza_participante' => rand(4, 15),
                    'kilometros_recorridos' => rand(10, 150),
                    'acompanamientos' => rand(0, 5),
                    'abanderamientos' => rand(0, 5),
                    'auxilios_viales' => rand(0, 8),
                    'puestas_disposicion' => rand(0, 3),
                    'vehiculos_recuperados' => rand(0, 2),
                    'armas_aseguradas' => rand(0, 1),
                    'mercancia_recuperada' => rand(0, 2),
                    'decomiso_drogas' => rand(0, 1),
                    'crps_participantes' => '25-' . rand(1000, 9999) . ', 22-' . rand(1000, 9999),
                    'observaciones' => $observacionesOperativo[array_rand($observacionesOperativo)],
                    'created_by' => !empty($usuarios) ? $usuarios[array_rand($usuarios)] : null,
                    'updated_by' => !empty($usuarios) ? $usuarios[array_rand($usuarios)] : null,
                ]);

                $cantidadDispositivos = rand(1, 4);

                for ($d = 0; $d < $cantidadDispositivos; $d++) {
                    OperativoDispositivo::create([
                        'operativo_id' => $operativo->id,
                        'operativo_dispositivo_catalogo_id' => $catalogosDispositivos[array_rand($catalogosDispositivos)],
                        'fecha' => $fecha->format('Y-m-d'),
                        'hora' => Carbon::createFromTime(rand(6, 22), rand(0, 59), 0, $tz)->format('H:i'),
                        'unidad_org_id' => 4,
                        'delegacion_id' => !empty($delegaciones) ? $delegaciones[array_rand($delegaciones)] : null,
                        'destacamento_id' => !empty($destacamentos) ? $destacamentos[array_rand($destacamentos)] : null,
                        'user_id' => !empty($usuarios) ? $usuarios[array_rand($usuarios)] : null,
                        'lugar' => $lugares[array_rand($lugares)],
                        'descripcion' => 'Captura de dispositivo dentro del operativo Guardianes del Camino',
                        'cantidad' => rand(1, 3),
                        'vehiculos_inspeccionados' => rand(0, 60),
                        'personas_inspeccionadas' => rand(0, 80),
                        'vehiculos_impactados' => rand(0, 10),
                        'personas_impactadas' => rand(0, 20),
                        'estado_fuerza_participante' => rand(3, 10),
                        'kilometros_recorridos' => rand(5, 80),
                        'crps_participantes' => '25-' . rand(1000, 9999) . ', 22-' . rand(1000, 9999),
                        'acompanamientos' => rand(0, 4),
                        'abanderamientos' => rand(0, 4),
                        'auxilios_viales' => rand(0, 6),
                        'prox_empresas' => rand(0, 5),
                        'prox_tiendas_conveniencia' => rand(0, 5),
                        'prox_escuelas' => rand(0, 5),
                        'prox_hospitales' => rand(0, 5),
                        'antecedentes_personas' => rand(0, 4),
                        'antecedentes_vehiculos' => rand(0, 3),
                        'antecedentes_motos' => rand(0, 2),
                        'antecedentes_camiones' => rand(0, 2),
                        'puestas_disposicion' => rand(0, 2),
                        'vehiculos_recuperados' => rand(0, 1),
                        'armas_aseguradas' => rand(0, 1),
                        'mercancia_recuperada' => rand(0, 1),
                        'decomiso_drogas' => rand(0, 1),
                        'observaciones' => $observacionesDispositivo[array_rand($observacionesDispositivo)],
                        'created_by' => !empty($usuarios) ? $usuarios[array_rand($usuarios)] : null,
                        'updated_by' => !empty($usuarios) ? $usuarios[array_rand($usuarios)] : null,
                    ]);
                }
            }

            $fecha->addDay();
        }

        $this->command->info('Seeder GuardianesCaminoDemoSeeder ejecutado correctamente.');
    }
}
