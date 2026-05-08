<?php

namespace App\Services\Delegaciones;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DelegacionesExcelRevisionService
{
    private const HOJAS_NO_REGIONALES = [
        'MORELIA_RP',
        'NOV_REL',
        'TOTAL',
    ];

    private const SUBCATEGORIAS_DISPOSITIVOS = [
        1 => [
            'APOYO A EVENTOS PÚBLICOS',
            'APOYO A EVENTOS DEPORTIVOS',
            'APOYO A EVENTOS CULTURALES',
            'APOYO A EVENTOS RELIGIOSOS',
            'APOYOS A OTRAS DEPENDENCIAS (Publicas o privadas)',
            'ESCUELAS',
            'DILIGENCIAS',
            'OTROS TIPOS (Especificar en las novedades relevantes)',
        ],
        2 => [
            'OBSTRUCCIÓN DE COCHERAS',
            'OTROS TIPOS DE OBSTRUCCIÓN',
            'ACTOS DELICTIVOS',
            'CONSENTRACION PERSONAS',
            'OTROS REPORTES (Especificar en las novedades relevantes)',
        ],
        3 => [
            'CORTES DE CIRCULACIÓN',
            'MARCHAS',
            'MÍTINES',
            'OBRAS PÚBLICAS',
            'ACOMPAÑAMIENTO A CARAVANAS U OTROS',
            'OTROS ABANDERAMIENTOS (Especificar en las novedades relevantes)',
        ],
        4 => [
            'ESCUELAS SEGURAS',
            'GUARDIAN VIAL',
            'MONITOREO INTEGRAL',
            'CONCIENTIZACIÓN USO DE CASCO',
            'AMPLIACION CARRETERA CIUDAD HIDALGO-ZITACUARO',
            'SEGURIDAD EN CARRETERAS - ZINAPECUARO - MORELIA',
            'SEGURIDAD EN CARRETERAS - SALAMANCA - MORELIA',
            'APOYO COCOTRA',
            'BASES DE OPERACIONES INTERINSTITUCIONAL',
            'OTROS OPERATIVOS (Especificar en las novedades relevantes)',
        ],
        5 => [
            'CONDUCE SIN ALCOHOL (ALCOHOLÍMETRO)',
            'OTROS PROGRAMAS (Especificar en las novedades relevantes)',
        ],
        6 => [
            'VÍAS FÉRREAS',
            'PERIFÉRICOS',
            'AVENIDAS',
            'TIENDAS DEPARTAMENTALES',
            'BANCOS',
            'GASOLINERAS',
            'OFICINAS GUBERNAMENTALES',
            'MANIFESTACIONES',
            'OTROS MONITOREOS (Especificar en las novedades relevantes)',
        ],
        7 => [
            'FALLAS MECÁNICAS',
            'PEATÓN',
            'ESCOLTA EN SITUACIONES DE EMERGENCIA',
            'AGRICOLAS',
            'OTROS AUXILIOS (Especificar en las novedades relevantes)',
        ],
        8 => [
            'APOYO A LA VIALIDAD',
            'PASO LIBRE DE FUNCIONARIOS',
            'ZONAS DE MAYOR PASE DE TRANSEÚNTES',
            'PASOS PEATONALES',
            'MEDIDAS DE PROTECCIÓN',
            'PATRULLAJES',
            'SERVICIOS DE ESCOLTAS',
            'OTROS (Especificar en las novedades relevantes)',
        ],
        9 => [
            'TALLER EDUCACIÓN SEGURIDAD VIAL',
            'CAMPAÑA EDUCACIÓN SEGURIDAD VIAL',
            'CAPACITACIONES EDUCACIÓN SEGURIDAD VIAL',
            'MÓDULOS EDUCACIÓN SEGURIDAD VIAL',
            'SSP',
            'CALEA',
            'OTRAS (Especificar en las novedades relevantes)',
        ],
        10 => [
            'CONCIENTIZACIÓN Y PREVENCIÓN',
            'REPARTICIÓN DE TRÍPTICOS',
            'ESTACIONALES (SEMANA SANTA, NAVIDAD ETC.)',
            'OTRAS (Especificar en las novedades relevantes)',
        ],
        11 => [
            'PREVENCIÓN SOCIAL',
            'RECORRIDOS DE PROXIMIDAD',
            'APOYO A TURISTAS',
            'APOYO A PERSONAS DE LA TERCERA EDAD',
            'APOYO A PERSONAS PERDIDAS',
            'RECUPERACIÓN DE ESPACIOS',
            'OTRAS (Especificar en las novedades relevantes)',
        ],
    ];

    public function resumen(string $fecha): array
    {
        $tz = 'America/Mexico_City';
        $fecha = Carbon::parse($fecha, $tz)->format('Y-m-d');
        [$inicio, $fin] = $this->rangoCorte($fecha);

        [$excelPath, $excelMeta] = $this->resolverExcel($fecha);
        $spreadsheet = IOFactory::load($excelPath);

        try {
            $totalSheet = $spreadsheet->getSheetByName('TOTAL');

            if (!$totalSheet) {
                throw new \RuntimeException('El Excel de delegaciones no contiene la hoja TOTAL.');
            }

            $totales = $this->leerResumenHoja($totalSheet);
            $regionales = $this->leerRegionales($spreadsheet, $inicio, $fin);
            $topActividades = $this->leerTopActividades($totalSheet);
            $fuentes = $this->leerFuentes($inicio, $fin);
            $detalles = $this->leerDetalles($inicio, $fin);
            $alertas = $this->construirAlertas($fuentes, $totales);

            return [
                'fecha' => $fecha,
                'corte' => [
                    'inicio' => $inicio->toDateTimeString(),
                    'fin' => $fin->toDateTimeString(),
                    'hora' => config('cortes.hora_corte_delegaciones', '17:00:00'),
                    'zona_horaria' => $tz,
                    'label' => $inicio->format('d/m/Y H:i') . ' - ' . $fin->format('d/m/Y H:i'),
                ],
                'excel' => $excelMeta,
                'totales' => $totales,
                'fuentes' => $fuentes,
                'detalles' => $detalles,
                'alertas' => $alertas,
                'regionales' => $regionales,
                'top_actividades' => $topActividades,
            ];
        } finally {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }
    }

    private function rangoCorte(string $fecha): array
    {
        $horaCorte = config('cortes.hora_corte_delegaciones', '17:00:00');
        $fin = Carbon::parse($fecha . ' ' . $horaCorte, 'America/Mexico_City');
        $inicio = $fin->copy()->subDay();

        return [$inicio, $fin];
    }

    private function archivoDiario(string $fecha): array
    {
        $nombre = 'excel_delegaciones_' . $fecha . '.xlsx';
        $ruta = storage_path('app/cortes/excel_delegaciones/' . $nombre);
        $existe = File::exists($ruta);

        return [
            'existe' => $existe,
            'nombre' => $nombre,
            'actualizado_at' => $existe
                ? Carbon::createFromTimestamp(File::lastModified($ruta), 'America/Mexico_City')->toDateTimeString()
                : null,
            'size_bytes' => $existe ? File::size($ruta) : 0,
        ];
    }

    private function resolverExcel(string $fecha): array
    {
        $archivoDiario = $this->archivoDiario($fecha);
        $rutaDiaria = storage_path('app/cortes/excel_delegaciones/' . $archivoDiario['nombre']);

        if ($archivoDiario['existe']) {
            return [
                $rutaDiaria,
                [
                    'generado_para_revision' => false,
                    'origen' => 'archivo_diario',
                    'titulo' => 'Excel diario guardado',
                    'mensaje' => 'Los conteos vienen del archivo del corte guardado en el servidor.',
                    'archivo_diario' => $archivoDiario,
                ],
            ];
        }

        return [
            app(ExcelDelegacionesGenerator::class)->generar($fecha),
            [
                'generado_para_revision' => true,
                'origen' => 'base_actual',
                'titulo' => 'Vista generada con la base actual',
                'mensaje' => 'No se encontró el Excel diario guardado en el servidor; estos números se recalcularon al momento y pueden diferir de un archivo descargado antes.',
                'archivo_diario' => $archivoDiario,
            ],
        ];
    }

    private function leerRegionales($spreadsheet, Carbon $inicio, Carbon $fin): array
    {
        $regionales = [];

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $nombre = $sheet->getTitle();

            if (in_array($nombre, self::HOJAS_NO_REGIONALES, true)) {
                continue;
            }

            $resumen = $this->leerResumenHoja($sheet);
            $alertas = [];

            if (($resumen['hechos_pendientes'] ?? 0) > 0) {
                $alertas[] = 'Tiene hechos pendientes dentro del corte.';
            }

            if (($resumen['hechos_turnados'] ?? 0) > 0) {
                $alertas[] = 'Tiene hechos turnados a MP.';
            }

            if (($resumen['dispositivos'] ?? 0) <= 0 && ($resumen['hechos_total'] ?? 0) <= 0) {
                $estado = 'vacio';
            } elseif (!empty($alertas)) {
                $estado = 'atencion';
            } else {
                $estado = 'ok';
            }

            $hijas = $this->leerDelegacionesDeRegional($nombre, $inicio, $fin);
            $totalHijas = array_sum(array_map(fn ($item) => (int) ($item['dispositivos'] ?? 0), $hijas));
            $personasAlcanzadasHijas = array_sum(array_map(fn ($item) => (int) ($item['personas_alcanzadas'] ?? 0), $hijas));
            $personasParticipantesHijas = array_sum(array_map(fn ($item) => (int) ($item['personas_participantes'] ?? 0), $hijas));
            $personasDetenidasHijas = array_sum(array_map(fn ($item) => (int) ($item['personas_detenidas'] ?? 0), $hijas));
            $delegacionIds = array_values(array_filter(array_map(fn ($item) => (int) ($item['id'] ?? 0), $hijas)));

            $regionales[] = [
                'nombre' => $nombre,
                'estado' => $estado,
                'alertas' => $alertas,
                'hijas' => $hijas,
                'diferencias_renglones' => $this->leerDiferenciasRenglonesRegional($sheet, $inicio, $fin, $delegacionIds),
                'dispositivos_hijas_total' => $totalHijas,
                'diferencia_hijas' => (int) ($resumen['dispositivos'] ?? 0) - (int) $totalHijas,
                'personas_alcanzadas_hijas_total' => $personasAlcanzadasHijas,
                'personas_participantes_hijas_total' => $personasParticipantesHijas,
                'personas_detenidas_hijas_total' => $personasDetenidasHijas,
                'diferencia_personas_alcanzadas_hijas' => (int) ($resumen['personas_alcanzadas'] ?? 0) - (int) $personasAlcanzadasHijas,
                'diferencia_personas_participantes_hijas' => (int) ($resumen['personas_participantes'] ?? $resumen['estado_fuerza'] ?? 0) - (int) $personasParticipantesHijas,
            ] + $resumen;
        }

        usort($regionales, function (array $a, array $b) {
            return ($b['dispositivos'] <=> $a['dispositivos'])
                ?: ($b['hechos_total'] <=> $a['hechos_total'])
                ?: strcmp($a['nombre'], $b['nombre']);
        });

        return $regionales;
    }

    private function leerDelegacionesDeRegional(string $regional, Carbon $inicio, Carbon $fin): array
    {
        $delegaciones = DB::table('delegaciones')
            ->select(['id', 'nombre', 'delegacion_padre_id'])
            ->where('activa', 1)
            ->orderBy('nombre')
            ->get();

        $regionalNormalizada = $this->normalizarNombre($regional);
        $padre = $delegaciones->first(function ($item) use ($regionalNormalizada) {
            return $item->delegacion_padre_id === null
                && $this->normalizarNombre($item->nombre) === $regionalNormalizada;
        });

        if (!$padre) {
            return [];
        }

        $items = $delegaciones
            ->filter(fn ($item) => (int) $item->id === (int) $padre->id || (int) ($item->delegacion_padre_id ?? 0) === (int) $padre->id)
            ->sort(function ($a, $b) use ($padre) {
                $aCabecera = (int) $a->id === (int) $padre->id ? 0 : 1;
                $bCabecera = (int) $b->id === (int) $padre->id ? 0 : 1;

                return ($aCabecera <=> $bCabecera) ?: strcmp($a->nombre, $b->nombre);
            })
            ->values();

        return $items
            ->map(function ($delegacion) use ($padre, $inicio, $fin) {
                $resumen = $this->resumenDispositivosDelegacion($inicio, $fin, (int) $delegacion->id);

                return [
                    'id' => (int) $delegacion->id,
                    'nombre' => $delegacion->nombre,
                    'es_cabecera' => (int) $delegacion->id === (int) $padre->id,
                ] + $resumen;
            })
            ->filter(fn ($item) => ($item['dispositivos'] ?? 0) > 0 || ($item['hechos_contados'] ?? 0) > 0 || $item['es_cabecera'])
            ->values()
            ->all();
    }

    private function resumenDispositivosDelegacion(Carbon $inicio, Carbon $fin, int $delegacionId): array
    {
        $actividadesBase = DB::table('actividades as a')
            ->join('actividad_subcategorias as s', 'a.actividad_subcategoria_id', '=', 's.id')
            ->whereRaw('TIMESTAMP(a.fecha, a.hora) >= ? AND TIMESTAMP(a.fecha, a.hora) < ?', [
                $inicio->toDateTimeString(),
                $fin->toDateTimeString(),
            ])
            ->where('a.unidad_org_id', 2)
            ->where('a.delegacion_id', $delegacionId)
            ->where(function ($q) {
                foreach (self::SUBCATEGORIAS_DISPOSITIVOS as $categoriaId => $subcategorias) {
                    $q->orWhere(function ($nested) use ($categoriaId, $subcategorias) {
                        $nested->where('a.actividad_categoria_id', $categoriaId)
                            ->whereIn('s.nombre', $subcategorias);
                    });
                }
            });

        $actividades = (clone $actividadesBase)->count();
        $personasAlcanzadas = (int) (clone $actividadesBase)->sum(DB::raw('COALESCE(a.personas_alcanzadas, 0)'));
        $personasParticipantes = (int) (clone $actividadesBase)->sum(DB::raw('COALESCE(a.personas_participantes, 0)'));
        $personasDetenidas = (int) (clone $actividadesBase)->sum(DB::raw('COALESCE(a.personas_detenidas, 0)'));

        $hechos = DB::table('hechos as h')
            ->where('h.captura_completa', 1)
            ->whereNotNull('h.captura_completa_at')
            ->where('h.captura_completa_at', '>=', $inicio->toDateTimeString())
            ->where('h.captura_completa_at', '<', $fin->toDateTimeString())
            ->where('h.unidad_org_id', 2)
            ->where('h.delegacion_id', $delegacionId)
            ->count();

        return [
            'actividades_contadas' => (int) $actividades,
            'hechos_contados' => (int) $hechos,
            'dispositivos' => (int) $actividades + ((int) $hechos * 2),
            'estado_fuerza' => $personasParticipantes,
            'personas_participantes' => $personasParticipantes,
            'personas_alcanzadas' => $personasAlcanzadas,
            'personas_detenidas' => $personasDetenidas,
            'registros_contados' => $this->leerRegistrosContadosDelegacion($inicio, $fin, $delegacionId),
        ];
    }

    private function leerRegistrosContadosDelegacion(Carbon $inicio, Carbon $fin, int $delegacionId): array
    {
        $actividades = $this->queryActividadesDispositivo($inicio, $fin)
            ->where('a.delegacion_id', $delegacionId)
            ->orderBy('a.fecha')
            ->orderBy('a.hora')
            ->limit(120)
            ->get()
            ->map(function ($row) {
                return $this->detalleActividad($row, 'Cuenta como 1 dispositivo en la suma actual') + [
                    'peso_dispositivo' => 1,
                    'renglon_excel' => $row->subcategoria,
                ];
            })
            ->values()
            ->all();

        $hechos = $this->queryHechosContadosExcel($inicio, $fin)
            ->where('h.delegacion_id', $delegacionId)
            ->orderBy('h.captura_completa_at')
            ->limit(120)
            ->get()
            ->map(function ($row) {
                return $this->detalleHecho($row, 'Cuenta como 2 dispositivos: SINIESTROS y ACCIDENTES') + [
                    'peso_dispositivo' => 2,
                    'renglon_excel' => 'SINIESTROS + ACCIDENTES',
                ];
            })
            ->values()
            ->all();

        return array_slice(array_merge($actividades, $hechos), 0, 160);
    }

    private function leerDiferenciasRenglonesRegional(Worksheet $sheet, Carbon $inicio, Carbon $fin, array $delegacionIds): array
    {
        if (empty($delegacionIds)) {
            return [];
        }

        $excel = [];

        for ($row = 4; $row <= 77; $row++) {
            $actividad = trim((string) $sheet->getCell('C' . $row)->getCalculatedValue());

            if ($actividad === '') {
                continue;
            }

            $excel[$actividad] = ($excel[$actividad] ?? 0) + $this->intCell($sheet, 'D' . $row);
        }

        $base = [];
        $actividadRows = DB::table('actividades as a')
            ->join('actividad_subcategorias as s', 'a.actividad_subcategoria_id', '=', 's.id')
            ->select('s.nombre as actividad', DB::raw('COUNT(*) as total'))
            ->whereRaw('TIMESTAMP(a.fecha, a.hora) >= ? AND TIMESTAMP(a.fecha, a.hora) < ?', [
                $inicio->toDateTimeString(),
                $fin->toDateTimeString(),
            ])
            ->where('a.unidad_org_id', 2)
            ->whereIn('a.delegacion_id', $delegacionIds)
            ->where(function ($q) {
                foreach (self::SUBCATEGORIAS_DISPOSITIVOS as $categoriaId => $subcategorias) {
                    $q->orWhere(function ($nested) use ($categoriaId, $subcategorias) {
                        $nested->where('a.actividad_categoria_id', $categoriaId)
                            ->whereIn('s.nombre', $subcategorias);
                    });
                }
            })
            ->groupBy('s.nombre')
            ->get();

        foreach ($actividadRows as $row) {
            $actividad = (string) $row->actividad;
            $base[$actividad] = ($base[$actividad] ?? 0) + (int) $row->total;
        }

        $hechos = $this->queryHechosContadosExcel($inicio, $fin)
            ->whereIn('h.delegacion_id', $delegacionIds)
            ->count();

        $base['SINIESTROS'] = ($base['SINIESTROS'] ?? 0) + (int) $hechos;
        $base['ACCIDENTES'] = ($base['ACCIDENTES'] ?? 0) + (int) $hechos;

        $actividades = array_values(array_unique(array_merge(array_keys($excel), array_keys($base))));
        $diferencias = [];

        foreach ($actividades as $actividad) {
            $excelTotal = (int) ($excel[$actividad] ?? 0);
            $baseTotal = (int) ($base[$actividad] ?? 0);
            $diferencia = $excelTotal - $baseTotal;

            if ($diferencia === 0) {
                continue;
            }

            $diferencias[] = [
                'actividad' => $actividad,
                'excel' => $excelTotal,
                'base_actual' => $baseTotal,
                'diferencia' => $diferencia,
                'lectura' => $diferencia < 0
                    ? 'La base actual trae ' . abs($diferencia) . ' más que el Excel guardado.'
                    : 'El Excel guardado trae ' . abs($diferencia) . ' más que la base actual.',
            ];
        }

        usort($diferencias, function (array $a, array $b) {
            return abs($b['diferencia']) <=> abs($a['diferencia'])
                ?: strcmp($a['actividad'], $b['actividad']);
        });

        return array_slice($diferencias, 0, 20);
    }

    private function normalizarNombre(?string $texto): string
    {
        $texto = trim((string) $texto);
        $texto = strtr($texto, [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ü' => 'U',
            'Ñ' => 'N',
            'á' => 'A',
            'é' => 'E',
            'í' => 'I',
            'ó' => 'O',
            'ú' => 'U',
            'ü' => 'U',
            'ñ' => 'N',
        ]);
        $texto = preg_replace('/\s+/', ' ', $texto);

        return mb_strtoupper($texto ?? '');
    }

    private function leerResumenHoja(Worksheet $sheet): array
    {
        $estadoFuerza = $this->intCell($sheet, 'E78');
        $personasAlcanzadas = $this->intCell($sheet, 'H78');
        $personasAseguradas = $this->intCell($sheet, 'D110');

        return [
            'dispositivos' => $this->intCell($sheet, 'D78'),
            'estado_fuerza' => $estadoFuerza,
            'personas_participantes' => $estadoFuerza,
            'unidades' => $this->intCell($sheet, 'F78'),
            'km_recorridos' => $this->floatCell($sheet, 'G78'),
            'personas_alcanzadas' => $personasAlcanzadas,
            'recomendaciones' => $this->intCell($sheet, 'I78'),
            'control_vehicular_total' => $this->intCell($sheet, 'D94')
                + $this->intCell($sheet, 'E94')
                + $this->intCell($sheet, 'F94')
                + $this->intCell($sheet, 'G94'),
            'aseguramientos_total' => $personasAseguradas,
            'personas_aseguradas' => $personasAseguradas,
            'hechos_resueltos' => $this->intCell($sheet, 'D120'),
            'hechos_pendientes' => $this->intCell($sheet, 'D121'),
            'hechos_turnados' => $this->intCell($sheet, 'D122'),
            'hechos_total' => $this->intCell($sheet, 'D123'),
            'involucrados_hombres' => $this->intCell($sheet, 'H120'),
            'involucrados_mujeres' => $this->intCell($sheet, 'H121'),
            'involucrados_menores' => $this->intCell($sheet, 'H122'),
            'involucrados_total' => $this->intCell($sheet, 'H123'),
            'monto_danos' => $this->floatCell($sheet, 'H149'),
        ];
    }

    private function leerTopActividades(Worksheet $sheet): array
    {
        $rows = [];

        for ($row = 4; $row <= 77; $row++) {
            $actividad = trim((string) $sheet->getCell('C' . $row)->getCalculatedValue());
            $cantidad = $this->intCell($sheet, 'D' . $row);

            if ($actividad === '' || $cantidad <= 0) {
                continue;
            }

            $rows[] = [
                'actividad' => $actividad,
                'cantidad' => $cantidad,
                'estado_fuerza' => $this->intCell($sheet, 'E' . $row),
                'personas_participantes' => $this->intCell($sheet, 'E' . $row),
                'unidades' => $this->intCell($sheet, 'F' . $row),
                'km_recorridos' => $this->floatCell($sheet, 'G' . $row),
                'personas_alcanzadas' => $this->intCell($sheet, 'H' . $row),
            ];
        }

        usort($rows, fn (array $a, array $b) => $b['cantidad'] <=> $a['cantidad']);

        return array_slice($rows, 0, 10);
    }

    private function leerFuentes(Carbon $inicio, Carbon $fin): array
    {
        $actividadesCorte = DB::table('actividades as a')
            ->whereRaw('TIMESTAMP(a.fecha, a.hora) >= ? AND TIMESTAMP(a.fecha, a.hora) < ?', [
                $inicio->toDateTimeString(),
                $fin->toDateTimeString(),
            ])
            ->where('a.unidad_org_id', 2);

        $hechosPorFecha = DB::table('hechos as h')
            ->whereRaw("TIMESTAMP(h.fecha, COALESCE(h.hora, '00:00:00')) >= ? AND TIMESTAMP(h.fecha, COALESCE(h.hora, '00:00:00')) < ?", [
                $inicio->toDateTimeString(),
                $fin->toDateTimeString(),
            ])
            ->where('h.unidad_org_id', 2);

        $hechosContadosExcel = DB::table('hechos as h')
            ->where('h.captura_completa', 1)
            ->whereNotNull('h.captura_completa_at')
            ->where('h.captura_completa_at', '>=', $inicio->toDateTimeString())
            ->where('h.captura_completa_at', '<', $fin->toDateTimeString())
            ->where('h.unidad_org_id', 2);

        $incompletos = (clone $hechosPorFecha)
            ->where(function ($q) {
                $q->whereNull('h.captura_completa')
                    ->orWhere('h.captura_completa', 0);
            });

        $completadosFueraCorte = (clone $hechosPorFecha)
            ->where('h.captura_completa', 1)
            ->whereNotNull('h.captura_completa_at')
            ->where(function ($q) use ($inicio, $fin) {
                $q->where('h.captura_completa_at', '<', $inicio->toDateTimeString())
                    ->orWhere('h.captura_completa_at', '>=', $fin->toDateTimeString());
            });

        $actividadesSinCatalogoCompleto = $this->queryActividadesSinCatalogo($inicio, $fin)
            ->distinct('a.id')
            ->count('a.id');

        return [
            'actividades_en_corte' => (clone $actividadesCorte)->count(),
            'personas_alcanzadas_fuente' => (int) (clone $actividadesCorte)->sum(DB::raw('COALESCE(a.personas_alcanzadas, 0)')),
            'personas_participantes_fuente' => (int) (clone $actividadesCorte)->sum(DB::raw('COALESCE(a.personas_participantes, 0)')),
            'personas_detenidas_fuente' => (int) (clone $actividadesCorte)->sum(DB::raw('COALESCE(a.personas_detenidas, 0)')),
            'actividades_sin_delegacion' => (clone $actividadesCorte)->whereNull('a.delegacion_id')->count(),
            'actividades_sin_categoria' => (clone $actividadesCorte)->whereNull('a.actividad_categoria_id')->count(),
            'actividades_sin_subcategoria' => (clone $actividadesCorte)->whereNull('a.actividad_subcategoria_id')->count(),
            'actividades_sin_catalogo_completo' => $actividadesSinCatalogoCompleto,
            'actividades_pendientes_revision' => (clone $actividadesCorte)
                ->where(function ($q) {
                    $q->whereNull('a.estado_revision')
                        ->orWhere('a.estado_revision', '<>', 'aprobado');
                })
                ->count(),
            'hechos_por_fecha_en_corte' => (clone $hechosPorFecha)->count(),
            'hechos_contados_excel' => (clone $hechosContadosExcel)->count(),
            'hechos_incompletos_en_corte' => (clone $incompletos)->count(),
            'hechos_completados_fuera_corte' => (clone $completadosFueraCorte)->count(),
            'hechos_sin_delegacion' => (clone $hechosContadosExcel)->whereNull('h.delegacion_id')->count(),
            'hechos_sin_vehiculos_esperados' => (clone $hechosPorFecha)->where('h.vehiculos_esperados', '<=', 0)->count(),
            'hechos_sin_conductores_esperados' => (clone $hechosPorFecha)->where('h.conductores_esperados', '<=', 0)->count(),
            'hechos_sin_lesionados_esperados' => (clone $hechosPorFecha)->whereNull('h.lesionados_esperados')->count(),
        ];
    }

    private function leerDetalles(Carbon $inicio, Carbon $fin): array
    {
        $actividadesSinDelegacion = $this->queryActividadesBase($inicio, $fin)
            ->whereNull('a.delegacion_id')
            ->orderBy('a.fecha')
            ->orderBy('a.hora')
            ->limit(100)
            ->get()
            ->map(fn ($row) => $this->detalleActividad($row, 'Sin delegación asignada'))
            ->values()
            ->all();

        $hechosSinDelegacion = $this->queryHechosContadosExcel($inicio, $fin)
            ->whereNull('h.delegacion_id')
            ->orderBy('h.captura_completa_at')
            ->limit(100)
            ->get()
            ->map(fn ($row) => $this->detalleHecho($row, 'Sin delegación asignada'))
            ->values()
            ->all();

        return [
            'registros_sin_delegacion' => array_values(array_merge($actividadesSinDelegacion, $hechosSinDelegacion)),
            'actividades_sin_catalogo' => $this->queryActividadesSinCatalogo($inicio, $fin)
                ->orderBy('a.fecha')
                ->orderBy('a.hora')
                ->limit(100)
                ->get()
                ->map(fn ($row) => $this->detalleActividad($row, $this->motivoCatalogo($row)))
                ->values()
                ->all(),
            'hechos_incompletos_en_corte' => $this->queryHechosPorFecha($inicio, $fin)
                ->where(function ($q) {
                    $q->whereNull('h.captura_completa')
                        ->orWhere('h.captura_completa', 0);
                })
                ->orderBy('h.fecha')
                ->orderBy('h.hora')
                ->limit(100)
                ->get()
                ->map(fn ($row) => $this->detalleHecho($row, 'Fecha dentro del corte, pero captura incompleta'))
                ->values()
                ->all(),
            'hechos_completados_fuera_corte' => $this->queryHechosPorFecha($inicio, $fin)
                ->where('h.captura_completa', 1)
                ->whereNotNull('h.captura_completa_at')
                ->where(function ($q) use ($inicio, $fin) {
                    $q->where('h.captura_completa_at', '<', $inicio->toDateTimeString())
                        ->orWhere('h.captura_completa_at', '>=', $fin->toDateTimeString());
                })
                ->orderBy('h.fecha')
                ->orderBy('h.hora')
                ->limit(100)
                ->get()
                ->map(fn ($row) => $this->detalleHecho($row, 'Ocurrió en este corte, pero se completó fuera de la ventana'))
                ->values()
                ->all(),
            'hechos_pendientes_excel' => $this->queryHechosContadosExcel($inicio, $fin)
                ->whereRaw("UPPER(COALESCE(h.situacion, '')) = 'PENDIENTE'")
                ->orderBy('h.captura_completa_at')
                ->limit(100)
                ->get()
                ->map(fn ($row) => $this->detalleHecho($row, 'Ya está contado en Excel, pero sigue pendiente'))
                ->values()
                ->all(),
        ];
    }

    private function queryActividadesBase(Carbon $inicio, Carbon $fin)
    {
        return DB::table('actividades as a')
            ->leftJoin('actividad_categorias as c', 'a.actividad_categoria_id', '=', 'c.id')
            ->leftJoin('actividad_subcategorias as s', 'a.actividad_subcategoria_id', '=', 's.id')
            ->leftJoin('delegaciones as d', 'a.delegacion_id', '=', 'd.id')
            ->leftJoin('users as u', 'a.created_by', '=', 'u.id')
            ->select([
                'a.id',
                'a.nombre',
                'a.fecha',
                'a.hora',
                'a.lugar',
                'a.municipio',
                'a.actividad_categoria_id',
                'a.actividad_subcategoria_id',
                'a.personas_alcanzadas',
                'a.personas_participantes',
                'a.personas_detenidas',
                'c.nombre as categoria',
                's.nombre as subcategoria',
                's.actividad_categoria_id as subcategoria_categoria_id',
                'd.nombre as delegacion',
                'u.name as creado_por',
            ])
            ->whereRaw('TIMESTAMP(a.fecha, a.hora) >= ? AND TIMESTAMP(a.fecha, a.hora) < ?', [
                $inicio->toDateTimeString(),
                $fin->toDateTimeString(),
            ])
            ->where('a.unidad_org_id', 2);
    }

    private function queryActividadesSinCatalogo(Carbon $inicio, Carbon $fin)
    {
        return $this->queryActividadesBase($inicio, $fin)
            ->where(function ($q) {
                $q->whereNull('a.actividad_categoria_id')
                    ->orWhereNull('a.actividad_subcategoria_id')
                    ->orWhereNull('c.id')
                    ->orWhereNull('s.id')
                    ->orWhereColumn('s.actividad_categoria_id', '<>', 'a.actividad_categoria_id');
            });
    }

    private function queryActividadesDispositivo(Carbon $inicio, Carbon $fin)
    {
        return $this->queryActividadesBase($inicio, $fin)
            ->where(function ($q) {
                foreach (self::SUBCATEGORIAS_DISPOSITIVOS as $categoriaId => $subcategorias) {
                    $q->orWhere(function ($nested) use ($categoriaId, $subcategorias) {
                        $nested->where('a.actividad_categoria_id', $categoriaId)
                            ->whereIn('s.nombre', $subcategorias);
                    });
                }
            });
    }

    private function queryHechosPorFecha(Carbon $inicio, Carbon $fin)
    {
        return $this->queryHechosBase()
            ->whereRaw("TIMESTAMP(h.fecha, COALESCE(h.hora, '00:00:00')) >= ? AND TIMESTAMP(h.fecha, COALESCE(h.hora, '00:00:00')) < ?", [
                $inicio->toDateTimeString(),
                $fin->toDateTimeString(),
            ])
            ->where('h.unidad_org_id', 2);
    }

    private function queryHechosContadosExcel(Carbon $inicio, Carbon $fin)
    {
        return $this->queryHechosBase()
            ->where('h.captura_completa', 1)
            ->whereNotNull('h.captura_completa_at')
            ->where('h.captura_completa_at', '>=', $inicio->toDateTimeString())
            ->where('h.captura_completa_at', '<', $fin->toDateTimeString())
            ->where('h.unidad_org_id', 2);
    }

    private function queryHechosBase()
    {
        return DB::table('hechos as h')
            ->leftJoin('delegaciones as d', 'h.delegacion_id', '=', 'd.id')
            ->leftJoin('users as u', 'h.created_by', '=', 'u.id')
            ->select([
                'h.id',
                'h.folio_c5i',
                'h.fecha',
                'h.hora',
                'h.tipo_hecho',
                'h.situacion',
                'h.municipio',
                'h.calle',
                'h.captura_completa',
                'h.captura_completa_at',
                'd.nombre as delegacion',
                'u.name as creado_por',
            ]);
    }

    private function detalleActividad($row, string $motivo): array
    {
        return [
            'tipo' => 'actividad',
            'id' => (int) $row->id,
            'fecha' => (string) $row->fecha,
            'hora' => (string) ($row->hora ?? ''),
            'titulo' => trim((string) ($row->nombre ?? 'Actividad #' . $row->id)),
            'subtitulo' => trim((string) ($row->subcategoria ?? 'Sin subcategoría')),
            'delegacion' => $row->delegacion,
            'categoria' => $row->categoria,
            'subcategoria' => $row->subcategoria,
            'municipio' => $row->municipio,
            'lugar' => $row->lugar,
            'personas_alcanzadas' => (int) ($row->personas_alcanzadas ?? 0),
            'personas_participantes' => (int) ($row->personas_participantes ?? 0),
            'personas_detenidas' => (int) ($row->personas_detenidas ?? 0),
            'motivo' => $motivo,
            'creado_por' => $row->creado_por,
        ];
    }

    private function detalleHecho($row, string $motivo): array
    {
        $folio = trim((string) ($row->folio_c5i ?? ''));

        return [
            'tipo' => 'hecho',
            'id' => (int) $row->id,
            'fecha' => (string) $row->fecha,
            'hora' => (string) ($row->hora ?? ''),
            'titulo' => $folio !== '' ? 'Folio ' . $folio : 'Hecho #' . $row->id,
            'subtitulo' => trim((string) ($row->tipo_hecho ?? 'Hecho de tránsito')),
            'delegacion' => $row->delegacion,
            'folio_c5i' => $row->folio_c5i,
            'tipo_hecho' => $row->tipo_hecho,
            'situacion' => $row->situacion,
            'municipio' => $row->municipio,
            'lugar' => $row->calle,
            'motivo' => $motivo,
            'captura_completa_at' => $row->captura_completa_at,
            'creado_por' => $row->creado_por,
        ];
    }

    private function motivoCatalogo($row): string
    {
        if (empty($row->actividad_categoria_id)) {
            return 'Sin categoría seleccionada';
        }

        if (empty($row->actividad_subcategoria_id)) {
            return 'Sin subcategoría seleccionada';
        }

        if (empty($row->categoria)) {
            return 'La categoría ya no existe en catálogo';
        }

        if (empty($row->subcategoria)) {
            return 'La subcategoría ya no existe en catálogo';
        }

        if ((int) $row->subcategoria_categoria_id !== (int) $row->actividad_categoria_id) {
            return 'La subcategoría no pertenece a la categoría capturada';
        }

        return 'Catálogo incompleto';
    }

    private function construirAlertas(array $fuentes, array $totales): array
    {
        $alertas = [];

        $agregar = function (string $tipo, string $titulo, string $detalle, int $conteo, ?string $detalleKey = null) use (&$alertas) {
            if ($conteo <= 0) {
                return;
            }

            $alerta = compact('tipo', 'titulo', 'detalle', 'conteo');

            if ($detalleKey) {
                $alerta['detalle_key'] = $detalleKey;
            }

            $alertas[] = $alerta;
        };

        $agregar(
            'critica',
            'Hechos todavía fuera del Excel',
            'Tienen fecha dentro del corte, pero siguen incompletos; el Excel diario los cuenta hasta que se complete la captura.',
            (int) ($fuentes['hechos_incompletos_en_corte'] ?? 0),
            'hechos_incompletos_en_corte'
        );

        $agregar(
            'aviso',
            'Hechos completados en otro corte',
            'Ocurrieron en este corte, pero su captura completa quedó antes o después de la hora de corte; aparecerán en el corte correspondiente a la finalización.',
            (int) ($fuentes['hechos_completados_fuera_corte'] ?? 0),
            'hechos_completados_fuera_corte'
        );

        $agregar(
            'critica',
            'Registros sin delegación',
            'No se pueden ubicar con confianza en una regional del Excel.',
            (int) ($fuentes['actividades_sin_delegacion'] ?? 0) + (int) ($fuentes['hechos_sin_delegacion'] ?? 0),
            'registros_sin_delegacion'
        );

        $agregar(
            'aviso',
            'Actividades sin catálogo completo',
            'Hay capturas sin categoría o subcategoría; pueden no entrar al renglón esperado del formato.',
            (int) ($fuentes['actividades_sin_catalogo_completo'] ?? 0),
            'actividades_sin_catalogo'
        );

        if (($totales['hechos_pendientes'] ?? 0) > 0) {
            $alertas[] = [
                'tipo' => 'aviso',
                'titulo' => 'Hechos pendientes en el conteo',
                'detalle' => 'El Excel ya los cuenta, pero siguen como pendientes en la sección de hechos de tránsito.',
                'conteo' => (int) $totales['hechos_pendientes'],
                'detalle_key' => 'hechos_pendientes_excel',
            ];
        }

        return $alertas;
    }

    private function intCell(Worksheet $sheet, string $cell): int
    {
        $value = $sheet->getCell($cell)->getCalculatedValue();

        if (is_numeric($value)) {
            return (int) round((float) $value);
        }

        return 0;
    }

    private function floatCell(Worksheet $sheet, string $cell): float
    {
        $value = $sheet->getCell($cell)->getCalculatedValue();

        if (is_numeric($value)) {
            return round((float) $value, 2);
        }

        return 0.0;
    }
}
