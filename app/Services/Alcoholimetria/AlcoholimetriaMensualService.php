<?php

namespace App\Services\Alcoholimetria;

use App\Models\BoquillaDotacion;
use App\Models\BoquillaPerdida;
use App\Models\ConduceLegalidadCaptura;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AlcoholimetriaMensualService
{
    private const TIPOS_VEHICULO = [
        'automovil',
        'motocicleta',
        'transporte_publico',
    ];

    private const SEXOS = [
        'hombres',
        'mujeres',
    ];

    public function resumen(Carbon $mes): array
    {
        $mes = $mes->copy()->startOfMonth();
        $inicio = $mes->toDateString();
        $fin = $mes->copy()->endOfMonth()->toDateString();

        $capturaIds = DB::table('conduce_legalidad_capturas as c')
            ->join('conduce_legalidad_operativos as o', 'o.id', '=', 'c.operativo_id')
            ->where('o.tipo_operativo', 'alcoholimetria')
            ->whereRaw('COALESCE(c.fecha, o.fecha) BETWEEN ? AND ?', [$inicio, $fin])
            ->pluck('c.id');

        $capturas = ConduceLegalidadCaptura::query()
            ->with([
                'operativo:id,tipo_operativo,fecha,municipio',
                'personas:id,captura_id,sexo',
                'vehiculos:id,captura_id,tipo_general,tipo,tipo_servicio',
            ])
            ->whereIn('id', $capturaIds)
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        $perdidas = BoquillaPerdida::query()
            ->whereBetween('fecha_perdida', [$inicio, $fin])
            ->orderBy('fecha_perdida')
            ->get(['fecha_perdida', 'cantidad']);

        $recibidasMes = (int) BoquillaDotacion::query()
            ->whereBetween('fecha_recepcion', [$inicio, $fin])
            ->sum('cantidad');

        $recibidasAntes = (int) BoquillaDotacion::query()
            ->where('fecha_recepcion', '<', $inicio)
            ->sum('cantidad');

        $perdidasAntes = (int) BoquillaPerdida::query()
            ->where('fecha_perdida', '<', $inicio)
            ->sum('cantidad');

        $pruebasAntes = (int) DB::table('conduce_legalidad_capturas as c')
            ->join('conduce_legalidad_operativos as o', 'o.id', '=', 'c.operativo_id')
            ->where('o.tipo_operativo', 'alcoholimetria')
            ->whereRaw('COALESCE(c.fecha, o.fecha) < ?', [$inicio])
            ->count('c.id');

        $existenciaInicial = $recibidasAntes - $pruebasAntes - $perdidasAntes;
        $municipio = $this->municipioDelMes($mes, $capturas);

        return $this->construirResumen(
            $mes,
            $capturas,
            $perdidas,
            $existenciaInicial,
            $recibidasMes,
            $municipio
        );
    }

    public function construirResumen(
        Carbon $mes,
        Collection $capturas,
        Collection $perdidas,
        int $existenciaInicial,
        int $recibidasMes,
        string $municipio
    ): array {
        $mes = $mes->copy()->startOfMonth();
        $pruebasRealesSemana = array_fill(1, 5, 0);
        $perdidasSemana = array_fill(1, 5, 0);
        $conductores = $this->matrizConductoresVacia();

        foreach ($capturas as $captura) {
            $fecha = $captura->fecha ?: optional($captura->operativo)->fecha;
            $semana = $this->semanaDelMes($fecha ? Carbon::parse($fecha) : $mes);
            $pruebasRealesSemana[$semana]++;

            foreach ($captura->personas->values() as $indice => $persona) {
                $vehiculo = $captura->vehiculos->values()->get($indice)
                    ?: $captura->vehiculos->first();
                $tipoVehiculo = $this->tipoVehiculo($vehiculo);
                $sexo = $this->sexo($persona->sexo ?? null);
                $conductores[$tipoVehiculo][$sexo][$semana]++;
            }
        }

        foreach ($perdidas as $perdida) {
            $semana = $this->semanaDelMes(Carbon::parse($perdida->fecha_perdida));
            $perdidasSemana[$semana] += max(0, (int) $perdida->cantidad);
        }

        $pruebasReportadasSemana = [];
        for ($semana = 1; $semana <= 5; $semana++) {
            $pruebasReportadasSemana[$semana] =
                $pruebasRealesSemana[$semana] + $perdidasSemana[$semana];
        }

        $pruebasReales = array_sum($pruebasRealesSemana);
        $boquillasPerdidas = array_sum($perdidasSemana);
        $boquillasUtilizadas = $pruebasReales + $boquillasPerdidas;
        $existenciaFinal = $existenciaInicial + $recibidasMes - $boquillasUtilizadas;
        $variables = $this->variables(
            $mes,
            $municipio,
            $pruebasReportadasSemana,
            $conductores,
            $existenciaInicial,
            $recibidasMes,
            $boquillasUtilizadas,
            $existenciaFinal
        );

        $noAptos = (int) $variables['tcna'];

        return [
            'mes' => $mes->toDateString(),
            'municipio' => $municipio,
            'pruebas_reales' => $pruebasReales,
            'pruebas_reportadas' => array_sum($pruebasReportadasSemana),
            'conductores_no_aptos' => $noAptos,
            'conductores_aptos_reales' => max(0, $pruebasReales - $noAptos),
            'conductores_aptos_reportados' => max(
                0,
                array_sum($pruebasReportadasSemana) - $noAptos
            ),
            'ajuste_aptos_por_boquillas_perdidas' => $boquillasPerdidas,
            'boquillas' => [
                'existencia_inicial' => $existenciaInicial,
                'recibidas' => $recibidasMes,
                'utilizadas_en_pruebas' => $pruebasReales,
                'perdidas' => $boquillasPerdidas,
                'salidas_totales' => $boquillasUtilizadas,
                'existencia_final' => $existenciaFinal,
            ],
            'variables' => $variables,
        ];
    }

    private function variables(
        Carbon $mes,
        string $municipio,
        array $pruebasSemana,
        array $conductores,
        int $existenciaInicial,
        int $recibidasMes,
        int $boquillasUtilizadas,
        int $existenciaFinal
    ): array {
        $variables = [
            'municipio' => Str::upper(trim($municipio)),
            'mes' => Str::upper($mes->locale('es')->translatedFormat('F')),
            'year' => $mes->format('Y'),
            'eim' => (string) $existenciaInicial,
            'rm' => (string) $recibidasMes,
            'um' => (string) $boquillasUtilizadas,
            'efm' => (string) $existenciaFinal,
        ];

        for ($semana = 1; $semana <= 5; $semana++) {
            $variables['ts' . $semana] = (string) $pruebasSemana[$semana];
            $variables['ahs' . $semana] = (string) $conductores['automovil']['hombres'][$semana];
            $variables['ams' . $semana] = (string) $conductores['automovil']['mujeres'][$semana];
            $variables['mhs' . $semana] = (string) $conductores['motocicleta']['hombres'][$semana];
            $variables['mms' . $semana] = (string) $conductores['motocicleta']['mujeres'][$semana];
            $variables['tpihs' . $semana] = (string) $conductores['transporte_publico']['hombres'][$semana];
            $variables['tpims' . $semana] = (string) $conductores['transporte_publico']['mujeres'][$semana];
            $variables['tvhs' . $semana] = (string) (
                $conductores['automovil']['hombres'][$semana]
                + $conductores['motocicleta']['hombres'][$semana]
                + $conductores['transporte_publico']['hombres'][$semana]
            );
            $variables['tvms' . $semana] = (string) (
                $conductores['automovil']['mujeres'][$semana]
                + $conductores['motocicleta']['mujeres'][$semana]
                + $conductores['transporte_publico']['mujeres'][$semana]
            );
        }

        $variables['tst'] = (string) array_sum($pruebasSemana);
        $variables['tahm'] = (string) array_sum($conductores['automovil']['hombres']);
        $variables['tamm'] = (string) array_sum($conductores['automovil']['mujeres']);
        $variables['tmhm'] = (string) array_sum($conductores['motocicleta']['hombres']);
        $variables['tmmm'] = (string) array_sum($conductores['motocicleta']['mujeres']);
        $variables['ttpihm'] = (string) array_sum($conductores['transporte_publico']['hombres']);
        $variables['ttpimm'] = (string) array_sum($conductores['transporte_publico']['mujeres']);
        $variables['thm'] = (string) array_sum(array_map('intval', [
            $variables['tahm'],
            $variables['tmhm'],
            $variables['ttpihm'],
        ]));
        $variables['tmm'] = (string) array_sum(array_map('intval', [
            $variables['tamm'],
            $variables['tmmm'],
            $variables['ttpimm'],
        ]));
        $variables['tcna'] = (string) ((int) $variables['thm'] + (int) $variables['tmm']);

        return $variables;
    }

    private function matrizConductoresVacia(): array
    {
        $matriz = [];

        foreach (self::TIPOS_VEHICULO as $tipo) {
            foreach (self::SEXOS as $sexo) {
                $matriz[$tipo][$sexo] = array_fill(1, 5, 0);
            }
        }

        return $matriz;
    }

    private function semanaDelMes(Carbon $fecha): int
    {
        return min(5, (int) ceil($fecha->day / 7));
    }

    private function tipoVehiculo($vehiculo): string
    {
        if (!$vehiculo) {
            return 'automovil';
        }

        $servicio = $this->normalizar(
            trim((string) ($vehiculo->tipo_servicio ?? ''))
        );
        $tipo = $this->normalizar(trim(
            (string) ($vehiculo->tipo_general ?: $vehiculo->tipo ?: '')
        ));

        if (Str::contains($servicio, ['PUBLICO', 'TAXI'])) {
            return 'transporte_publico';
        }

        if (Str::contains($tipo, ['MOTO', 'CUATRIMOTO'])) {
            return 'motocicleta';
        }

        return 'automovil';
    }

    private function sexo(?string $sexo): string
    {
        $sexo = $this->normalizar(trim((string) $sexo));

        return Str::contains($sexo, ['FEMEN', 'MUJER']) ? 'mujeres' : 'hombres';
    }

    private function municipioDelMes(Carbon $mes, Collection $capturas): string
    {
        $municipios = $capturas
            ->map(fn ($captura) => trim((string) optional($captura->operativo)->municipio))
            ->filter()
            ->map(fn ($municipio) => Str::upper($municipio))
            ->unique()
            ->values();

        if ($municipios->isEmpty()) {
            $municipios = DB::table('conduce_legalidad_operativos')
                ->where('tipo_operativo', 'alcoholimetria')
                ->whereBetween('fecha', [
                    $mes->toDateString(),
                    $mes->copy()->endOfMonth()->toDateString(),
                ])
                ->pluck('municipio')
                ->map(fn ($municipio) => Str::upper(trim((string) $municipio)))
                ->filter()
                ->unique()
                ->values();
        }

        if ($municipios->count() === 1) {
            return (string) $municipios->first();
        }

        if ($municipios->count() > 1) {
            return 'VARIOS MUNICIPIOS';
        }

        return Str::upper((string) config(
            'services.alcoholimetria_mensual.municipio_sin_registros',
            'MORELIA'
        ));
    }

    private function normalizar(string $texto): string
    {
        return Str::upper(Str::ascii($texto));
    }
}
