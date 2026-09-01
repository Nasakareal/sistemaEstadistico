<?php

namespace Tests\Unit;

use App\Http\Controllers\EstadisticasActividadesController;
use App\Models\Dictamen;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EstadisticasActividadesDictamenesLayoutTest extends TestCase
{
    use DatabaseTransactions;

    public function test_incluye_dictamenes_de_siniestros_en_las_puestas(): void
    {
        $user = User::factory()->create(['unidad_id' => 1]);
        Auth::login($user);

        $marca = 'AGENTE DICTAMEN TABLERO ' . uniqid();
        $dictamen = Dictamen::query()->create([
            'numero_dictamen' => ((int) Dictamen::query()->where('anio', now()->year)->max('numero_dictamen')) + 1000,
            'anio' => now()->year,
            'nombre_policia' => $marca,
            'nombre_mp' => 'MP DE PRUEBA',
            'area' => 'SINIESTROS',
            'created_by' => $user->id,
        ]);

        $request = Request::create('/estadisticas-actividades/puestas-disposicion', 'GET', [
            'desde' => now()->subDay()->toDateString(),
            'hasta' => now()->addDay()->toDateString(),
            'unidad_org_id' => 1,
            'q' => $marca,
            'cache_ttl' => 0,
        ]);
        $request->setUserResolver(fn () => $user);

        $controller = new EstadisticasActividadesController();
        $puestas = $controller->puestasDisposicion($request)->getData(true);
        $kpis = $controller->kpis($request)->getData(true);

        $this->assertSame(1, $puestas['total']);
        $this->assertSame('dictamen', $puestas['data'][0]['origen']);
        $this->assertSame($dictamen->id, $puestas['data'][0]['source_id']);
        $this->assertSame(1, $kpis['totales']['puestas_disposicion']);
        $this->assertSame(0, $kpis['totales']['personas_en_puestas']);
    }

    public function test_guarda_el_orden_de_graficas_por_usuario(): void
    {
        $user = User::factory()->create(['unidad_id' => 1]);
        Auth::login($user);
        $order = [
            'actividades_filtradas',
            'resumen_categorias',
            'actividades_edades',
            'actividades_articulos',
            'personas_articulos',
            'puestas_edades',
            'actividades_categoria',
            'actividades_unidad',
            'actividades_tiempo',
            'puestas_filtradas',
        ];

        $request = Request::create('/estadisticas-actividades/preferencias/graficas', 'POST', [
            'order' => $order,
        ]);
        $request->setUserResolver(fn () => $user);

        $response = (new EstadisticasActividadesController())->updateChartLayout($request);

        $this->assertSame(['saved' => true], $response->getData(true));
        $this->assertSame(
            $order,
            json_decode((string) DB::table('user_dashboard_preferences')
                ->where('user_id', $user->id)
                ->where('dashboard', 'estadisticas_actividades')
                ->value('layout'), true)
        );
    }

    public function test_todos_los_bloques_visibles_son_reordenables(): void
    {
        $view = (string) file_get_contents(
            resource_path('views/estadisticas_actividades/index.blade.php')
        );

        foreach ([
            'actividades_tiempo',
            'actividades_unidad',
            'actividades_categoria',
            'resumen_categorias',
            'actividades_filtradas',
            'actividades_edades',
            'actividades_articulos',
            'personas_articulos',
            'puestas_edades',
            'puestas_filtradas',
        ] as $block) {
            $this->assertStringContainsString(
                'data-dashboard-block="' . $block . '"',
                $view
            );
        }

        $this->assertStringNotContainsString(
            "event.target.closest('.sv-panel__title')",
            $view,
            'El evento dragstart se dispara sobre la tarjeta y no debe cancelarse por su elemento hijo.'
        );
    }

    public function test_completa_preferencias_antiguas_que_solo_tenian_graficas(): void
    {
        $user = User::factory()->create(['unidad_id' => 1]);
        Auth::login($user);
        $oldOrder = [
            'puestas_edades',
            'actividades_categoria',
            'actividades_unidad',
            'actividades_tiempo',
        ];

        DB::table('user_dashboard_preferences')->insert([
            'user_id' => $user->id,
            'dashboard' => 'estadisticas_actividades',
            'layout' => json_encode($oldOrder),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/estadisticas-actividades', 'GET');
        $request->setUserResolver(fn () => $user);
        $view = (new EstadisticasActividadesController())->index($request);
        $order = $view->getData()['chartOrder'];

        $this->assertSame($oldOrder, array_slice($order, 0, 4));
        $this->assertSame([
            'resumen_categorias',
            'actividades_filtradas',
            'actividades_edades',
            'actividades_articulos',
            'personas_articulos',
            'puestas_filtradas',
        ], array_values(array_diff($order, $oldOrder)));
        $this->assertCount(10, $order);
    }
}
