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
