<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\EstadisticasCarreterasController;
use App\Http\Controllers\EstadisticasDelegacionesSettingsController;
use App\Http\Controllers\EstadisticasFomentoSettingsController;
use App\Http\Controllers\RendimientoPeritosController;
use App\Http\Controllers\ResumenEjecutivoController;
use App\Services\Inegi\InegiChoquesSelectionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EstadisticasReportesController extends Controller
{
    private const REPORT_UNITS = [
        'siniestros-rendimiento-peritos' => 1,
        'siniestros-resumen-ejecutivo' => 1,
        'delegaciones-actividades-fisicas' => 2,
        'delegaciones-control-inegi' => 2,
        'carreteras-concentrado' => 4,
        'carreteras-panel' => 4,
        'carreteras-elementos' => 4,
        'carreteras-rendimiento' => 4,
        'carreteras-incapacidades' => 4,
        'fomento-panel' => 6,
        'fomento-servicios-personal' => 6,
    ];

    public function show(Request $request, string $reporte): JsonResponse
    {
        abort_unless(array_key_exists($reporte, self::REPORT_UNITS), 404);
        $this->authorizeReport($request, $reporte);

        $payload = match ($reporte) {
            'siniestros-rendimiento-peritos' => $this->viewData(
                app(RendimientoPeritosController::class)->index($request)
            ),
            'siniestros-resumen-ejecutivo' => $this->responseData(
                app(ResumenEjecutivoController::class)->data(
                    (string) $request->query('fecha', now('America/Mexico_City')->toDateString())
                )
            ),
            'delegaciones-actividades-fisicas' => $this->viewData(
                app(EstadisticasDelegacionesSettingsController::class)->actividadesFisicas($request)
            ),
            'delegaciones-control-inegi' => $this->viewData(
                app(EstadisticasDelegacionesSettingsController::class)->controlInegi(
                    $request,
                    app(InegiChoquesSelectionService::class)
                )
            ),
            'carreteras-concentrado' => $this->viewData(
                app(EstadisticasCarreterasController::class)->concentrado($request)
            ),
            'carreteras-panel' => $this->carreterasPanel($request),
            'carreteras-elementos' => $this->viewData(
                app(EstadisticasCarreterasController::class)->elementos($request)
            ),
            'carreteras-rendimiento' => $this->viewData(
                app(EstadisticasCarreterasController::class)->rendimiento($request)
            ),
            'carreteras-incapacidades' => $this->viewData(
                app(EstadisticasCarreterasController::class)->incapacidades($request)
            ),
            'fomento-panel' => $this->fomentoPanel($request),
            'fomento-servicios-personal' => $this->viewData(
                app(EstadisticasFomentoSettingsController::class)->serviciosPersonal($request)
            ),
        };

        return response()->json($this->normalize($payload));
    }

    private function carreterasPanel(Request $request): array
    {
        $controller = app(EstadisticasCarreterasController::class);

        return [
            'kpis' => $this->responseData($controller->kpis($request)),
            'actividades' => $this->responseData($controller->seriesActividades($request)),
            'operativos' => $this->responseData($controller->seriesOperativos($request)),
            'puestas_disposicion' => $this->responseData($controller->seriesPuestasDisposicion($request)),
        ];
    }

    private function fomentoPanel(Request $request): array
    {
        $controller = app(EstadisticasFomentoSettingsController::class);

        return [
            'municipios_atendidos' => $this->viewData($controller->municipiosAtendidos($request)),
            'servicios_personal' => $this->viewData($controller->serviciosPersonal($request)),
        ];
    }

    private function viewData($response): array
    {
        abort_unless($response instanceof View, 500, 'El reporte no devolvió una vista válida.');

        return $response->getData();
    }

    private function responseData($response): array
    {
        if ($response instanceof JsonResponse) {
            return (array) $response->getData(true);
        }

        if ($response instanceof Response) {
            return (array) json_decode((string) $response->getContent(), true);
        }

        return (array) $response;
    }

    private function normalize($value)
    {
        return json_decode(json_encode($value, JSON_UNESCAPED_UNICODE), true);
    }

    private function authorizeReport(Request $request, string $reporte): void
    {
        $user = $request->user();
        abort_unless($user, 403);

        $unitId = (int) ($user->unidad_id ?? 0);
        $requiredUnit = self::REPORT_UNITS[$reporte];
        $hasUnitScope = $user->hasRole('Superadmin')
            || $unitId === 3
            || $unitId === $requiredUnit;
        abort_unless($hasUnitScope, 403, 'Este reporte pertenece a otra unidad.');

        $allowed = match ($requiredUnit) {
            1 => $user->can('ver estadisticas globales') || $user->can('ver estadisticas'),
            2 => $user->can('menu-estadisticas-delegaciones') || $user->can('ver estadisticas actividades'),
            4 => $user->can('ver estadisticas carreteras'),
            6 => $user->can('menu-estadisticas-actividades-fomento') || $user->can('ver estadisticas actividades'),
            default => false,
        };

        abort_unless($allowed, 403, 'No tienes permiso para consultar este reporte.');
    }
}
