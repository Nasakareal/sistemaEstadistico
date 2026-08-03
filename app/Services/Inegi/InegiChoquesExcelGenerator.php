<?php

namespace App\Services\Inegi;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InegiChoquesExcelGenerator
{
    private const LEGACY_PERITOS_DATABASE = 'peritos_legacy';

    private int $ultimoTotal = 0;

    private InegiChoquesSelectionService $selectionService;

    public function __construct(?InegiChoquesSelectionService $selectionService = null)
    {
        $this->selectionService = $selectionService ?: new InegiChoquesSelectionService();
    }

    public function generarAdjunto(Carbon $fecha): array
    {
        return $this->generarAdjuntoRango($fecha, $fecha);
    }

    public function generarAdjuntoRango(Carbon $desde, Carbon $hasta): array
    {
        $desde = Carbon::parse($desde->toDateString(), $this->timezone())->startOfDay();
        $hasta = Carbon::parse($hasta->toDateString(), $this->timezone())->startOfDay();

        if ($hasta->lessThan($desde)) {
            throw new \InvalidArgumentException('La fecha final del rango no puede ser anterior a la fecha inicial.');
        }

        $plantilla = $this->rutaPlantilla();

        if (!is_file($plantilla)) {
            throw new \RuntimeException('No existe la plantilla FORMATO INEGI.xlsx.');
        }

        $nombreArchivo = $this->nombreArchivo($desde, $hasta);

        $spreadsheet = IOFactory::load($plantilla);
        $sheet = $spreadsheet->getSheet(0);
        $columns = $this->columnasPorEncabezado($sheet);

        $hechos = $this->hechosPorRango($desde, $hasta);
        $this->ultimoTotal = $hechos->count();

        $this->prepararFilasDatos($sheet, $hechos->count());

        $vehiculos = $this->vehiculosPorHecho($hechos->pluck('id'));
        $conductores = $this->conductoresPorHecho($hechos->pluck('id'));
        $lesionados = $this->lesionadosPorHecho($hechos->pluck('id'));

        $row = 2;
        foreach ($hechos as $hecho) {
            $fechaHecho = Carbon::parse((string) $hecho->fecha, $this->timezone())->startOfDay();

            $data = $this->filaInegi(
                $hecho,
                $fechaHecho,
                $vehiculos->get($hecho->id, collect()),
                $conductores->get($hecho->id, collect()),
                $lesionados->get($hecho->id, collect())
            );

            foreach ($data as $header => $value) {
                if (!isset($columns[$header])) {
                    continue;
                }

                $sheet->setCellValueByColumnAndRow($columns[$header], $row, $value);
            }

            $row++;
        }

        $spreadsheet->setActiveSheetIndex(0);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->setPreCalculateFormulas(false);

        return [
            'name' => $nombreArchivo,
            'contents' => $this->exportarContenido($writer),
            'total' => $this->ultimoTotal,
            'hecho_ids' => $hechos->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
        ];
    }

    public function ultimoTotal(): int
    {
        return $this->ultimoTotal;
    }

    private function nombreArchivo(Carbon $desde, Carbon $hasta): string
    {
        if ($desde->isSameDay($hasta)) {
            return 'FORMATO_INEGI_CHOQUES_' . $desde->format('Y-m-d') . '.xlsx';
        }

        if ($desde->isSameMonth($hasta) && $desde->isSameDay($desde->copy()->startOfMonth()) && $hasta->isSameDay($hasta->copy()->endOfMonth())) {
            return 'FORMATO_INEGI_CHOQUES_' . $desde->format('Y-m') . '.xlsx';
        }

        return 'FORMATO_INEGI_CHOQUES_' . $desde->format('Y-m-d') . '_a_' . $hasta->format('Y-m-d') . '.xlsx';
    }

    private function filaInegi($hecho, Carbon $fecha, Collection $vehiculos, Collection $conductores, Collection $lesionados): array
    {
        $hora = $this->horaPartes($hecho->hora ?? null);
        $coordenadas = $this->coordenadasGeograficas($hecho);
        $clasificacion = $this->clasificarVehiculos($vehiculos);
        $conductor = $conductores->first();
        $zonaCarretera = $this->esCarretera($hecho);
        [$calle1, $calle2, $carretera] = $this->ubicacionColumnas($hecho, $zonaCarretera);

        return [
            'FOLIO' => (int) $hecho->id,
            'EDO' => 16,
            'MES' => (int) $fecha->format('n'),
            'ANIO' => (int) $fecha->format('Y'),
            'MPIO' => 53,
            'LOCALIDAD' => 1,
            'HORA' => $hora['hora'],
            'MINUTOS' => $hora['minutos'],
            'DIA' => (int) $fecha->format('j'),
            'DIASEMANA' => (int) $fecha->isoWeekday(),
            'URBANA' => $zonaCarretera ? 0 : 1,
            'SUBURBANA' => $zonaCarretera ? 2 : 0,
            'CALLE1' => $calle1,
            'CALLE2' => $calle2,
            'CARRETERA' => $carretera,
            'COLONIA' => $this->valorLimpio($hecho->colonia ?? null),
            'NUMERO EXTERIOR' => null,
            'CODIGO POSTAL' => $this->valorLimpio($hecho->codigo_postal ?? null),
            'REFERENCIA' => null,
            'LATITUD' => $coordenadas['lat'],
            'LONGITUD' => $coordenadas['lng'],
            'TIPACCID' => $this->tipoAccidenteInegi($hecho, $clasificacion),
            'TIPACCIDES' => $this->valorLimpio($hecho->tipo_hecho ?? null),
            'AUTOMOVIL' => $clasificacion['AUTOMOVIL'],
            'CAMPASAJ' => $clasificacion['CAMPASAJ'],
            'MICROBUS' => $clasificacion['MICROBUS'],
            'PASCAMION' => $clasificacion['PASCAMION'],
            'OMNIBUS' => $clasificacion['OMNIBUS'],
            'TRANVIA' => 0,
            'CAMIONETA' => $clasificacion['CAMIONETA'],
            'CAMION' => $clasificacion['CAMION'],
            'TRACTOR' => $clasificacion['TRACTOR'],
            'FERROCARRI' => $clasificacion['FERROCARRI'],
            'MOTOCICLET' => $clasificacion['MOTOCICLET'],
            'BICICLETA' => $clasificacion['BICICLETA'],
            'OTROVEHIC' => $clasificacion['OTROVEHIC'],
            'CAUSAACCI' => 1,
            'DESCRPCION' => $this->valorLimpio($hecho->causas ?? null),
            'CAPAROD' => $this->valorLimpio($hecho->superficie_via ?? null),
            'DESCPAROD' => $this->valorLimpio($hecho->condiciones ?? null),
            'SEXO' => $this->sexoCodigo($conductor->sexo ?? null),
            'CONDDES' => $this->valorLimpio($conductor->sexo ?? null),
            'ALIENTO1' => $this->alientoCodigo($conductor ?? null),
            'ALIENTODES' => $this->alientoDescripcion($conductor ?? null),
            'CINTURON1' => $this->cinturonCodigo($conductor->cinturon ?? null),
            'CINTURONDE' => $this->cinturonDescripcion($conductor->cinturon ?? null),
            'EDAD' => $this->enteroONull($conductor->edad ?? null),
            'HERIDOS' => $this->conteoHeridos($lesionados),
            'MUERTOS' => $this->conteoMuertos($lesionados),
            'CONDMUERTO' => $this->conteoVictimas($lesionados, 'COND', true),
            'CONDHERIDO' => $this->conteoVictimas($lesionados, 'COND', false),
            'PASAMUERTO' => $this->conteoVictimas($lesionados, 'PASA', true),
            'PASAHERIDO' => $this->conteoVictimas($lesionados, 'PASA', false),
            'PEATMUERTO' => $this->conteoVictimas($lesionados, 'PEAT', true),
            'PEATHERIDO' => $this->conteoVictimas($lesionados, 'PEAT', false),
            'CICLMUERTO' => $this->conteoVictimas($lesionados, 'CICL', true),
            'CICLHERIDO' => $this->conteoVictimas($lesionados, 'CICL', false),
            'OTROMUERTO' => $this->conteoVictimas($lesionados, 'OTRO', true),
            'OTROHERIDO' => $this->conteoVictimas($lesionados, 'OTRO', false),
            'VEHICULOS' => $this->montoVehiculos($vehiculos),
            'PROPESTADO' => 0,
            'PROPPARTIC' => $this->monto($hecho->monto_danos_patrimoniales ?? null),
            'OTROSDANOS' => 0,
        ];
    }

    private function exportarContenido($writer): string
    {
        $bufferLevel = ob_get_level();
        ob_start();

        try {
            $writer->save('php://output');
            $contents = ob_get_clean();
        } catch (\Throwable $exception) {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }

            throw $exception;
        }

        if ($contents === false || $contents === '') {
            throw new \RuntimeException('No se pudo generar el contenido del formato INEGI.');
        }

        return $contents;
    }

    private function prepararFilasDatos(Worksheet $sheet, int $totalFilas): void
    {
        $highestRow = max(2, $sheet->getHighestRow());
        $highestColumn = $sheet->getHighestColumn();

        if ($highestRow > 2) {
            $sheet->removeRow(3, $highestRow - 2);
        }

        $filasNecesarias = max(1, $totalFilas);
        if ($filasNecesarias > 1) {
            $sheet->insertNewRowBefore(3, $filasNecesarias - 1);
        }

        $rowHeight = $sheet->getRowDimension(2)->getRowHeight();

        for ($row = 2; $row < 2 + $filasNecesarias; $row++) {
            if ($row > 2) {
                $sheet->duplicateStyle($sheet->getStyle("A2:{$highestColumn}2"), "A{$row}:{$highestColumn}{$row}");
                $sheet->getRowDimension($row)->setRowHeight($rowHeight);
            }

            $sheet->getStyle("A{$row}:{$highestColumn}{$row}")
                ->getNumberFormat()
                ->setFormatCode('General');

            foreach (range(1, Coordinate::columnIndexFromString($highestColumn)) as $column) {
                $sheet->setCellValueByColumnAndRow($column, $row, null);
            }
        }
    }

    private function columnasPorEncabezado(Worksheet $sheet): array
    {
        $columns = [];
        $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());

        for ($column = 1; $column <= $highestColumnIndex; $column++) {
            $header = $this->normalizarHeader($sheet->getCellByColumnAndRow($column, 1)->getValue());
            if ($header !== '') {
                $columns[$header] = $column;
            }
        }

        return $columns;
    }

    private function hechosPorFecha(Carbon $fecha): Collection
    {
        return $this->hechosPorRango($fecha, $fecha);
    }

    private function hechosPorRango(Carbon $desde, Carbon $hasta): Collection
    {
        return $this->queryHechos()
            ->whereDate('h.fecha', '>=', $desde->toDateString())
            ->whereDate('h.fecha', '<=', $hasta->toDateString())
            ->orderBy('h.fecha')
            ->orderBy('h.hora')
            ->orderBy('h.id')
            ->get();
    }

    private function queryHechos()
    {
        $query = DB::table('hechos as h')
            ->leftJoin('users as creator', 'creator.id', '=', 'h.created_by');

        $this->selectionService->aplicarFiltroIncluidos($query);

        if ($this->legacyAccidentestDisponible()) {
            if ($this->legacyMapDisponible()) {
                $query->leftJoin('legacy_peritos_import_hechos as legacy_map', 'legacy_map.new_hecho_id', '=', 'h.id');
            }

            $legacyId = $this->legacyMapDisponible()
                ? DB::raw('COALESCE(legacy_map.old_hecho_id, h.id)')
                : 'h.id';

            $query->leftJoin(self::LEGACY_PERITOS_DATABASE . '.accidentest as legacy_accidente', function ($join) use ($legacyId) {
                $join->on('legacy_accidente.id_accidentes', '=', $legacyId)
                    ->where('h.fuente_ubicacion', '=', 'legacy_peritos');
            });
        }

        $selects = [
            'h.id',
            'h.folio_c5i',
            'h.fecha',
            'h.hora',
            'h.calle',
            'h.colonia',
            'h.entre_calles',
            'h.municipio',
            'h.codigo_postal',
            'h.lat',
            'h.lng',
            'h.tipo_hecho',
            'h.superficie_via',
            'h.tiempo',
            'h.clima',
            'h.condiciones',
            'h.causas',
            'h.oficio_mp',
            'h.danos_patrimoniales',
            'h.propiedades_afectadas',
            'h.monto_danos_patrimoniales',
        ];

        if ($this->legacyAccidentestDisponible()) {
            $selects[] = 'legacy_accidente.coordenadas as legacy_coordenadas';
            $selects[] = DB::raw('ST_Y(legacy_accidente.punto) as legacy_lat_punto');
            $selects[] = DB::raw('ST_X(legacy_accidente.punto) as legacy_lng_punto');
        } else {
            $selects[] = DB::raw('NULL as legacy_coordenadas');
            $selects[] = DB::raw('NULL as legacy_lat_punto');
            $selects[] = DB::raw('NULL as legacy_lng_punto');
        }

        return $query->select($selects);
    }

    private function vehiculosPorHecho(Collection $hechoIds): Collection
    {
        if ($hechoIds->isEmpty()) {
            return collect();
        }

        return DB::table('hecho_vehiculo as hv')
            ->join('vehiculos as v', 'v.id', '=', 'hv.vehiculo_id')
            ->whereIn('hv.hecho_id', $hechoIds->all())
            ->select(
                'hv.hecho_id',
                'v.id',
                'v.marca',
                'v.tipo',
                'v.linea',
                'v.color',
                'v.placas',
                'v.estado_placas',
                'v.tipo_servicio',
                'v.capacidad_personas',
                'v.monto_danos'
            )
            ->get()
            ->groupBy('hecho_id');
    }

    private function conductoresPorHecho(Collection $hechoIds): Collection
    {
        if ($hechoIds->isEmpty()) {
            return collect();
        }

        return DB::table('hecho_vehiculo as hv')
            ->join('vehiculo_conductor as vc', 'vc.vehiculo_id', '=', 'hv.vehiculo_id')
            ->join('conductores as c', 'c.id', '=', 'vc.conductor_id')
            ->whereIn('hv.hecho_id', $hechoIds->all())
            ->select(
                'hv.hecho_id',
                'c.id',
                'c.edad',
                'c.sexo',
                'c.cinturon',
                'c.certificado_alcoholemia',
                'c.aliento_etilico'
            )
            ->get()
            ->groupBy('hecho_id');
    }

    private function lesionadosPorHecho(Collection $hechoIds): Collection
    {
        if ($hechoIds->isEmpty()) {
            return collect();
        }

        return DB::table('lesionados')
            ->whereIn('hecho_id', $hechoIds->all())
            ->select('hecho_id', 'id', 'edad', 'sexo', 'tipo_lesion', 'tipo_victima', 'hospital')
            ->get()
            ->groupBy('hecho_id');
    }

    private function ubicacionColumnas($hecho, bool $zonaCarretera): array
    {
        $calle = $this->valorLimpio($hecho->calle ?? null);
        $entreCalles = $this->valorLimpio($hecho->entre_calles ?? null);

        if ($zonaCarretera) {
            return [null, null, $this->unirPartes([$calle, $entreCalles ? 'entre ' . $entreCalles : null])];
        }

        return [$calle, $entreCalles, null];
    }

    private function esCarretera($hecho): bool
    {
        $texto = $this->normalizarTexto($this->unirPartes([
            $hecho->calle ?? null,
            $hecho->entre_calles ?? null,
            $hecho->ubicacion_formateada ?? null,
        ]));

        return str_contains($texto, 'CARRETERA')
            || str_contains($texto, 'AUTOPISTA')
            || str_contains($texto, 'LIBRAMIENTO')
            || str_contains($texto, 'KM ');
    }

    private function clasificarVehiculos(Collection $vehiculos): array
    {
        $conteos = [
            'AUTOMOVIL' => 0,
            'CAMPASAJ' => 0,
            'MICROBUS' => 0,
            'PASCAMION' => 0,
            'OMNIBUS' => 0,
            'CAMIONETA' => 0,
            'CAMION' => 0,
            'TRACTOR' => 0,
            'FERROCARRI' => 0,
            'MOTOCICLET' => 0,
            'BICICLETA' => 0,
            'OTROVEHIC' => 0,
        ];

        foreach ($vehiculos as $vehiculo) {
            $key = $this->mapVehiculoInegi($vehiculo);
            $conteos[$key] = ($conteos[$key] ?? 0) + 1;
        }

        return $conteos;
    }

    private function mapVehiculoInegi($vehiculo): string
    {
        $tipoServicio = $this->normalizarTexto($vehiculo->tipo_servicio ?? '');
        $tipo = $this->normalizarTexto($vehiculo->tipo ?? '');
        $linea = $this->normalizarTexto($vehiculo->linea ?? '');
        $general = $this->tipoGeneralVehiculo($tipo);
        $capacidad = (int) ($vehiculo->capacidad_personas ?? 0);
        $texto = trim($tipo . ' ' . $linea);

        if ($general === 'bicicleta') {
            return 'BICICLETA';
        }

        if ($general === 'motocicleta') {
            return 'MOTOCICLET';
        }

        if (str_contains($texto, 'FERROCARRIL') || str_contains($texto, 'TREN')) {
            return 'FERROCARRI';
        }

        if (str_contains($texto, 'TRACTOR') || str_contains($texto, 'TRACTO')) {
            return 'TRACTOR';
        }

        if (str_contains($texto, 'OMNIBUS') || str_contains($texto, 'AUTOBUS') || str_contains($texto, 'BUS')) {
            return 'OMNIBUS';
        }

        if (str_contains($texto, 'MICROBUS') || str_contains($texto, 'URVAN')) {
            return 'MICROBUS';
        }

        if (str_contains($tipoServicio, 'PUBLICO') || str_contains($tipoServicio, 'TAXI') || str_contains($tipoServicio, 'COLECTIVO') || str_contains($tipoServicio, 'RUTA')) {
            return $general === 'camion' ? 'PASCAMION' : 'CAMPASAJ';
        }

        if ($general === 'automovil') {
            return 'AUTOMOVIL';
        }

        if ($general === 'camioneta') {
            return 'CAMIONETA';
        }

        if ($general === 'camion' || $general === 'remolque') {
            return $capacidad > 8 ? 'PASCAMION' : 'CAMION';
        }

        return 'OTROVEHIC';
    }

    private function tipoGeneralVehiculo(string $tipo): string
    {
        if ($tipo === '') {
            return 'otros';
        }

        if ($this->contiene($tipo, ['MOTO', 'SCOOTER', 'MOTONETA', 'ENDURO', 'NAKED', 'PISTA', 'DOBLE PROPOSITO', 'CRUISER', 'CHOPPER', 'CUATRIMOTO'])) {
            return 'motocicleta';
        }

        if ($this->contiene($tipo, ['BICICLETA', 'BMX'])) {
            return 'bicicleta';
        }

        if ($this->contiene($tipo, ['CAMION', 'TRACTO', 'TRAILER', 'VOLTEO', 'PIPA', 'TORTON', 'RABON'])) {
            return 'camion';
        }

        if ($this->contiene($tipo, ['REMOLQUE', 'SEMIRREM', 'SEMIRREMOLQUE', 'PLATAFORMA', 'DOLLY'])) {
            return 'remolque';
        }

        if ($this->contiene($tipo, ['PICK', 'CAMIONETA', 'SUV', 'VAN', 'MINIVAN', 'PANEL', 'URVAN', 'FURGON', 'VAGONETA'])) {
            return 'camioneta';
        }

        if ($this->contiene($tipo, ['AUTO', 'SEDAN', 'HATCH', 'COUPE', 'CONVERTIBLE', 'VOCHO', 'TSURU', 'COMPACTO'])) {
            return 'automovil';
        }

        return 'otros';
    }

    private function tipoAccidenteInegi($hecho, array $clasificacion): int
    {
        $tipo = $this->normalizarTexto($hecho->tipo_hecho ?? '');

        if (str_contains($tipo, 'PEATON')) {
            return 2;
        }

        if (str_contains($tipo, 'SEMOVIENTE')) {
            return 3;
        }

        if (str_contains($tipo, 'OBJETO FIJO')) {
            return 4;
        }

        if (str_contains($tipo, 'VOLCADURA')) {
            return 5;
        }

        if (str_contains($tipo, 'SALIDA')) {
            return 7;
        }

        if (str_contains($tipo, 'INCENDIO')) {
            return 8;
        }

        if (($clasificacion['FERROCARRI'] ?? 0) > 0) {
            return 9;
        }

        if (($clasificacion['MOTOCICLET'] ?? 0) > 0) {
            return 10;
        }

        if (($clasificacion['BICICLETA'] ?? 0) > 0) {
            return 11;
        }

        if (str_contains($tipo, 'COLISION') || str_contains($tipo, 'CHOQUE')) {
            return 1;
        }

        return 12;
    }

    private function conteoHeridos(Collection $lesionados): int
    {
        return $lesionados->filter(fn ($lesionado) => !$this->esFallecido($lesionado->tipo_lesion ?? null))->count();
    }

    private function conteoMuertos(Collection $lesionados): int
    {
        return $lesionados->filter(fn ($lesionado) => $this->esFallecido($lesionado->tipo_lesion ?? null))->count();
    }

    private function conteoVictimas(Collection $lesionados, string $categoria, bool $fallecido): int
    {
        return $lesionados
            ->filter(function ($lesionado) use ($categoria, $fallecido) {
                return $this->categoriaVictimaInegi($lesionado->tipo_victima ?? null) === $categoria
                    && $this->esFallecido($lesionado->tipo_lesion ?? null) === $fallecido;
            })
            ->count();
    }

    private function categoriaVictimaInegi($tipoVictima): string
    {
        $tipo = $this->normalizarTexto($tipoVictima);

        if ($tipo === 'CONDUCTOR' || $tipo === 'MOTOCICLISTA') {
            return 'COND';
        }

        if ($tipo === 'PASAJERO') {
            return 'PASA';
        }

        if ($tipo === 'PEATON') {
            return 'PEAT';
        }

        if ($tipo === 'CICLISTA') {
            return 'CICL';
        }

        return 'OTRO';
    }

    private function esFallecido($tipoLesion): bool
    {
        return $this->normalizarTexto($tipoLesion) === 'FALLECIDO';
    }

    private function montoVehiculos(Collection $vehiculos): float
    {
        return round($vehiculos->sum(fn ($vehiculo) => $this->monto($vehiculo->monto_danos ?? null)), 2);
    }

    private function monto($valor): float
    {
        return is_numeric($valor) ? round((float) $valor, 2) : 0.0;
    }

    private function sexoCodigo($sexo): ?int
    {
        $sexo = $this->normalizarTexto($sexo);

        if (str_contains($sexo, 'MASC') || $sexo === 'HOMBRE') {
            return 2;
        }

        if (str_contains($sexo, 'FEM') || $sexo === 'MUJER') {
            return 3;
        }

        return null;
    }

    private function alientoCodigo($conductor): ?int
    {
        if (!$conductor) {
            return null;
        }

        if ((int) ($conductor->aliento_etilico ?? 0) === 1 || (int) ($conductor->certificado_alcoholemia ?? 0) === 1) {
            return 1;
        }

        return null;
    }

    private function alientoDescripcion($conductor): string
    {
        if (!$conductor) {
            return 'SIN DATO';
        }

        if ((int) ($conductor->aliento_etilico ?? 0) === 1) {
            return 'ALIENTO ETILICO';
        }

        if ((int) ($conductor->certificado_alcoholemia ?? 0) === 1) {
            return 'CON CERTIFICADO';
        }

        return 'SIN DATO';
    }

    private function cinturonCodigo($valor): ?int
    {
        if ((string) $valor === '1') {
            return 1;
        }

        if ((string) $valor === '0') {
            return 2;
        }

        $valor = $this->normalizarTexto($valor);

        if ($valor === '') {
            return null;
        }

        if ($this->contiene($valor, ['SI', 'USO', 'UTILIZO'])) {
            return 1;
        }

        if ($this->contiene($valor, ['NO', 'SIN'])) {
            return 2;
        }

        return null;
    }

    private function cinturonDescripcion($valor): ?string
    {
        $codigo = $this->cinturonCodigo($valor);

        if ($codigo === 1) {
            return 'SI';
        }

        if ($codigo === 2) {
            return 'NO';
        }

        return null;
    }

    private function coordenadasGeograficas($hecho): array
    {
        $coordenadas = $this->normalizarParCoordenadas($hecho->lat ?? null, $hecho->lng ?? null);

        if ($coordenadas !== null) {
            return $coordenadas;
        }

        $coordenadas = $this->normalizarParCoordenadas($hecho->legacy_lat_punto ?? null, $hecho->legacy_lng_punto ?? null);

        if ($coordenadas !== null) {
            return $coordenadas;
        }

        $coordenadas = $this->parsearCoordenadasLegacy($hecho->legacy_coordenadas ?? null);

        if ($coordenadas !== null) {
            return $coordenadas;
        }

        return ['lat' => null, 'lng' => null];
    }

    private function parsearCoordenadasLegacy($valor): ?array
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return null;
        }

        if (!preg_match('/^\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*$/', $valor, $matches)) {
            return null;
        }

        return $this->normalizarParCoordenadas($matches[1], $matches[2]);
    }

    private function normalizarParCoordenadas($lat, $lng): ?array
    {
        if (!is_numeric($lat) || !is_numeric($lng)) {
            return null;
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        if (abs($lat) < 0.0000001 && abs($lng) < 0.0000001) {
            return null;
        }

        if ($this->latitudValida($lat) && $this->longitudValida($lng)) {
            return ['lat' => $lat, 'lng' => $lng];
        }

        if ($this->longitudValida($lat) && $this->latitudValida($lng)) {
            return ['lat' => $lng, 'lng' => $lat];
        }

        return null;
    }

    private function latitudValida(float $valor): bool
    {
        return $valor >= -90 && $valor <= 90;
    }

    private function longitudValida(float $valor): bool
    {
        return $valor >= -180 && $valor <= 180;
    }

    private function horaPartes($hora): array
    {
        $valor = trim((string) $hora);

        if ($valor === '') {
            return ['hora' => null, 'minutos' => null];
        }

        if (preg_match('/^(\d{1,2}):(\d{2})/', $valor, $matches)) {
            return [
                'hora' => (int) $matches[1],
                'minutos' => (int) $matches[2],
            ];
        }

        return ['hora' => null, 'minutos' => null];
    }

    private function valorLimpio($valor): ?string
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return null;
        }

        if (in_array($this->normalizarTexto($valor), ['NA', 'N/A', 'NO APLICA', 'NULL', 'S/D', 'SD'], true)) {
            return null;
        }

        return $valor;
    }

    private function enteroONull($valor): ?int
    {
        return is_numeric($valor) ? (int) $valor : null;
    }

    private function unirPartes(array $partes): string
    {
        return collect($partes)
            ->map(fn ($parte) => $this->valorLimpio($parte))
            ->filter()
            ->implode(', ');
    }

    private function contiene(string $texto, array $palabras): bool
    {
        foreach ($palabras as $palabra) {
            if (str_contains($texto, $palabra)) {
                return true;
            }
        }

        return false;
    }

    private function normalizarHeader($valor): string
    {
        return $this->normalizarTexto($valor);
    }

    private function normalizarTexto($valor): string
    {
        $texto = mb_strtoupper(trim((string) $valor), 'UTF-8');

        $texto = strtr($texto, [
            'Á' => 'A',
            'À' => 'A',
            'Ä' => 'A',
            'Â' => 'A',
            'É' => 'E',
            'È' => 'E',
            'Ë' => 'E',
            'Ê' => 'E',
            'Í' => 'I',
            'Ì' => 'I',
            'Ï' => 'I',
            'Î' => 'I',
            'Ó' => 'O',
            'Ò' => 'O',
            'Ö' => 'O',
            'Ô' => 'O',
            'Ú' => 'U',
            'Ù' => 'U',
            'Ü' => 'U',
            'Û' => 'U',
            'Ñ' => 'N',
        ]);

        return preg_replace('/\s+/', ' ', $texto) ?? $texto;
    }

    private function rutaPlantilla(): string
    {
        $configPath = trim((string) config('services.inegi_choques.template_path', ''));

        return $configPath !== '' ? $configPath : public_path('FORMATO INEGI.xlsx');
    }

    private function timezone(): string
    {
        return (string) config('app.schedule_timezone', config('app.timezone', 'America/Mexico_City'));
    }

    private function legacyAccidentestDisponible(): bool
    {
        static $disponible = null;

        if ($disponible !== null) {
            return $disponible;
        }

        return $disponible = $this->tablaDisponible(self::LEGACY_PERITOS_DATABASE, 'accidentest');
    }

    private function legacyMapDisponible(): bool
    {
        static $disponible = null;

        if ($disponible !== null) {
            return $disponible;
        }

        return $disponible = $this->tablaDisponible(DB::getDatabaseName(), 'legacy_peritos_import_hechos');
    }

    private function tablaDisponible(string $database, string $tabla): bool
    {
        $resultado = DB::selectOne(
            'SELECT 1 AS existe FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? LIMIT 1',
            [$database, $tabla]
        );

        return $resultado !== null;
    }
}
