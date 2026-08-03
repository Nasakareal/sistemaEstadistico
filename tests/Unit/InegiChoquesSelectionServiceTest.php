<?php

namespace Tests\Unit;

use App\Services\Inegi\InegiChoquesSelectionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InegiChoquesSelectionServiceTest extends TestCase
{
    public function test_shared_filter_contains_the_real_inegi_inclusion_rule(): void
    {
        $query = DB::table('hechos as h')
            ->leftJoin('users as creator', 'creator.id', '=', 'h.created_by');

        (new InegiChoquesSelectionService())->aplicarFiltroIncluidos($query);

        $sql = $query->toSql();

        $this->assertStringContainsString('COALESCE(h.unidad_org_id, creator.unidad_id) IS NULL', $sql);
        $this->assertStringContainsString('COALESCE(h.unidad_org_id, creator.unidad_id) <> ?', $sql);
        $this->assertStringContainsString('`h`.`captura_completa` = ?', $sql);
        $this->assertStringContainsString('`h`.`vehiculos_capturados` >= `h`.`vehiculos_esperados`', $sql);
        $this->assertStringContainsString('`h`.`conductores_capturados` >= `h`.`conductores_esperados`', $sql);
        $this->assertStringContainsString('`h`.`lesionados_capturados` >= `h`.`lesionados_esperados`', $sql);
        $this->assertSame([InegiChoquesSelectionService::UNIDAD_DELEGACIONES_ID, 1], $query->getBindings());
    }

    public function test_delegaciones_preview_uses_the_month_range_and_completion_counts(): void
    {
        $service = new InegiChoquesSelectionService();
        $query = $service->queryDelegacionesIncluidas(
            Carbon::parse('2026-07-01'),
            Carbon::parse('2026-07-31')
        );

        $sql = $query->toSql();

        $this->assertStringContainsString('COALESCE(h.unidad_org_id, creator_inegi.unidad_id) = ?', $sql);
        $this->assertStringContainsString('date(`h`.`fecha`) >= ?', $sql);
        $this->assertStringContainsString('date(`h`.`fecha`) <= ?', $sql);
        $this->assertStringContainsString('`h`.`captura_completa` = ?', $sql);
        $this->assertSame([2, '2026-07-01', '2026-07-31', 1], $query->getBindings());
    }

    public function test_pending_preview_is_the_complement_for_incomplete_delegaciones_records(): void
    {
        $service = new InegiChoquesSelectionService();
        $sql = $service->queryDelegacionesPendientes(
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-31')
        )->toSql();

        $this->assertStringContainsString('COALESCE(h.captura_completa, 0) <> 1', $sql);
        $this->assertStringContainsString('COALESCE(h.vehiculos_capturados, 0) < COALESCE(h.vehiculos_esperados, 0)', $sql);
        $this->assertStringContainsString('COALESCE(h.conductores_capturados, 0) < COALESCE(h.conductores_esperados, 0)', $sql);
        $this->assertStringContainsString('COALESCE(h.lesionados_capturados, 0) < COALESCE(h.lesionados_esperados, 0)', $sql);
    }

    public function test_control_page_has_a_direct_statistics_route_outside_settings(): void
    {
        $route = route('estadisticas_delegaciones.control_inegi', [], false);

        $this->assertSame('/estadisticas-delegaciones/control-inegi', $route);
    }
}
