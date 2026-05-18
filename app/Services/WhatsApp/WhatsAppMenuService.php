<?php

namespace App\Services\WhatsApp;

class WhatsAppMenuService
{
    public function buildRootMenu($user, array $context, ?string $message = null): array
    {
        $text = $message ?: 'Selecciona la unidad que deseas consultar.';
        $modules = is_array($context['modules'] ?? null) ? $context['modules'] : [];
        $rows = [];

        foreach ($modules as $module) {
            $rows[] = [
                'id' => 'module:' . $module,
                'title' => $this->moduleTitle($module),
                'description' => $this->moduleDescription($module),
            ];
        }

        if (empty($rows)) {
            $rows[] = [
                'id' => 'module:siniestros',
                'title' => 'Siniestros',
                'description' => 'Hechos, personal y estadísticas',
            ];
        }

        return [
            'text' => "Hola {$user->name}.\n\n{$text}",
            'interactive' => [
                'type' => 'list',
                'header' => [
                    'type' => 'text',
                    'text' => 'Menú general',
                ],
                'body' => [
                    'text' => 'Elige una unidad',
                ],
                'footer' => [
                    'text' => 'Puedes escribir MENÚ para reiniciar',
                ],
                'action' => [
                    'button' => 'Ver opciones',
                    'sections' => [
                        [
                            'title' => 'Unidades',
                            'rows' => $rows,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function buildModuleMenu($user, array $context, string $module, ?string $message = null): array
    {
        $title = $this->moduleTitle($module);
        $text = $message ?: "Menú de {$title}.";

        if ($module === 'siniestros') {
            $rows = $context['solo_propios'] ?? false
                ? [
                    ['id' => 'action:mis_hechos_hoy', 'title' => 'Mis hechos de hoy', 'description' => 'Solo los tuyos'],
                    ['id' => 'action:mis_hechos_placas', 'title' => 'Mis hechos por placas', 'description' => 'Buscar por placas'],
                    ['id' => 'action:mi_detalle_folio', 'title' => 'Mi detalle por ID', 'description' => 'Buscar por ID'],
                    ['id' => 'action:estadisticas_rapidas', 'title' => 'Estadísticas rápidas', 'description' => 'Resumen, lesionados y tipos'],
                    ['id' => 'action:personal_armado', 'title' => 'Personal armado', 'description' => 'Relación actual'],
                    ['id' => 'action:personal_activo', 'title' => 'Personal activo', 'description' => 'Listado del personal'],
                    ['id' => 'action:expediente_personal', 'title' => 'Expediente de personal', 'description' => 'Foto, patrulla y datos'],
                    ['id' => 'action:actividades_hoy', 'title' => 'Actividades de hoy', 'description' => 'Apoyos y labores'],
                    ['id' => 'action:puestas_hoy', 'title' => 'Puestas de hoy', 'description' => 'Listado de puestas'],
                ]
                : [
                    ['id' => 'action:hechos_hoy', 'title' => 'Hechos de hoy', 'description' => 'Listado de hoy'],
                    ['id' => 'action:hechos_placas', 'title' => 'Hechos por placas', 'description' => 'Buscar por placas'],
                    ['id' => 'action:detalle_folio', 'title' => 'Detalle por ID', 'description' => 'Buscar por ID'],
                    ['id' => 'action:estadisticas_rapidas', 'title' => 'Estadísticas rápidas', 'description' => 'Resumen, lesionados y tipos'],
                    ['id' => 'action:personal_armado', 'title' => 'Personal armado', 'description' => 'Relación actual'],
                    ['id' => 'action:personal_activo', 'title' => 'Personal activo', 'description' => 'Listado del personal'],
                    ['id' => 'action:expediente_personal', 'title' => 'Expediente de personal', 'description' => 'Foto, patrulla y datos'],
                    ['id' => 'action:actividades_hoy', 'title' => 'Actividades de hoy', 'description' => 'Apoyos y labores'],
                    ['id' => 'action:puestas_hoy', 'title' => 'Puestas de hoy', 'description' => 'Listado de puestas'],
                ];
        } elseif ($module === 'carreteras') {
            $rows = [
                ['id' => 'action:operativos_hoy', 'title' => 'Operativos de hoy', 'description' => 'PSV, RSV, CASCO y más'],
                ['id' => 'action:operativos_tipo', 'title' => 'Operativos por tipo', 'description' => 'Filtrar por dispositivo'],
                ['id' => 'action:actividades_hoy', 'title' => 'Actividades de hoy', 'description' => 'Apoyos y labores'],
                ['id' => 'action:personal_armado', 'title' => 'Personal armado', 'description' => 'Relación actual'],
                ['id' => 'action:personal_activo', 'title' => 'Personal activo', 'description' => 'Listado del personal'],
                ['id' => 'action:expediente_personal', 'title' => 'Expediente de personal', 'description' => 'Foto, patrulla y datos'],
                ['id' => 'action:puestas_hoy', 'title' => 'Puestas de hoy', 'description' => 'Listado de puestas'],
            ];
        } elseif ($module === 'vialidades') {
            $rows = [
                ['id' => 'action:actividades_hoy', 'title' => 'Actividades de hoy', 'description' => 'Operativos, apoyos y labores'],
                ['id' => 'action:actividades_rango', 'title' => 'Actividades por rango', 'description' => 'Consultar por fechas'],
                ['id' => 'action:personal_armado', 'title' => 'Personal armado', 'description' => 'Relación actual'],
                ['id' => 'action:personal_activo', 'title' => 'Personal activo', 'description' => 'Listado del personal'],
                ['id' => 'action:expediente_personal', 'title' => 'Expediente de personal', 'description' => 'Foto, patrulla y datos'],
            ];
        } elseif ($module === 'delegaciones') {
            $rows = [
                ['id' => 'action:actividades_hoy', 'title' => 'Actividades de hoy', 'description' => 'Apoyos y labores'],
                ['id' => 'action:actividades_rango', 'title' => 'Actividades por rango', 'description' => 'Consultar por fechas'],
                ['id' => 'action:personal_armado', 'title' => 'Personal armado', 'description' => 'Relación actual'],
                ['id' => 'action:personal_activo', 'title' => 'Personal activo', 'description' => 'Listado del personal'],
                ['id' => 'action:expediente_personal', 'title' => 'Expediente de personal', 'description' => 'Foto, patrulla y datos'],
            ];
        } elseif ($module === 'fomento') {
            $rows = [
                ['id' => 'action:actividades_hoy', 'title' => 'Actividades de hoy', 'description' => 'Proximidad y labores'],
                ['id' => 'action:actividades_rango', 'title' => 'Actividades por rango', 'description' => 'Consultar por fechas'],
                ['id' => 'action:personal_activo', 'title' => 'Personal activo', 'description' => 'Listado del personal'],
                ['id' => 'action:expediente_personal', 'title' => 'Expediente de personal', 'description' => 'Foto, patrulla y datos'],
            ];
        } elseif ($module === 'coordinacion' || $module === 'seguridad_vial') {
            $rows = [
                ['id' => 'action:estadisticas_rapidas', 'title' => 'Estadísticas rápidas', 'description' => 'Resumen de hechos'],
                ['id' => 'action:actividades_hoy', 'title' => 'Actividades de hoy', 'description' => 'Apoyos y labores'],
                ['id' => 'action:operativos_hoy', 'title' => 'Operativos de hoy', 'description' => 'Dispositivos de carreteras'],
                ['id' => 'action:personal_armado', 'title' => 'Personal armado', 'description' => 'Relación actual'],
                ['id' => 'action:personal_activo', 'title' => 'Personal activo', 'description' => 'Listado del personal'],
                ['id' => 'action:expediente_personal', 'title' => 'Expediente de personal', 'description' => 'Foto, patrulla y datos'],
                ['id' => 'action:puestas_hoy', 'title' => 'Puestas de hoy', 'description' => 'Listado de puestas'],
            ];
        } else {
            $rows = [
                ['id' => 'action:actividades_hoy', 'title' => 'Actividades de hoy', 'description' => 'Consulta rápida'],
                ['id' => 'action:personal_activo', 'title' => 'Personal activo', 'description' => 'Listado del personal'],
                ['id' => 'action:expediente_personal', 'title' => 'Expediente de personal', 'description' => 'Foto, patrulla y datos'],
            ];
        }

        return [
            'text' => "{$text}\n\nPuedes escribir MENÚ para reiniciar.",
            'interactive' => [
                'type' => 'list',
                'header' => [
                    'type' => 'text',
                    'text' => $title,
                ],
                'body' => [
                    'text' => 'Selecciona una opción',
                ],
                'footer' => [
                    'text' => 'Consultas disponibles',
                ],
                'action' => [
                    'button' => 'Ver opciones',
                    'sections' => [
                        [
                            'title' => $title,
                            'rows' => $rows,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function buildQuickStatsMenu($user, array $context, ?string $message = null): array
    {
        $text = $message ?: 'Selecciona la estadística rápida que deseas consultar.';

        return [
            'text' => "{$text}\n\nPuedes escribir MENÚ para reiniciar.",
            'interactive' => [
                'type' => 'list',
                'header' => [
                    'type' => 'text',
                    'text' => 'Estadísticas rápidas',
                ],
                'body' => [
                    'text' => 'Elige una consulta',
                ],
                'footer' => [
                    'text' => 'Siniestros',
                ],
                'action' => [
                    'button' => 'Ver opciones',
                    'sections' => [
                        [
                            'title' => 'Consultas rápidas',
                            'rows' => [
                                [
                                    'id' => 'action:estadistica_resumen_general',
                                    'title' => 'Resumen general',
                                    'description' => 'Hechos, lesionados, fallecidos y situación',
                                ],
                                [
                                    'id' => 'action:estadistica_motocicletas',
                                    'title' => 'Motocicletas',
                                    'description' => 'Hechos con motocicletas',
                                ],
                                [
                                    'id' => 'action:estadistica_lesionados',
                                    'title' => 'Lesionados',
                                    'description' => 'Total de lesionados',
                                ],
                                [
                                    'id' => 'action:estadistica_fallecidos',
                                    'title' => 'Fallecidos',
                                    'description' => 'Total de fallecidos',
                                ],
                                [
                                    'id' => 'action:estadistica_situacion',
                                    'title' => 'Por situación',
                                    'description' => 'Resuelto, pendiente, turnado o reporte',
                                ],
                                [
                                    'id' => 'action:estadistica_tipo_hecho',
                                    'title' => 'Por tipo de hecho',
                                    'description' => 'Volcadura, objeto fijo y más',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function buildQuickStatsPeriodMenu(string $action, ?string $message = null): array
    {
        $text = $message ?: 'Selecciona el periodo que deseas consultar.';

        return [
            'text' => "{$text}\n\nPuedes escribir MENÚ para reiniciar.",
            'interactive' => [
                'type' => 'list',
                'header' => [
                    'type' => 'text',
                    'text' => 'Periodo',
                ],
                'body' => [
                    'text' => 'Elige un periodo',
                ],
                'footer' => [
                    'text' => 'Estadísticas rápidas',
                ],
                'action' => [
                    'button' => 'Ver opciones',
                    'sections' => [
                        [
                            'title' => 'Periodos',
                            'rows' => [
                                [
                                    'id' => "period:{$action}:hoy",
                                    'title' => 'Hoy',
                                    'description' => 'Solo el día actual',
                                ],
                                [
                                    'id' => "period:{$action}:ayer",
                                    'title' => 'Ayer',
                                    'description' => 'Solo el día anterior',
                                ],
                                [
                                    'id' => "period:{$action}:este_mes",
                                    'title' => 'Este mes',
                                    'description' => 'Del 1 al día actual',
                                ],
                                [
                                    'id' => "period:{$action}:mes_anterior",
                                    'title' => 'Mes anterior',
                                    'description' => 'Mes completo anterior',
                                ],
                                [
                                    'id' => "period:{$action}:personalizado",
                                    'title' => 'Rango personalizado',
                                    'description' => 'Escribir fechas',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function buildSituacionMenu(?string $message = null): array
    {
        $text = $message ?: 'Selecciona la situación que deseas consultar.';

        return [
            'text' => "{$text}\n\nPuedes escribir MENÚ para reiniciar.",
            'interactive' => [
                'type' => 'list',
                'header' => [
                    'type' => 'text',
                    'text' => 'Situación',
                ],
                'body' => [
                    'text' => 'Elige una situación',
                ],
                'footer' => [
                    'text' => 'Siniestros',
                ],
                'action' => [
                    'button' => 'Ver opciones',
                    'sections' => [
                        [
                            'title' => 'Situación',
                            'rows' => [
                                [
                                    'id' => 'filter:situacion:RESUELTO',
                                    'title' => 'Resuelto',
                                    'description' => 'Hechos resueltos',
                                ],
                                [
                                    'id' => 'filter:situacion:PENDIENTE',
                                    'title' => 'Pendiente',
                                    'description' => 'Hechos pendientes',
                                ],
                                [
                                    'id' => 'filter:situacion:TURNADO',
                                    'title' => 'Turnado',
                                    'description' => 'Hechos turnados',
                                ],
                                [
                                    'id' => 'filter:situacion:REPORTE',
                                    'title' => 'Reporte',
                                    'description' => 'Hechos en reporte',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function buildTipoHechoMenu(?string $message = null): array
    {
        $text = $message ?: 'Selecciona el tipo de hecho que deseas consultar.';

        return [
            'text' => "{$text}\n\nPuedes escribir MENÚ para reiniciar.",
            'interactive' => [
                'type' => 'list',
                'header' => [
                    'type' => 'text',
                    'text' => 'Tipo de hecho',
                ],
                'body' => [
                    'text' => 'Elige un tipo',
                ],
                'footer' => [
                    'text' => 'Siniestros',
                ],
                'action' => [
                    'button' => 'Ver opciones',
                    'sections' => [
                        [
                            'title' => 'Tipos de hecho',
                            'rows' => [
                                [
                                    'id' => 'filter:tipo_hecho:COLISIÓN POR CAMBIO DE CARRIL',
                                    'title' => 'Cambio de carril',
                                    'description' => 'Colisión por cambio de carril',
                                ],
                                [
                                    'id' => 'filter:tipo_hecho:COLISIÓN POR ALCANCE',
                                    'title' => 'Alcance',
                                    'description' => 'Colisión por alcance',
                                ],
                                [
                                    'id' => 'filter:tipo_hecho:VOLCADURA',
                                    'title' => 'Volcadura',
                                    'description' => 'Hechos tipo volcadura',
                                ],
                                [
                                    'id' => 'filter:tipo_hecho:COLISIÓN CONTRA OBJETO FIJO',
                                    'title' => 'Objeto fijo',
                                    'description' => 'Colisión contra objeto fijo',
                                ],
                                [
                                    'id' => 'filter:tipo_hecho:ATROPELLAMIENTO',
                                    'title' => 'Atropellamiento',
                                    'description' => 'Hechos por atropellamiento',
                                ],
                                [
                                    'id' => 'filter:tipo_hecho:CAÍDA DE MOTOCICLETA',
                                    'title' => 'Caída de motocicleta',
                                    'description' => 'Hechos de motocicleta',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function buildOperativoTipoMenu(?string $message = null): array
    {
        $text = $message ?: 'Selecciona el tipo de operativo o dispositivo.';
        $rows = [];

        foreach ($this->guardianesDispositivos() as $dispositivo) {
            $nombre = (string) ($dispositivo['nombre'] ?? '');

            if ($nombre === '') {
                continue;
            }

            $rows[] = [
                'id' => 'filter:tipo_operativo:' . $nombre,
                'title' => (string) ($dispositivo['titulo_menu'] ?? $nombre),
                'description' => (string) ($dispositivo['descripcion_menu'] ?? 'Dispositivo de carreteras'),
            ];
        }

        $sections = [];

        foreach (array_chunk($rows, 10) as $index => $chunk) {
            $sections[] = [
                'title' => $index === 0 ? 'Dispositivos' : 'Dispositivos ' . ($index + 1),
                'rows' => $chunk,
            ];
        }

        return [
            'text' => "{$text}\n\nPuedes escribir MENÚ para reiniciar.",
            'interactive' => [
                'type' => 'list',
                'header' => [
                    'type' => 'text',
                    'text' => 'Tipo de operativo',
                ],
                'body' => [
                    'text' => 'Elige un tipo',
                ],
                'footer' => [
                    'text' => 'Carreteras',
                ],
                'action' => [
                    'button' => 'Ver opciones',
                    'sections' => $sections,
                ],
            ],
        ];
    }

    public function resolveModuleSelection(array $input, array $context): ?string
    {
        $value = trim((string) ($input['value'] ?? ''));
        $value = mb_strtolower($value, 'UTF-8');

        if ($this->startsWith($value, 'module:')) {
            $value = substr($value, 7);
        }

        if (in_array($value, $context['modules'] ?? [], true)) {
            return $value;
        }

        return null;
    }

    public function resolveActionSelection(array $input, string $module, array $context): ?array
    {
        $value = trim((string) ($input['value'] ?? ''));
        $value = mb_strtolower($value, 'UTF-8');

        if ($this->startsWith($value, 'action:')) {
            $value = substr($value, 7);
        }

        $map = [
            'hechos_hoy' => ['key' => 'hechos_hoy', 'requires_param' => false],
            'hechos_placas' => ['key' => 'hechos_placas', 'requires_param' => true, 'param_type' => 'placas'],
            'detalle_folio' => ['key' => 'detalle_folio', 'requires_param' => true, 'param_type' => 'folio'],
            'mis_hechos_hoy' => ['key' => 'mis_hechos_hoy', 'requires_param' => false],
            'mis_hechos_placas' => ['key' => 'mis_hechos_placas', 'requires_param' => true, 'param_type' => 'placas'],
            'mi_detalle_folio' => ['key' => 'mi_detalle_folio', 'requires_param' => true, 'param_type' => 'folio'],
            'estadisticas_rapidas' => ['key' => 'estadisticas_rapidas', 'requires_param' => false],
            'estadistica_resumen_general' => ['key' => 'estadistica_resumen_general', 'requires_param' => false],
            'estadistica_motocicletas' => ['key' => 'estadistica_motocicletas', 'requires_param' => false],
            'estadistica_lesionados' => ['key' => 'estadistica_lesionados', 'requires_param' => false],
            'estadistica_fallecidos' => ['key' => 'estadistica_fallecidos', 'requires_param' => false],
            'estadistica_situacion' => ['key' => 'estadistica_situacion', 'requires_param' => false],
            'estadistica_tipo_hecho' => ['key' => 'estadistica_tipo_hecho', 'requires_param' => false],
            'personal_armado' => ['key' => 'personal_armado', 'requires_param' => false],
            'personal_activo' => ['key' => 'personal_activo', 'requires_param' => false],
            'expediente_personal' => ['key' => 'expediente_personal', 'requires_param' => true, 'param_type' => 'personal'],
            'actividades_hoy' => ['key' => 'actividades_hoy', 'requires_param' => false],
            'actividades_rango' => ['key' => 'actividades_rango', 'requires_param' => true, 'param_type' => 'rango_fechas'],
            'operativos_hoy' => ['key' => 'operativos_hoy', 'requires_param' => false],
            'operativos_tipo' => ['key' => 'operativos_tipo', 'requires_param' => true, 'param_type' => 'tipo_operativo'],
            'puestas_hoy' => ['key' => 'puestas_hoy', 'requires_param' => false],
            'no_disponible' => ['key' => 'no_disponible', 'requires_param' => false],
        ];

        return $map[$value] ?? null;
    }

    public function resolvePeriodSelection(array $input): ?array
    {
        $value = trim((string) ($input['value'] ?? ''));

        if (!$this->startsWith($value, 'period:')) {
            return null;
        }

        $parts = explode(':', $value, 3);

        if (count($parts) !== 3) {
            return null;
        }

        return [
            'action' => $parts[1],
            'period' => $parts[2],
        ];
    }

    public function resolveFilterSelection(array $input): ?array
    {
        $value = trim((string) ($input['value'] ?? ''));

        if (!$this->startsWith($value, 'filter:')) {
            return null;
        }

        $parts = explode(':', $value, 3);

        if (count($parts) !== 3) {
            return null;
        }

        return [
            'field' => $parts[1],
            'value' => $parts[2],
        ];
    }

    public function buildActionPrompt(string $module, string $action, array $context, ?string $message = null): array
    {
        if (in_array($action, ['hechos_placas', 'mis_hechos_placas'], true)) {
            $text = "Escribe las placas.\n\nEjemplo:\nABC123";
        } elseif (in_array($action, ['detalle_folio', 'mi_detalle_folio'], true)) {
            $text = "Escribe el ID del hecho.\n\nEjemplo:\n59564";
        } elseif ($action === 'actividades_rango') {
            $text = "Escribe el rango de fechas.\n\nEjemplo:\n2026-04-01 al 2026-04-15";
        } elseif ($action === 'operativos_tipo') {
            $text = "Escribe el tipo de operativo.\n\nEjemplo:\nCASCO";
        } elseif ($action === 'expediente_personal') {
            $text = "Escribe el nombre, número de empleado, CUP, CUIP, CURP o RFC.\n\nEjemplo:\nJuan Pérez";
        } elseif (in_array($action, [
            'estadistica_resumen_general',
            'estadistica_motocicletas',
            'estadistica_lesionados',
            'estadistica_fallecidos',
        ], true)) {
            $text = "Selecciona el periodo a consultar.";
        } elseif ($action === 'estadistica_situacion') {
            $text = "Selecciona primero la situación y después el periodo.";
        } elseif ($action === 'estadistica_tipo_hecho') {
            $text = "Selecciona primero el tipo de hecho y después el periodo.";
        } else {
            $text = 'Escribe el dato solicitado.';
        }

        if ($message) {
            $text = $message . "\n\n" . $text;
        }

        return [
            'text' => $text,
        ];
    }

    protected function moduleTitle(string $module): string
    {
        switch ($module) {
            case 'siniestros':
                return 'Siniestros';
            case 'carreteras':
                return 'Carreteras';
            case 'vialidades':
                return 'Vialidades Urbanas';
            case 'delegaciones':
                return 'Delegaciones';
            case 'fomento':
                return 'Fomento a la Cultura Vial';
            case 'coordinacion':
            case 'seguridad_vial':
                return 'Coordinación';
            default:
                return 'Consultas';
        }
    }

    protected function moduleDescription(string $module): string
    {
        switch ($module) {
            case 'siniestros':
                return 'Hechos, personal, puestas y estadísticas';
            case 'carreteras':
                return 'Actividades, dispositivos y personal';
            case 'vialidades':
                return 'Actividades y personal';
            case 'delegaciones':
                return 'Actividades y personal';
            case 'fomento':
                return 'Proximidad social y actividades';
            case 'coordinacion':
            case 'seguridad_vial':
                return 'Consulta general';
            default:
                return 'Consultas disponibles';
        }
    }

    protected function guardianesDispositivos(): array
    {
        $dispositivos = config('guardianes_camino.dispositivos', []);

        return is_array($dispositivos) ? $dispositivos : [];
    }

    protected function startsWith(string $value, string $prefix): bool
    {
        return substr($value, 0, strlen($prefix)) === $prefix;
    }
}
