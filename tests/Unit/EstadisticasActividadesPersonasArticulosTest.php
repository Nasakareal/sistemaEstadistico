<?php

namespace Tests\Unit;

use App\Http\Controllers\EstadisticasActividadesController;
use App\Models\Actividad;
use App\Models\ActividadCategoria;
use App\Models\LicenciaPuntoInfraccion;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

class EstadisticasActividadesPersonasArticulosTest extends TestCase
{
    use DatabaseTransactions;

    public function test_edad_filtra_actividades_y_genera_estadisticas_por_articulo(): void
    {
        $user = User::factory()->create(['unidad_id' => 5]);
        Auth::login($user);

        $categoria = ActividadCategoria::query()->firstOrFail();
        $fundamento = LicenciaPuntoInfraccion::query()
            ->where('activa', true)
            ->whereNotNull('articulo')
            ->firstOrFail();
        $marca = 'RECOMENDACION ESTADISTICA ' . Str::upper(Str::random(12));

        $joven = $this->crearActividad($user, $categoria, $fundamento, $marca . ' JOVEN');
        $joven->personas()->create([
            'tipo_participacion' => 'CONDUCTOR',
            'nombre' => 'PERSONA RECOMENDADA JOVEN',
            'edad' => 25,
        ]);

        $adulta = $this->crearActividad($user, $categoria, $fundamento, $marca . ' ADULTA');
        $adulta->personas()->create([
            'tipo_participacion' => 'CONDUCTOR',
            'nombre' => 'PERSONA RECOMENDADA ADULTA',
            'edad' => 50,
        ]);

        $request = Request::create('/estadisticas-actividades/actividades', 'GET', [
            'desde' => now()->subDay()->toDateString(),
            'hasta' => now()->addDay()->toDateString(),
            'edad_min' => 18,
            'edad_max' => 29,
            'articulo' => $fundamento->articulo,
            'q' => $marca,
            'cache_ttl' => 0,
        ]);
        $request->setUserResolver(fn () => $user);

        $controller = new EstadisticasActividadesController();
        $actividades = $controller->actividades($request)->getData(true);
        $edades = $controller->seriesActividadPersonasEdad($request)->getData(true);
        $articulos = $controller->seriesArticulos($request)->getData(true);
        $kpis = $controller->kpis($request)->getData(true);

        $this->assertSame(1, $actividades['total']);
        $this->assertSame($joven->id, $actividades['data'][0]['id']);
        $this->assertSame(['PERSONA RECOMENDADA JOVEN (25)'], $actividades['data'][0]['personas_resumen']);
        $this->assertContains('Artículo ' . $fundamento->articulo, $actividades['data'][0]['articulos_resumen']);
        $this->assertSame(1, $edades['total']);
        $this->assertSame(1, collect($edades['series'])->firstWhere('label', '18-29')['total']);
        $this->assertSame(1, $articulos['series'][0]['reportes']);
        $this->assertSame(1, $articulos['series'][0]['personas']);
        $this->assertSame(1, $kpis['totales']['actividades']);
        $this->assertSame(1, $kpis['totales']['personas_en_actividades']);
        $this->assertNotSame($adulta->id, $actividades['data'][0]['id']);
        $this->assertNotNull(app('router')->getRoutes()->getByName('estadisticas_actividades.series.articulos'));
        $this->assertNotNull(app('router')->getRoutes()->getByName('estadisticas_actividades.series.personas_actividad_edades'));
    }

    private function crearActividad(
        User $user,
        ActividadCategoria $categoria,
        LicenciaPuntoInfraccion $fundamento,
        string $nombre
    ): Actividad {
        return Actividad::query()->create([
            'actividad_categoria_id' => $categoria->id,
            'nombre' => $nombre,
            'cantidad' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'unidad_org_id' => 5,
            'fecha' => now()->toDateString(),
            'hora' => now()->format('H:i:s'),
            'infracciones_actividad' => [[
                'id' => $fundamento->id,
                'codigo' => $fundamento->codigo,
                'referencia_legal_corta' => $fundamento->referencia_legal_corta,
            ]],
        ]);
    }
}
