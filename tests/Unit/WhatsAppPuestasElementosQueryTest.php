<?php

namespace Tests\Unit;

use App\Models\Personal;
use App\Models\PuestaDisposicion;
use App\Models\User;
use App\Services\WhatsApp\WhatsAppMenuService;
use App\Services\WhatsApp\WhatsAppQueryService;
use App\Services\WhatsApp\WhatsAppRenderService;
use App\Services\WhatsApp\WhatsAppUserResolverService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WhatsAppPuestasElementosQueryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_top_de_subdirector_ignora_otra_unidad_solicitada(): void
    {
        $fecha = '2042-07-27';
        $this->crearPuesta(4, $fecha, 'RANKING CARRETERAS JUAN', 2);
        $this->crearPuesta(1, $fecha, 'RANKING SINIESTROS AJENO', 5);

        $packet = $this->service()->executeOpenAI(
            $this->usuarioCarreteras(),
            $this->contextoCarreteras(),
            [
                'accion' => 'top_puestas_elementos',
                'unidad_id' => 1,
                'filtros' => ['fecha' => $fecha],
            ]
        );

        $this->assertStringContainsString('RANKING CARRETERAS JUAN', $packet['text']);
        $this->assertStringContainsString('Puestas: 02', $packet['text']);
        $this->assertStringNotContainsString('RANKING SINIESTROS AJENO', $packet['text']);
    }

    public function test_tarjeta_del_top_incluye_expediente_y_desempeno_de_su_unidad(): void
    {
        $fecha = '2042-07-28';
        $this->crearPuesta(4, $fecha, 'RANKING CARRETERAS JUAN', 3);
        $this->crearPuesta(1, $fecha, 'RANKING SINIESTROS AJENO', 6);

        Personal::query()->create([
            'unidad_id' => 4,
            'nombre' => 'JUAN',
            'ap_paterno' => 'RANKING',
            'ap_materno' => 'CARRETERAS',
            'numero_empleado' => 'WA-TOP-2042',
            'grado' => 'OFICIAL',
            'puesto' => 'ELEMENTO',
            'estatus' => 'ACTIVO',
        ]);

        $packet = $this->service()->executeOpenAI(
            $this->usuarioCarreteras(),
            $this->contextoCarreteras(),
            [
                'accion' => 'tarjeta_top_puestas',
                'unidad_id' => 1,
                'posicion' => 1,
                'filtros' => ['fecha' => $fecha],
            ]
        );

        $this->assertStringContainsString('EXPEDIENTE DE PERSONAL', $packet['text']);
        $this->assertStringContainsString('RANKING CARRETERAS JUAN', $packet['text']);
        $this->assertStringContainsString('DESEMPEÑO EN PUESTAS A DISPOSICIÓN', $packet['text']);
        $this->assertStringContainsString('Puestas a disposición: 03', $packet['text']);
        $this->assertStringNotContainsString('RANKING SINIESTROS AJENO', $packet['text']);
    }

    public function test_tarjeta_por_nombre_no_permite_consultar_personal_de_otra_unidad(): void
    {
        Personal::query()->create([
            'unidad_id' => 1,
            'nombre' => 'FORANEO',
            'ap_paterno' => 'SOLO',
            'ap_materno' => 'SINIESTROS',
            'numero_empleado' => 'WA-AJENO-2042',
            'estatus' => 'ACTIVO',
        ]);

        Personal::query()->create([
            'unidad_id' => 4,
            'nombre' => 'PROPIO',
            'ap_paterno' => 'SOLO',
            'ap_materno' => 'CARRETERAS',
            'numero_empleado' => 'WA-PROPIO-2042',
            'estatus' => 'ACTIVO',
        ]);

        $ajeno = $this->service()->executeOpenAI(
            $this->usuarioCarreteras(),
            $this->contextoCarreteras(),
            [
                'accion' => 'detalle_personal',
                'unidad_id' => 1,
                'persona' => 'SOLO SINIESTROS FORANEO',
            ]
        );

        $propio = $this->service()->executeOpenAI(
            $this->usuarioCarreteras(),
            $this->contextoCarreteras(),
            [
                'accion' => 'detalle_personal',
                'unidad_id' => 1,
                'persona' => 'SOLO CARRETERAS PROPIO',
            ]
        );

        $this->assertStringContainsString('No encontré personal', $ajeno['text']);
        $this->assertStringNotContainsString('EXPEDIENTE DE PERSONAL', $ajeno['text']);
        $this->assertStringContainsString('EXPEDIENTE DE PERSONAL', $propio['text']);
        $this->assertStringContainsString('SOLO CARRETERAS PROPIO', $propio['text']);
    }

    public function test_expediente_de_carreteras_incluye_total_de_puestas_sin_duplicarlas(): void
    {
        Personal::query()->create([
            'unidad_id' => 4,
            'nombre' => 'JUAN',
            'ap_paterno' => 'PEREZ',
            'ap_materno' => 'LOPEZ',
            'numero_empleado' => 'WA-PUESTAS-2042',
            'estatus' => 'ACTIVO',
        ]);

        $fecha = '2042-07-29';
        $this->crearPuesta(4, $fecha, 'PEREZ LOPEZ JUAN', 2);
        PuestaDisposicion::query()->create([
            'numero_puesta' => 8801,
            'anio' => 2042,
            'tipo_puesta' => 'PERSONA',
            'motivo' => 'APOYO',
            'estatus' => 'ACTIVA',
            'nombre_policia' => 'OTRO ELEMENTO',
            'personal_participante' => 'JUAN PEREZ LOPEZ',
            'fecha_puesta' => $fecha,
            'unidad_id' => 4,
        ]);
        PuestaDisposicion::query()->create([
            'numero_puesta' => 8802,
            'anio' => 2042,
            'tipo_puesta' => 'PERSONA',
            'motivo' => 'DUPLICADO DE ROL',
            'estatus' => 'ACTIVA',
            'nombre_policia' => 'JUAN PEREZ LOPEZ',
            'personal_participante' => 'PEREZ LOPEZ JUAN',
            'fecha_puesta' => '2042-07-30',
            'unidad_id' => 4,
        ]);

        $packet = $this->service()->executeOpenAI(
            $this->usuarioCarreteras(),
            $this->contextoCarreteras(),
            [
                'accion' => 'detalle_personal',
                'persona' => 'WA-PUESTAS-2042',
                'unidad_id' => 1,
            ]
        );

        $this->assertStringContainsString('PUESTAS A DISPOSICIÓN', $packet['text']);
        $this->assertStringContainsString('Total vinculadas: 04', $packet['text']);
        $this->assertStringContainsString('Como primer respondiente: 03', $packet['text']);
        $this->assertStringContainsString('Como participante: 01', $packet['text']);
        $this->assertStringContainsString('Última puesta: 2042-07-30', $packet['text']);
    }

    public function test_superadmin_sin_unidad_solicitada_ve_puestas_si_el_expediente_es_de_carreteras(): void
    {
        Personal::query()->create([
            'unidad_id' => 4,
            'nombre' => 'SUPERADMIN',
            'ap_paterno' => 'PRUEBA',
            'ap_materno' => 'UNIDAD',
            'numero_empleado' => '5269-WA-TEST',
            'estatus' => 'ACTIVO',
        ]);
        $this->crearPuesta(4, '2042-08-01', 'PRUEBA UNIDAD SUPERADMIN', 2);

        $user = new User();
        $user->id = 1;
        $user->unidad_id = 1;
        $packet = $this->service()->executeOpenAI($user, [
            'acceso_total' => true,
            'modules' => ['siniestros', 'delegaciones', 'coordinacion', 'carreteras', 'vialidades'],
            'default_module' => null,
            'unidad_id' => 1,
        ], [
            'accion' => 'detalle_personal',
            'persona' => '5269-WA-TEST',
            'unidad_id' => null,
        ]);

        $this->assertStringContainsString('PROTECCIÓN A CARRETERAS', $packet['text']);
        $this->assertStringContainsString('PUESTAS A DISPOSICIÓN', $packet['text']);
        $this->assertStringContainsString('Total vinculadas: 02', $packet['text']);
    }

    public function test_menu_de_carreteras_expone_indicadores_ejecutivos_y_tarjeta_por_posicion(): void
    {
        $menu = new WhatsAppMenuService();
        $packet = $menu->buildModuleMenu(
            $this->usuarioCarreteras(),
            $this->contextoCarreteras(),
            'carreteras'
        );

        $rows = $packet['interactive']['action']['sections'][0]['rows'];
        $ids = collect($rows)->pluck('id')->all();

        $this->assertContains('action:rendimiento_carreteras', $ids);
        $this->assertContains('action:incapacidades_carreteras', $ids);
        $this->assertContains('action:detalle_puesta', $ids);
        $this->assertCount(10, $rows);

        $action = $menu->resolveActionSelection(
            ['value' => 'action:detalle_puesta'],
            'carreteras',
            $this->contextoCarreteras()
        );

        $this->assertSame('detalle_puesta', $action['key']);
        $this->assertTrue($action['requires_param']);
        $this->assertSame('folio', $action['param_type']);
    }

    public function test_detalle_de_puesta_incluye_datos_generales_personas_vehiculos_y_objetos(): void
    {
        $numero = (int) PuestaDisposicion::query()
            ->where('anio', 2042)
            ->where('unidad_id', 4)
            ->max('numero_puesta') + 500;

        $puesta = PuestaDisposicion::query()->create([
            'numero_puesta' => $numero,
            'anio' => 2042,
            'tipo_puesta' => 'PERSONA Y VEHÍCULO',
            'motivo' => 'HECHOS DELICTIVOS',
            'estatus' => 'ACTIVA',
            'nombre_policia' => 'OFICIAL RESPONSABLE',
            'nombre_mp' => 'LIC. MINISTERIO PÚBLICO',
            'autoridad_receptora' => 'FISCALÍA REGIONAL',
            'area' => 'UNIDAD DE INVESTIGACIÓN',
            'carpeta_investigacion' => 'CI-2042-99',
            'oficio' => 'OF-2042-77',
            'fecha_puesta' => '2042-08-03',
            'hora_puesta' => '14:35:00',
            'lugar_puesta' => 'MORELIA',
            'narrativa' => 'NARRATIVA COMPLETA DE PRUEBA',
            'observaciones' => 'OBSERVACIÓN GENERAL DE PRUEBA',
            'unidad_id' => 4,
            'personal_participante' => 'ELEMENTO DE APOYO',
            'rnd' => 'RND-2042-55',
            'numero_detenidos' => 1,
            'numero_aseguramientos' => 2,
            'detenidos_descripcion' => 'UNA PERSONA DETENIDA',
            'archivo_puesta' => 'puestas_disposicion/2042/iph-prueba.pdf',
            'archivo_uso_fuerza' => 'puestas_disposicion/uso-fuerza-prueba.pdf',
        ]);

        $puesta->personas()->create([
            'nombre_completo' => 'PERSONA DETENIDA PRUEBA',
            'alias' => 'EL PRUEBA',
            'edad' => 31,
            'sexo' => 'MASCULINO',
            'fecha_nacimiento' => '2011-01-02',
            'curp' => 'CURP-DETENIDO-PRUEBA',
            'rfc' => 'RFC-DETENIDO',
            'domicilio' => 'DOMICILIO DE PRUEBA',
            'calidad' => 'DETENIDO',
            'delito_o_motivo' => 'ROBO',
            'orden_aprehension' => true,
            'mandamiento_judicial' => 'MANDAMIENTO-55',
            'observaciones' => 'OBSERVACIÓN DE PERSONA',
            'archivo_uso_fuerza' => 'puestas_disposicion/uso-fuerza-persona-prueba.pdf',
        ]);
        $puesta->vehiculos()->create([
            'tipo' => 'CAMIONETA',
            'marca' => 'MARCA PRUEBA',
            'submarca' => 'LÍNEA PRUEBA',
            'modelo' => '2040',
            'color' => 'AZUL',
            'placas' => 'ABC-2042',
            'serie' => 'SERIE-VEHÍCULO-2042',
            'calidad' => 'ASEGURADO',
            'motivo_relacion' => 'INSTRUMENTO DEL DELITO',
            'con_reporte_robo' => true,
            'numero_reporte_robo' => 'REPORTE-ROBO-77',
            'observaciones' => 'OBSERVACIÓN DE VEHÍCULO',
        ]);
        $puesta->objetos()->create([
            'tipo_objeto' => 'ARMA',
            'descripcion' => 'OBJETO ASEGURADO DE PRUEBA',
            'cantidad' => 2,
            'unidad_medida' => 'PIEZAS',
            'cadena_custodia' => 'CADENA-2042-88',
            'observaciones' => 'OBSERVACIÓN DE OBJETO',
        ]);
        $puesta->fotos()->create([
            'ruta' => 'puestas_disposicion/2042/foto-prueba.jpg',
            'orden' => 0,
        ]);

        $packet = $this->service()->executeOpenAI(
            $this->usuarioCarreteras(),
            $this->contextoCarreteras(),
            [
                'accion' => 'detalle_puesta_disposicion',
                'id' => $puesta->id,
                'unidad_id' => 1,
            ]
        );

        $this->assertStringContainsString('CI-2042-99', $packet['text']);
        $this->assertStringContainsString('NARRATIVA COMPLETA DE PRUEBA', $packet['text']);
        $this->assertStringContainsString('PERSONA DETENIDA PRUEBA', $packet['text']);
        $this->assertStringContainsString('CURP-DETENIDO-PRUEBA', $packet['text']);
        $this->assertStringContainsString('ABC-2042', $packet['text']);
        $this->assertStringContainsString('REPORTE-ROBO-77', $packet['text']);
        $this->assertStringContainsString('OBJETO ASEGURADO DE PRUEBA', $packet['text']);
        $this->assertStringContainsString('CADENA-2042-88', $packet['text']);
        $this->assertStringContainsString('PERSONAS (01)', $packet['text']);
        $this->assertCount(3, $packet['documents']);
        $this->assertSame('Puesta_disposicion_' . $numero . '_2042.pdf', $packet['documents'][0]['filename']);
        $this->assertCount(1, $packet['images']);
        $this->assertStringContainsString('/hechos-fotos/archivo-temporal/', $packet['images'][0]);
        $this->assertStringContainsString('signature=', $packet['images'][0]);
    }

    public function test_rendimiento_de_carreteras_incluye_resultados_y_respeta_la_unidad(): void
    {
        $fecha = '2042-08-02';
        $propia = $this->crearPuesta(4, $fecha, 'ELEMENTO PRODUCTIVO CARRETERAS', 2);
        $this->crearPuesta(1, $fecha, 'ELEMENTO AJENO SINIESTROS', 4);

        PuestaDisposicion::query()->whereKey($propia)->update([
            'numero_detenidos' => 2,
            'numero_faltas_administrativas' => 1,
            'numero_aseguramientos' => 3,
            'motivo' => 'DELITO CONTRA LA SALUD',
        ]);

        $packet = $this->service()->executeOpenAI(
            $this->usuarioCarreteras(),
            $this->contextoCarreteras(),
            [
                'accion' => 'rendimiento_carreteras',
                'unidad_id' => 1,
                'filtros' => ['fecha' => $fecha],
            ]
        );

        $this->assertStringContainsString('Rendimiento operativo', $packet['text']);
        $this->assertStringContainsString('ELEMENTO PRODUCTIVO CARRETERAS', $packet['text']);
        $this->assertStringContainsString('Aseguramientos: 03', $packet['text']);
        $this->assertStringNotContainsString('ELEMENTO AJENO SINIESTROS', $packet['text']);
    }

    public function test_superadmin_puede_consultar_rendimiento_de_carreteras_desde_el_menu(): void
    {
        $fecha = now()->startOfMonth()->toDateString();
        $this->crearPuesta(4, $fecha, 'ELEMENTO VISIBLE PARA SUPERADMIN', 1);

        $user = new User();
        $user->id = 1;
        $user->unidad_id = 1;

        $packet = $this->service()->executeQuickStat(
            $user,
            [
                'acceso_total' => true,
                'modules' => ['siniestros', 'delegaciones', 'coordinacion', 'carreteras', 'vialidades'],
                'default_module' => null,
                'unidad_id' => 1,
            ],
            'rendimiento_carreteras',
            'este_mes',
            []
        );

        $this->assertStringContainsString('Rendimiento operativo', $packet['text']);
        $this->assertStringContainsString('ELEMENTO VISIBLE PARA SUPERADMIN', $packet['text']);
        $this->assertStringNotContainsString('disponible únicamente', $packet['text']);
    }

    public function test_incapacidades_de_carreteras_muestra_dias_y_no_filtra_otra_unidad(): void
    {
        $personal = Personal::query()->create([
            'unidad_id' => 4,
            'nombre' => 'MARIA',
            'ap_paterno' => 'PRUEBA',
            'ap_materno' => 'INCAPACIDAD',
            'numero_empleado' => 'WA-INC-2042',
            'estatus' => 'ACTIVO',
        ]);
        $tipoId = DB::table('incidencia_tipos')->insertGetId([
            'clave' => 'INCAPACIDAD',
            'nombre' => 'INCAPACIDAD',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('personal_incidencias')->insert([
            'personal_id' => $personal->id,
            'incidencia_tipo_id' => $tipoId,
            'fecha_inicio' => '2042-08-01',
            'fecha_fin' => '2042-08-05',
            'folio' => 'INC-55',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $packet = $this->service()->executeQuickStat(
            $this->usuarioCarreteras(),
            $this->contextoCarreteras(),
            'incapacidades_carreteras',
            'este_mes',
            []
        );

        $directo = $this->service()->executeOpenAI(
            $this->usuarioCarreteras(),
            $this->contextoCarreteras(),
            [
                'accion' => 'incapacidades_carreteras',
                'unidad_id' => 1,
                'filtros' => ['fecha_inicio' => '2042-08-01', 'fecha_fin' => '2042-08-31'],
            ]
        );

        $this->assertIsArray($packet);
        $this->assertStringContainsString('PRUEBA INCAPACIDAD MARIA', $directo['text']);
        $this->assertStringContainsString('Días 05', $directo['text']);
        $this->assertStringContainsString('Con folio 01', $directo['text']);
    }

    public function test_acceso_de_carreteras_exige_administrador_o_subdirector_de_la_unidad(): void
    {
        Role::findOrCreate('Administrador');
        Role::findOrCreate('Agente Upec');
        $admin = User::factory()->create(['unidad_id' => 4]);
        $admin->assignRole('Administrador');
        $agente = User::factory()->create(['unidad_id' => 4]);
        $agente->assignRole('Agente Upec');
        $resolver = new WhatsAppUserResolverService();

        $this->assertSame(['carreteras'], $resolver->resolveContext($admin->fresh(['unidad', 'roles']))['modules']);
        $this->assertSame([], $resolver->resolveContext($agente->fresh(['unidad', 'roles']))['modules']);
    }

    private function service(): WhatsAppQueryService
    {
        return new WhatsAppQueryService(
            new WhatsAppRenderService(),
            new WhatsAppMenuService()
        );
    }

    private function usuarioCarreteras(): User
    {
        $user = new User();
        $user->id = 900042;
        $user->unidad_id = 4;

        return $user;
    }

    private function contextoCarreteras(): array
    {
        return [
            'acceso_total' => false,
            'modules' => ['carreteras'],
            'default_module' => 'carreteras',
            'unidad_id' => 4,
        ];
    }

    private function crearPuesta(int $unidadId, string $fecha, string $elemento, int $cantidad): int
    {
        $anio = (int) substr($fecha, 0, 4);
        $numeroBase = (int) PuestaDisposicion::query()
            ->where('anio', $anio)
            ->where('unidad_id', $unidadId)
            ->max('numero_puesta') + 100;

        for ($i = 0; $i < $cantidad; $i++) {
            $puesta = PuestaDisposicion::query()->create([
                'numero_puesta' => $numeroBase + $i,
                'anio' => $anio,
                'tipo_puesta' => 'PERSONA',
                'motivo' => 'PERSONA DETENIDA',
                'estatus' => 'ACTIVA',
                'nombre_policia' => $elemento,
                'area' => $unidadId === 4 ? 'CARRETERAS' : 'SINIESTROS',
                'fecha_puesta' => $fecha,
                'unidad_id' => $unidadId,
            ]);
        }

        return (int) $puesta->id;
    }
}
