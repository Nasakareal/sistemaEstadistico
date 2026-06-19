<?php

namespace App\Services;

use App\Models\Hechos;
use App\Models\PuestaDisposicion;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class DelegacionesCorteAseguramientosWhatsAppService
{
    private const UNIDAD_DELEGACIONES_ID = 2;

    public function generar(?Carbon $corte = null): array
    {
        [$inicio, $fin] = $this->rango($corte);
        $resumen = $this->resumenVacio($inicio, $fin);

        foreach ($this->puestasDisposicion($inicio, $fin) as $puesta) {
            if ($this->esPuestaTransitoNoRelevante($puesta)) {
                continue;
            }

            $this->sumarPersonas($resumen, $puesta);
            $this->sumarVehiculos($resumen, $puesta);
            $this->sumarObjetos($resumen, $puesta);
        }

        foreach ($this->siniestrosRelevantes($inicio, $fin) as $hecho) {
            $resumen['siniestros'][] = $this->lineaSiniestro($hecho);
        }

        $resumen['params'] = $this->templateParams($resumen);
        $resumen['mensaje'] = $this->templateBody($resumen['params']);

        return $resumen;
    }

    public function generarDemo(?Carbon $corte = null): array
    {
        [$inicio, $fin] = $this->rango($corte);
        $resumen = $this->resumenVacio($inicio, $fin);

        $resumen['personas']['justicia_civica'] = 2;
        $resumen['personas']['ministerio_publico'] = 1;
        $resumen['vehiculos']['total'] = 2;
        $resumen['vehiculos']['tipos']['MOTOCICLETA'] = 1;
        $resumen['vehiculos']['tipos']['SEDAN'] = 1;
        $resumen['vehiculos']['reporte_robo'] = 1;
        $resumen['vehiculos']['hechos_delictivos'] = 1;
        $resumen['armas']['corta'] = 1;
        $resumen['armas']['cargadores'] = 2;
        $resumen['armas']['cartuchos'] = 15;
        $this->agregarCantidad($resumen['drogas']['metanfetaminas'], 25, 'gramos');
        $this->agregarCantidad($resumen['drogas']['marihuana'], 50, 'gramos');
        $resumen['dinero']['total'] = 1200;
        $resumen['otros'][] = '01 telefono celular';
        $resumen['siniestros'][] = 'Hecho #123, choque con tren, 3 lesionados, Morelia.';
        $resumen['params'] = $this->templateParams($resumen);
        $resumen['mensaje'] = $this->templateBody($resumen['params']);

        return $resumen;
    }

    public function rango(?Carbon $corte = null): array
    {
        $timezone = $this->timezone();
        $base = $corte ? $corte->copy()->timezone($timezone) : Carbon::now($timezone);
        $fin = $this->ultimoCorte($base);

        if ($fin->format('H:i') === '15:00') {
            $inicio = $fin->copy()->subDay()->setTime(22, 0, 0);
        } elseif ($fin->format('H:i') === '20:00') {
            $inicio = $fin->copy()->setTime(15, 0, 0);
        } else {
            $inicio = $fin->copy()->setTime(20, 0, 0);
        }

        return [$inicio, $fin];
    }

    public function debeIncluirSiniestroRelevante(Hechos $hecho): bool
    {
        if (!$this->esSiniestroTransito($hecho)) {
            return false;
        }

        $hecho->loadMissing('lesionados');

        $lesionados = $hecho->relationLoaded('lesionados') ? $hecho->lesionados : collect();
        $fallecidos = $lesionados->filter(function ($lesionado) {
            return $this->normalizar($lesionado->tipo_lesion ?? null) === 'FALLECIDO';
        })->count();

        return $fallecidos > 0
            || $lesionados->count() > 2
            || $this->contiene($this->textoHecho($hecho), [
                'TREN',
                'FERROCARRIL',
                'FERROVIARIO',
                'FERROVIARIA',
                'VIA FERREA',
                'VIAS FERREAS',
            ]);
    }

    public function templateParams(array $resumen): array
    {
        $fin = $resumen['fin'];
        $inicio = $resumen['inicio'];

        return [
            $fin->copy()->locale('es')->translatedFormat('d \d\e F \d\e Y'),
            $fin->format('H:i') . ' hrs',
            $this->periodoTexto($inicio, $fin),
            $this->personasTexto($resumen['personas']),
            $this->vehiculosTexto($resumen['vehiculos']),
            $this->armasTexto($resumen['armas']),
            $this->drogasTexto($resumen['drogas']),
            $this->dineroTexto($resumen['dinero']),
            $this->otrosTexto($resumen['otros']),
            $this->siniestrosTexto($resumen['siniestros']),
        ];
    }

    public function templateBody(array $params): string
    {
        $p = array_values(array_map('strval', $params));

        for ($i = count($p); $i < 10; $i++) {
            $p[] = '';
        }

        return trim(
            "Unidad de Delegaciones\n"
            . "{$p[0]}\n"
            . "Corte: {$p[1]}\n"
            . "Periodo reportado: {$p[2]}\n\n"
            . "Aseguramientos relevantes del periodo\n\n"
            . "Personas:\n{$p[3]}\n\n"
            . "Vehículos:\n{$p[4]}\n\n"
            . "Armas:\n{$p[5]}\n\n"
            . "Droga y alcohol:\n{$p[6]}\n\n"
            . "Dinero:\n{$p[7]}\n\n"
            . "Otros aseguramientos:\n{$p[8]}\n\n"
            . "Siniestros de tránsito relevantes:\n{$p[9]}\n\n"
            . "Criterio vial: solo se informa si hay fallecidos, 3 o más lesionados, o intervención del tren."
        );
    }

    private function resumenVacio(Carbon $inicio, Carbon $fin): array
    {
        return [
            'inicio' => $inicio,
            'fin' => $fin,
            'personas' => [
                'justicia_civica' => 0,
                'ministerio_publico' => 0,
            ],
            'vehiculos' => [
                'total' => 0,
                'tipos' => [],
                'reporte_robo' => 0,
                'alteracion_medios' => 0,
                'hechos_delictivos' => 0,
                'siniestro_transito' => 0,
                'abandono' => 0,
                'otros' => 0,
            ],
            'armas' => [
                'corta' => 0,
                'larga' => 0,
                'cargadores' => 0,
                'cartuchos' => 0,
                'otros' => 0,
            ],
            'drogas' => [
                'alcohol' => [],
                'metanfetaminas' => [],
                'marihuana' => [],
                'otras' => [],
            ],
            'dinero' => [
                'total' => 0.0,
                'detalles' => [],
            ],
            'otros' => [],
            'siniestros' => [],
        ];
    }

    private function puestasDisposicion(Carbon $inicio, Carbon $fin): Collection
    {
        if (!$this->tablaDisponible('puestas_disposicion')) {
            return collect();
        }

        return PuestaDisposicion::query()
            ->with(['hecho.lesionados', 'personas', 'vehiculos', 'objetos'])
            ->where('unidad_id', self::UNIDAD_DELEGACIONES_ID)
            ->whereRaw(
                "TIMESTAMP(DATE(fecha_puesta), COALESCE(TIME(hora_puesta), '00:00:00')) >= ?",
                [$inicio->toDateTimeString()]
            )
            ->whereRaw(
                "TIMESTAMP(DATE(fecha_puesta), COALESCE(TIME(hora_puesta), '00:00:00')) < ?",
                [$fin->toDateTimeString()]
            )
            ->orderBy('fecha_puesta')
            ->orderBy('hora_puesta')
            ->orderBy('id')
            ->get();
    }

    private function siniestrosRelevantes(Carbon $inicio, Carbon $fin): Collection
    {
        if (!$this->tablaDisponible('hechos')) {
            return collect();
        }

        return Hechos::query()
            ->with(['lesionados', 'delegacion'])
            ->where('unidad_org_id', self::UNIDAD_DELEGACIONES_ID)
            ->whereRaw(
                "TIMESTAMP(DATE(fecha), COALESCE(NULLIF(hora, ''), '00:00:00')) >= ?",
                [$inicio->toDateTimeString()]
            )
            ->whereRaw(
                "TIMESTAMP(DATE(fecha), COALESCE(NULLIF(hora, ''), '00:00:00')) < ?",
                [$fin->toDateTimeString()]
            )
            ->orderBy('fecha')
            ->orderBy('hora')
            ->orderBy('id')
            ->get()
            ->filter(function (Hechos $hecho) {
                return $this->debeIncluirSiniestroRelevante($hecho);
            })
            ->values();
    }

    private function esPuestaTransitoNoRelevante(PuestaDisposicion $puesta): bool
    {
        if (!$this->esTextoTransito($this->normalizar($puesta->motivo ?? '') . ' ' . $this->normalizar($puesta->tipo_puesta ?? ''))) {
            return false;
        }

        return !$puesta->hecho || !$this->debeIncluirSiniestroRelevante($puesta->hecho);
    }

    private function sumarPersonas(array &$resumen, PuestaDisposicion $puesta): void
    {
        $personas = $puesta->relationLoaded('personas') ? $puesta->personas : collect();

        foreach ($personas as $persona) {
            $texto = $this->normalizar(implode(' ', [
                $puesta->motivo ?? '',
                $puesta->tipo_puesta ?? '',
                $persona->calidad ?? '',
                $persona->delito_o_motivo ?? '',
                $persona->observaciones ?? '',
            ]));

            if ($this->esJusticiaCivica($texto)) {
                $resumen['personas']['justicia_civica']++;
            } else {
                $resumen['personas']['ministerio_publico']++;
            }
        }
    }

    private function sumarVehiculos(array &$resumen, PuestaDisposicion $puesta): void
    {
        $vehiculos = $puesta->relationLoaded('vehiculos') ? $puesta->vehiculos : collect();
        $esTransitoRelevante = $this->esTextoTransito($this->normalizar($puesta->motivo ?? '') . ' ' . $this->normalizar($puesta->tipo_puesta ?? ''));

        foreach ($vehiculos as $vehiculo) {
            $texto = $this->normalizar(implode(' ', [
                $puesta->motivo ?? '',
                $puesta->tipo_puesta ?? '',
                $vehiculo->tipo ?? '',
                $vehiculo->calidad ?? '',
                $vehiculo->motivo_relacion ?? '',
                $vehiculo->observaciones ?? '',
                $vehiculo->numero_reporte_robo ?? '',
            ]));

            $resumen['vehiculos']['total']++;
            $tipo = $this->labelVehiculo($vehiculo->tipo ?? null);
            $resumen['vehiculos']['tipos'][$tipo] = ($resumen['vehiculos']['tipos'][$tipo] ?? 0) + 1;

            if (!empty($vehiculo->con_reporte_robo) || trim((string) ($vehiculo->numero_reporte_robo ?? '')) !== '' || $this->contiene($texto, ['REPORTE DE ROBO', 'RECUPERADO', 'ROBO DE VEHICULO', 'ROBO DE VEHÍCULO'])) {
                $resumen['vehiculos']['reporte_robo']++;
            } elseif ($this->contiene($texto, ['ALTERAD', 'MEDIOS DE IDENTIFICACION', 'MEDIOS DE IDENTIFICACIÓN', 'SERIE', 'NIV'])) {
                $resumen['vehiculos']['alteracion_medios']++;
            } elseif ($esTransitoRelevante) {
                $resumen['vehiculos']['siniestro_transito']++;
            } elseif ($this->contiene($texto, ['ABANDON'])) {
                $resumen['vehiculos']['abandono']++;
            } elseif ($this->contiene($texto, ['DELITO', 'DELICT', 'ROBO', 'POSESION', 'POSESIÓN', 'DETENID'])) {
                $resumen['vehiculos']['hechos_delictivos']++;
            } else {
                $resumen['vehiculos']['otros']++;
            }
        }
    }

    private function sumarObjetos(array &$resumen, PuestaDisposicion $puesta): void
    {
        $objetos = $puesta->relationLoaded('objetos') ? $puesta->objetos : collect();

        foreach ($objetos as $objeto) {
            $cantidad = $this->cantidadObjeto($objeto);
            $unidad = trim((string) ($objeto->unidad_medida ?? ''));
            $texto = $this->normalizar(implode(' ', [
                $objeto->tipo_objeto ?? '',
                $objeto->descripcion ?? '',
                $unidad,
                $objeto->observaciones ?? '',
            ]));

            if ($this->sumarArma($resumen, $texto, $cantidad)) {
                continue;
            }

            if ($this->sumarDroga($resumen, $texto, $cantidad, $unidad)) {
                continue;
            }

            if ($this->esDinero($texto)) {
                $monto = $this->montoDinero($objeto, $texto);
                $resumen['dinero']['total'] += $monto;

                if ($monto <= 0) {
                    $resumen['dinero']['detalles'][] = $this->lineaObjeto($cantidad, $unidad, $objeto->descripcion ?? $objeto->tipo_objeto ?? 'Dinero');
                }

                continue;
            }

            $resumen['otros'][] = $this->lineaObjeto($cantidad, $unidad, $objeto->descripcion ?? $objeto->tipo_objeto ?? 'Objeto asegurado');
        }
    }

    private function sumarArma(array &$resumen, string $texto, float $cantidad): bool
    {
        if ($this->contiene($texto, ['CARGADOR'])) {
            $resumen['armas']['cargadores'] += $cantidad;
            return true;
        }

        if ($this->contiene($texto, ['CARTUCHO', 'MUNICION', 'MUNICIÓN'])) {
            $resumen['armas']['cartuchos'] += $cantidad;
            return true;
        }

        if ($this->contiene($texto, ['ARMA CORTA', 'CORTA', 'PISTOLA', 'REVOLVER'])) {
            $resumen['armas']['corta'] += $cantidad;
            return true;
        }

        if ($this->contiene($texto, ['ARMA LARGA', 'LARGA', 'RIFLE', 'ESCOPETA'])) {
            $resumen['armas']['larga'] += $cantidad;
            return true;
        }

        if ($this->contiene($texto, ['ARMA', 'GRANADA', 'LANZA', 'PUNZO', 'CUCHILLO', 'NAVAJA'])) {
            $resumen['armas']['otros'] += $cantidad;
            return true;
        }

        return false;
    }

    private function sumarDroga(array &$resumen, string $texto, float $cantidad, string $unidad): bool
    {
        if ($this->contiene($texto, ['ALCOHOL', 'CERVEZA', 'LICOR'])) {
            $this->agregarCantidad($resumen['drogas']['alcohol'], $cantidad, $unidad);
            return true;
        }

        if ($this->contiene($texto, ['METANFETAMINA', 'CRISTAL'])) {
            $this->agregarCantidad($resumen['drogas']['metanfetaminas'], $cantidad, $unidad);
            return true;
        }

        if ($this->contiene($texto, ['MARIHUANA', 'CANNABIS'])) {
            $this->agregarCantidad($resumen['drogas']['marihuana'], $cantidad, $unidad);
            return true;
        }

        if ($this->contiene($texto, ['DROGA', 'COCAINA', 'COCAÍNA', 'PASTILLA', 'FENTANILO', 'HEROINA', 'HEROÍNA', 'NARCOTICO', 'NARCÓTICO'])) {
            $this->agregarCantidad($resumen['drogas']['otras'], $cantidad, $unidad);
            return true;
        }

        return false;
    }

    private function personasTexto(array $personas): string
    {
        return 'A justicia cívica: ' . $this->pad((int) $personas['justicia_civica']) . "\n"
            . 'Al Ministerio Público: ' . $this->pad((int) $personas['ministerio_publico']);
    }

    private function vehiculosTexto(array $vehiculos): string
    {
        return 'Total: ' . $this->pad((int) $vehiculos['total']) . $this->tiposVehiculoTexto($vehiculos['tipos']) . "\n"
            . 'Recuperados con reporte de robo: ' . $this->pad((int) $vehiculos['reporte_robo']) . "\n"
            . 'A disposición por alteración en medios de identificación: ' . $this->pad((int) $vehiculos['alteracion_medios']) . "\n"
            . 'A disposición por hechos delictivos: ' . $this->pad((int) $vehiculos['hechos_delictivos']) . "\n"
            . 'A disposición por siniestro de tránsito relevante: ' . $this->pad((int) $vehiculos['siniestro_transito']) . "\n"
            . 'Asegurados por abandono: ' . $this->pad((int) $vehiculos['abandono']) . "\n"
            . 'Asegurados por otros motivos: ' . $this->pad((int) $vehiculos['otros']);
    }

    private function armasTexto(array $armas): string
    {
        return 'Corta: ' . $this->cantidadTexto($armas['corta']) . "\n"
            . 'Larga: ' . $this->cantidadTexto($armas['larga']) . "\n"
            . 'Cargadores: ' . $this->cantidadTexto($armas['cargadores']) . "\n"
            . 'Cartuchos: ' . $this->cantidadTexto($armas['cartuchos']) . "\n"
            . 'Otros: ' . $this->cantidadTexto($armas['otros']);
    }

    private function drogasTexto(array $drogas): string
    {
        return 'Alcohol: ' . $this->cantidadesPorUnidadTexto($drogas['alcohol']) . "\n"
            . 'Metanfetaminas: ' . $this->cantidadesPorUnidadTexto($drogas['metanfetaminas']) . "\n"
            . 'Marihuana: ' . $this->cantidadesPorUnidadTexto($drogas['marihuana']) . "\n"
            . 'Otras: ' . $this->cantidadesPorUnidadTexto($drogas['otras']);
    }

    private function dineroTexto(array $dinero): string
    {
        $total = (float) ($dinero['total'] ?? 0);

        if ($total > 0) {
            return 'Cantidad: $' . number_format($total, 2, '.', ',') . ' MXN';
        }

        if (!empty($dinero['detalles'])) {
            return 'Cantidad no especificada: ' . implode('; ', array_slice($dinero['detalles'], 0, 4));
        }

        return 'Cantidad: $0.00 MXN';
    }

    private function otrosTexto(array $otros): string
    {
        $otros = array_values(array_filter(array_map('trim', $otros)));

        if (empty($otros)) {
            return 'Sin otros aseguramientos relevantes.';
        }

        return implode("\n", array_slice($otros, 0, 8));
    }

    private function siniestrosTexto(array $siniestros): string
    {
        $siniestros = array_values(array_filter(array_map('trim', $siniestros)));

        if (empty($siniestros)) {
            return 'Sin siniestros de tránsito relevantes en el periodo.';
        }

        return implode("\n", array_slice($siniestros, 0, 6));
    }

    private function lineaSiniestro(Hechos $hecho): string
    {
        $hecho->loadMissing(['lesionados', 'delegacion']);
        $lesionados = $hecho->relationLoaded('lesionados') ? $hecho->lesionados : collect();
        $fallecidos = $lesionados->filter(function ($lesionado) {
            return $this->normalizar($lesionado->tipo_lesion ?? null) === 'FALLECIDO';
        })->count();
        $partes = ['Hecho #' . $hecho->id];

        if (trim((string) ($hecho->tipo_hecho ?? '')) !== '') {
            $partes[] = trim((string) $hecho->tipo_hecho);
        }

        $partes[] = $lesionados->count() . ' lesionado(s)';

        if ($fallecidos > 0) {
            $partes[] = $fallecidos . ' fallecido(s)';
        }

        if ($this->contiene($this->textoHecho($hecho), ['TREN', 'FERROCARRIL', 'VIA FERREA', 'VIAS FERREAS'])) {
            $partes[] = 'intervención de tren';
        }

        $ubicacion = $this->ubicacionHecho($hecho);

        if ($ubicacion !== '') {
            $partes[] = $ubicacion;
        }

        return implode(', ', $partes) . '.';
    }

    private function esSiniestroTransito(Hechos $hecho): bool
    {
        return $this->esTextoTransito($this->textoHecho($hecho));
    }

    private function esTextoTransito(string $texto): bool
    {
        return $this->contiene($texto, [
            'HECHO DE TRANSITO',
            'HECHOS DE TRANSITO',
            'TRANSITO',
            'SINIESTRO',
            'ACCIDENTE',
            'CHOQUE',
            'COLISION',
            'COLISIÓN',
            'VOLCADURA',
            'ATROPELL',
            'TREN',
            'FERROCARRIL',
            'FERROVIARIO',
            'FERROVIARIA',
        ]);
    }

    private function esJusticiaCivica(string $texto): bool
    {
        return $this->contiene($texto, [
            'JUSTICIA CIVICA',
            'JUSTICIA CÍVICA',
            'JUEZ CIVICO',
            'JUEZ CÍVICO',
            'CIVICA',
            'CÍVICA',
            'BARANDILLA',
            'FALTA ADMINISTRATIVA',
            'ALTERAR EL ORDEN PUBLICO',
            'ALTERAR EL ORDEN PÚBLICO',
            'ALCOHOL',
            'EBRIO',
            'ETILICO',
            'ETÍLICO',
        ]);
    }

    private function esDinero(string $texto): bool
    {
        return $this->contiene($texto, ['DINERO', 'EFECTIVO', 'PESO', 'PESOS', 'MXN', '$']);
    }

    private function montoDinero($objeto, string $texto): float
    {
        $cantidad = is_numeric($objeto->cantidad ?? null) ? (float) $objeto->cantidad : 0.0;
        $unidad = $this->normalizar($objeto->unidad_medida ?? '');

        if ($cantidad > 0 && ($unidad === '' || $this->contiene($unidad, ['PESO', 'MXN', '$']))) {
            return $cantidad;
        }

        if (preg_match_all('/\$?\s*([0-9]{1,3}(?:[, ][0-9]{3})*(?:\.[0-9]{1,2})?|[0-9]+(?:\.[0-9]{1,2})?)\s*(?:PESOS|MXN)?/u', $texto, $matches)) {
            $sum = 0.0;

            foreach ($matches[1] as $match) {
                $sum += (float) str_replace([',', ' '], '', $match);
            }

            return $sum;
        }

        return 0.0;
    }

    private function agregarCantidad(array &$bucket, float $cantidad, string $unidad): void
    {
        $unidad = trim($unidad) !== '' ? trim($unidad) : 'unidad(es)';
        $bucket[$unidad] = ($bucket[$unidad] ?? 0) + $cantidad;
    }

    private function cantidadesPorUnidadTexto(array $bucket): string
    {
        if (empty($bucket)) {
            return '00';
        }

        $partes = [];

        foreach ($bucket as $unidad => $cantidad) {
            if ((float) $cantidad <= 0) {
                continue;
            }

            $partes[] = $this->cantidadTexto($cantidad) . ' ' . trim((string) $unidad);
        }

        return $partes ? implode(', ', $partes) : '00';
    }

    private function tiposVehiculoTexto(array $tipos): string
    {
        if (empty($tipos)) {
            return '';
        }

        ksort($tipos);
        $partes = [];

        foreach ($tipos as $tipo => $total) {
            $partes[] = $this->pad((int) $total) . ' ' . mb_strtolower($tipo, 'UTF-8');
        }

        return ' (' . implode(', ', $partes) . ')';
    }

    private function labelVehiculo($tipo): string
    {
        $tipo = trim((string) $tipo);

        return $tipo !== '' ? mb_strtoupper($tipo, 'UTF-8') : 'NO ESPECIFICADO';
    }

    private function lineaObjeto(float $cantidad, string $unidad, $descripcion): string
    {
        $descripcion = trim((string) $descripcion);

        return $this->cantidadTexto($cantidad)
            . (trim($unidad) !== '' ? ' ' . trim($unidad) : '')
            . ($descripcion !== '' ? ' ' . $descripcion : '');
    }

    private function cantidadObjeto($objeto): float
    {
        if (is_numeric($objeto->cantidad ?? null) && (float) $objeto->cantidad > 0) {
            return (float) $objeto->cantidad;
        }

        return 1.0;
    }

    private function cantidadTexto($value): string
    {
        $value = (float) $value;

        if (abs($value - round($value)) < 0.00001) {
            return $this->pad((int) round($value));
        }

        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    private function periodoTexto(Carbon $inicio, Carbon $fin): string
    {
        return $inicio->format('d/m/Y H:i') . ' hrs a ' . $fin->format('d/m/Y H:i') . ' hrs';
    }

    private function ultimoCorte(Carbon $base): Carbon
    {
        $cortes = [
            $base->copy()->setTime(15, 0, 0),
            $base->copy()->setTime(20, 0, 0),
            $base->copy()->setTime(22, 0, 0),
        ];

        for ($i = count($cortes) - 1; $i >= 0; $i--) {
            if ($base->greaterThanOrEqualTo($cortes[$i])) {
                return $cortes[$i];
            }
        }

        return $base->copy()->subDay()->setTime(22, 0, 0);
    }

    private function textoHecho(Hechos $hecho): string
    {
        return $this->normalizar(implode(' ', [
            $hecho->tipo_hecho ?? '',
            $hecho->colision_camino ?? '',
            $hecho->causas ?? '',
            $hecho->responsable ?? '',
            $hecho->calle ?? '',
            $hecho->colonia ?? '',
            $hecho->municipio ?? '',
            $hecho->ubicacion_formateada ?? '',
        ]));
    }

    private function ubicacionHecho(Hechos $hecho): string
    {
        return trim(implode(', ', array_filter([
            $hecho->calle ?? null,
            $hecho->colonia ?? null,
            $hecho->municipio ?? null,
        ])));
    }

    private function contiene(string $texto, array $needles): bool
    {
        $texto = $this->normalizar($texto);

        foreach ($needles as $needle) {
            if ($needle !== '' && strpos($texto, $this->normalizar($needle)) !== false) {
                return true;
            }
        }

        return false;
    }

    private function normalizar($value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        $value = strtr($value, [
            'Á' => 'A', 'À' => 'A', 'Ä' => 'A', 'Â' => 'A',
            'É' => 'E', 'È' => 'E', 'Ë' => 'E', 'Ê' => 'E',
            'Í' => 'I', 'Ì' => 'I', 'Ï' => 'I', 'Î' => 'I',
            'Ó' => 'O', 'Ò' => 'O', 'Ö' => 'O', 'Ô' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Ü' => 'U', 'Û' => 'U',
            'Ñ' => 'N',
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n',
        ]);

        return mb_strtoupper(preg_replace('/\s+/', ' ', $value) ?? $value, 'UTF-8');
    }

    private function pad(int $value): string
    {
        return str_pad((string) max(0, $value), 2, '0', STR_PAD_LEFT);
    }

    private function tablaDisponible(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function timezone(): string
    {
        return (string) config('app.schedule_timezone', config('app.timezone', 'America/Mexico_City'));
    }
}
