<?php

namespace App\Services\WhatsApp;

use App\Models\Hechos;

class WhatsAppQueryService
{
    protected WhatsAppRenderService $renderService;

    public function __construct(WhatsAppRenderService $renderService)
    {
        $this->renderService = $renderService;
    }

    public function executeImmediate($user, array $context, string $module, string $action): array
    {
        if ($module === 'siniestros' && $action === 'hechos_hoy') {
            return $this->hechosHoy($user, false);
        }

        if ($module === 'siniestros' && $action === 'mis_hechos_hoy') {
            return $this->hechosHoy($user, true);
        }

        return [
            'text' => 'Esa opción todavía no está disponible.',
        ];
    }

    public function executeWithParam($user, array $context, string $module, string $action, string $paramType, string $value): array
    {
        if ($module !== 'siniestros') {
            return [
                'text' => 'Ese módulo todavía no está disponible.',
            ];
        }

        if ($action === 'hechos_placas') {
            return $this->buscarHechosPorPlaca($user, $value, false);
        }

        if ($action === 'mis_hechos_placas') {
            return $this->buscarHechosPorPlaca($user, $value, true);
        }

        if ($action === 'detalle_folio') {
            return $this->detallePorFolio($user, $value, false);
        }

        if ($action === 'mi_detalle_folio') {
            return $this->detallePorFolio($user, $value, true);
        }

        return [
            'text' => 'No pude procesar esa consulta.',
        ];
    }

    protected function hechosHoy($user, bool $soloPropios): array
    {
        $query = Hechos::query()
            ->orderByDesc('fecha')
            ->orderByDesc('hora');

        if ($soloPropios) {
            $query->where('user_id', $user->id);
        }

        $hoy = now()->toDateString();

        $hechos = $query
            ->whereDate('fecha', $hoy)
            ->limit(15)
            ->get();

        if ($hechos->isEmpty()) {
            return [
                'text' => $soloPropios ? 'No encontré hechos tuyos el día de hoy.' : 'No encontré hechos el día de hoy.',
            ];
        }

        $lineas = [];
        $lineas[] = $soloPropios ? 'Tus hechos de hoy:' : 'Hechos de hoy:';
        $lineas[] = '';

        foreach ($hechos as $hecho) {
            $lineas[] = ($hecho->folio_c5i ?: $hecho->id) . ' | ' . ((string) $hecho->fecha) . ' ' . substr((string) $hecho->hora, 0, 5) . ' | ' . ($hecho->tipo_hecho ?: 'SIN TIPO') . ' | ' . ($hecho->situacion ?: 'SIN ESTADO');
        }

        return [
            'text' => implode("\n", $lineas),
        ];
    }

    protected function buscarHechosPorPlaca($user, string $placa, bool $soloPropios): array
    {
        $placaNormalizada = $this->normalizarPlaca($placa);

        $query = Hechos::query()
            ->with(['vehiculos'])
            ->whereHas('vehiculos', function ($q) use ($placaNormalizada) {
                $q->whereRaw(
                    "REPLACE(REPLACE(REPLACE(UPPER(placas), '-', ''), ' ', ''), '.', '') = ?",
                    [$placaNormalizada]
                );
            })
            ->orderByDesc('fecha')
            ->orderByDesc('hora');

        if ($soloPropios) {
            $query->where('user_id', $user->id);
        }

        $hechos = $query->limit(10)->get();

        if ($hechos->isEmpty()) {
            return [
                'text' => $soloPropios
                    ? "No encontré hechos tuyos con las placas {$placa}."
                    : "No encontré hechos con las placas {$placa}.",
            ];
        }

        $lineas = [];
        $lineas[] = $soloPropios
            ? 'Encontré ' . $hechos->count() . " hecho(s) tuyos con las placas {$placa}:"
            : 'Encontré ' . $hechos->count() . " hecho(s) con las placas {$placa}:";
        $lineas[] = '';

        foreach ($hechos as $hecho) {
            $lineas[] = ($hecho->folio_c5i ?: $hecho->id) . ' | ' . ((string) $hecho->fecha) . ' ' . substr((string) $hecho->hora, 0, 5) . ' | ' . ($hecho->tipo_hecho ?: 'SIN TIPO') . ' | ' . ($hecho->situacion ?: 'SIN ESTADO');
        }

        return [
            'text' => implode("\n", $lineas),
        ];
    }

    protected function detallePorFolio($user, string $folio, bool $soloPropios): array
    {
        $query = Hechos::query()
            ->with(['vehiculos'])
            ->where(function ($q) use ($folio) {
                $q->where('id', $folio)
                    ->orWhere('folio_c5i', $folio);
            });

        if ($soloPropios) {
            $query->where('user_id', $user->id);
        }

        $hecho = $query->first();

        if (!$hecho) {
            return [
                'text' => $soloPropios
                    ? "No encontré un hecho tuyo con el folio {$folio}."
                    : "No encontré el hecho {$folio}.",
            ];
        }

        return $this->renderService->renderDetalleHecho($hecho);
    }

    protected function normalizarPlaca(string $placa): string
    {
        $placa = mb_strtoupper(trim($placa), 'UTF-8');
        $placa = str_replace(['-', ' ', '.'], '', $placa);

        return $placa;
    }
}
