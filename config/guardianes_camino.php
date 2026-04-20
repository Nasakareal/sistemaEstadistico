<?php

$camposResultados = [
    'puestas_disposicion',
    'vehiculos_recuperados',
    'armas_aseguradas',
    'mercancia_recuperada',
    'decomiso_drogas',
    'antecedentes_personas',
    'antecedentes_vehiculos',
    'antecedentes_motos',
    'antecedentes_camiones',
];

$camposFuerza = [
    'estado_fuerza_participante',
    'kilometros_recorridos',
    'crps_participantes',
];

$camposInspeccion = [
    'vehiculos_inspeccionados',
    'personas_inspeccionadas',
    'vehiculos_impactados',
    'personas_impactadas',
];

$camposProximidad = [
    'prox_empresas',
    'prox_tiendas_conveniencia',
    'prox_escuelas',
    'prox_hospitales',
];

$allCampos = array_values(array_unique(array_merge(
    $camposInspeccion,
    $camposFuerza,
    [
        'acompanamientos',
        'abanderamientos',
        'auxilios_viales',
        'tipo_acompanamiento',
        'tipo_abanderamiento',
        'tipo_auxilio_vial',
        'folio_atendido',
        'motivo_folio',
    ],
    $camposProximidad,
    $camposResultados
)));

$camposOperativo = array_values(array_unique(array_merge(
    $camposInspeccion,
    $camposFuerza,
    $camposResultados
)));

$camposApoyo = array_values(array_unique(array_merge(
    $camposFuerza,
    ['auxilios_viales'],
    $camposResultados
)));

return [
    'operativo_slug' => env('GUARDIANES_CAMINO_OPERATIVO_SLUG', 'guardianes-del-camino'),

    'all_campos' => $allCampos,

    /*
    |--------------------------------------------------------------------------
    | Dispositivos Guardianes del Camino
    |--------------------------------------------------------------------------
    |
    | Las llaves deben coincidir con operativo_dispositivo_catalogos.nombre.
    | El mismo catálogo alimenta el formulario web, Flutter, WhatsApp y los
    | filtros por tipo. Para agregar o quitar un dispositivo, actualiza aquí
    | su llave, título, campos y aliases.
    |
    */
    'dispositivos' => [
        'PSV (PUESTO DE SEGURIDAD Y VIGILANCIA)' => [
            'key' => 'psv',
            'nombre' => 'PSV (PUESTO DE SEGURIDAD Y VIGILANCIA)',
            'titulo' => 'PSV (PUESTO DE SEGURIDAD Y VIGILANCIA)',
            'titulo_menu' => 'PSV',
            'descripcion_menu' => 'Puesto de Seguridad y Vigilancia',
            'campos' => $camposOperativo,
            'aliases' => [
                'PSV',
                'PUESTO DE SEGURIDAD Y VIGILANCIA',
            ],
        ],

        'RSV (RECORRIDOS DE SEGURIDAD Y VIGILANCIA - PATRULLAJE)' => [
            'key' => 'rsv',
            'nombre' => 'RSV (RECORRIDOS DE SEGURIDAD Y VIGILANCIA - PATRULLAJE)',
            'titulo' => 'RSV (RECORRIDOS DE SEGURIDAD Y VIGILANCIA - PATRULLAJE)',
            'titulo_menu' => 'RSV',
            'descripcion_menu' => 'Recorridos de Seguridad y Vigilancia',
            'campos' => $camposOperativo,
            'aliases' => [
                'RSV',
                'RECORRIDOS DE SEGURIDAD Y VIGILANCIA',
                'PATRULLAJE',
            ],
        ],

        'CASCO' => [
            'key' => 'casco',
            'nombre' => 'CASCO',
            'titulo' => 'CASCO',
            'titulo_menu' => 'CASCO',
            'descripcion_menu' => 'Dispositivo casco',
            'campos' => $camposOperativo,
            'aliases' => [
                'CASCO',
                'DISPOSITIVO CASCO',
            ],
        ],

        'CINTURÓN' => [
            'key' => 'cinturon',
            'nombre' => 'CINTURÓN',
            'titulo' => 'CINTURÓN',
            'titulo_menu' => 'Cinturón',
            'descripcion_menu' => 'Dispositivo cinturón',
            'campos' => $camposOperativo,
            'aliases' => [
                'CINTURÓN',
                'CINTURON',
                'DISPOSITIVO CINTURÓN',
                'DISPOSITIVO CINTURON',
            ],
        ],

        'CARRUSEL' => [
            'key' => 'carrusel',
            'nombre' => 'CARRUSEL',
            'titulo' => 'CARRUSEL',
            'titulo_menu' => 'Carrusel',
            'descripcion_menu' => 'Dispositivo carrusel',
            'campos' => $camposOperativo,
            'aliases' => [
                'CARRUSEL',
                'DISPOSITIVO CARRUSEL',
            ],
        ],

        'CORDILLERA' => [
            'key' => 'cordillera',
            'nombre' => 'CORDILLERA',
            'titulo' => 'CORDILLERA',
            'titulo_menu' => 'Cordillera',
            'descripcion_menu' => 'Operativo cordillera',
            'campos' => $camposOperativo,
            'aliases' => [
                'CORDILLERA',
            ],
        ],

        'ASIENTO SEGURO PASAJEROS MENORES' => [
            'key' => 'asiento_seguro',
            'nombre' => 'ASIENTO SEGURO PASAJEROS MENORES',
            'titulo' => 'ASIENTO SEGURO PASAJEROS MENORES',
            'titulo_menu' => 'Asiento seguro',
            'descripcion_menu' => 'Pasajeros menores',
            'campos' => $camposOperativo,
            'aliases' => [
                'ASIENTO SEGURO',
                'ASIENTO SEGURO PASAJEROS MENORES',
            ],
        ],

        'CABALLERO DEL CAMINO (PROXIMIDAD SOCIAL)' => [
            'key' => 'caballero_camino',
            'nombre' => 'CABALLERO DEL CAMINO (PROXIMIDAD SOCIAL)',
            'titulo' => 'CABALLERO DEL CAMINO (PROXIMIDAD SOCIAL)',
            'titulo_menu' => 'Caballero camino',
            'descripcion_menu' => 'Proximidad social',
            'campos' => array_values(array_unique(array_merge(
                $camposApoyo,
                $camposProximidad
            ))),
            'aliases' => [
                'CABALLERO DEL CAMINO',
                'CABALLEROS DEL CAMINO',
                'PROXIMIDAD SOCIAL',
                'PROXIMIDAD CARRETERA',
            ],
        ],

        'ACOMPAÑAMIENTOS' => [
            'key' => 'acompanamientos',
            'nombre' => 'ACOMPAÑAMIENTOS',
            'titulo' => 'ACOMPAÑAMIENTOS',
            'titulo_menu' => 'Acompañamientos',
            'descripcion_menu' => 'Escoltas y caravanas',
            'campos' => array_values(array_unique(array_merge(
                $camposFuerza,
                ['acompanamientos', 'tipo_acompanamiento'],
                $camposResultados
            ))),
            'aliases' => [
                'ACOMPAÑAMIENTO',
                'ACOMPANAMIENTO',
                'ACOMPAÑAMIENTOS',
                'ACOMPANAMIENTOS',
                'ESCOLTA',
                'ESCOLTAS',
                'CARAVANA',
                'CARAVANAS',
            ],
        ],

        'ABANDERAMIENTOS' => [
            'key' => 'abanderamientos',
            'nombre' => 'ABANDERAMIENTOS',
            'titulo' => 'ABANDERAMIENTOS',
            'titulo_menu' => 'Abanderamientos',
            'descripcion_menu' => 'Eventos y siniestros',
            'campos' => array_values(array_unique(array_merge(
                $camposFuerza,
                ['abanderamientos', 'tipo_abanderamiento'],
                $camposResultados
            ))),
            'aliases' => [
                'ABANDERAMIENTO',
                'ABANDERAMIENTOS',
            ],
        ],

        'AUXILIOS VIALES' => [
            'key' => 'auxilios_viales',
            'nombre' => 'AUXILIOS VIALES',
            'titulo' => 'AUXILIOS VIALES',
            'titulo_menu' => 'Auxilios viales',
            'descripcion_menu' => 'Apoyo vial',
            'campos' => array_values(array_unique(array_merge(
                $camposFuerza,
                ['auxilios_viales', 'tipo_auxilio_vial'],
                $camposResultados
            ))),
            'aliases' => [
                'AUXILIO VIAL',
                'AUXILIOS VIALES',
                'APOYO VIAL',
                'APOYOS VIALES',
            ],
        ],

        'ATENCIÓN A REPORTES C5' => [
            'key' => 'reportes_c5',
            'nombre' => 'ATENCIÓN A REPORTES C5',
            'titulo' => 'ATENCIÓN A REPORTES C5',
            'titulo_menu' => 'Reportes C5',
            'descripcion_menu' => 'Folio atendido',
            'campos' => array_values(array_unique(array_merge(
                $camposFuerza,
                ['folio_atendido', 'motivo_folio'],
                $camposResultados
            ))),
            'aliases' => [
                'ATENCIÓN A REPORTES C5',
                'ATENCION A REPORTES C5',
                'REPORTES C5',
                'C5',
            ],
        ],
    ],
];
