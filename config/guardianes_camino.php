<?php

return [
    'dispositivos' => [
        'PSV (PUESTO DE SEGURIDAD Y VIGILANCIA)' => [
            'titulo' => 'PSV (Puesto de Seguridad y Vigilancia)',
            'campos' => [
                'vehiculos_inspeccionados',
                'personas_inspeccionadas',
                'estado_fuerza_participante',
                'crps_participantes',
                'kilometros_recorridos',
            ],
        ],

        'RSV (RECORRIDOS DE SEGURIDAD Y VIGILANCIA - PATRULLAJE)' => [
            'titulo' => 'RSV (Recorridos de Seguridad y Vigilancia - Patrullaje)',
            'campos' => [
                'vehiculos_inspeccionados',
                'personas_inspeccionadas',
                'estado_fuerza_participante',
                'crps_participantes',
                'kilometros_recorridos',
            ],
        ],

        'CASCO' => [
            'titulo' => 'Dispositivo Casco',
            'campos' => [
                'vehiculos_impactados',
                'personas_impactadas',
                'estado_fuerza_participante',
                'crps_participantes',
                'kilometros_recorridos',
            ],
        ],

        'CINTURÓN' => [
            'titulo' => 'Dispositivo Cinturón',
            'campos' => [
                'vehiculos_impactados',
                'personas_impactadas',
                'estado_fuerza_participante',
                'crps_participantes',
                'kilometros_recorridos',
            ],
        ],

        'CARRUSEL' => [
            'titulo' => 'Dispositivo Carrusel',
            'campos' => [
                'vehiculos_impactados',
                'estado_fuerza_participante',
                'crps_participantes',
                'kilometros_recorridos',
            ],
        ],

        'CORDILLERA' => [
            'titulo' => 'Cordillera',
            'campos' => [
                'vehiculos_impactados',
                'personas_impactadas',
                'estado_fuerza_participante',
                'crps_participantes',
                'kilometros_recorridos',
            ],
        ],

        'ASIENTO SEGURO PASAJEROS MENORES' => [
            'titulo' => 'Dispositivo Asiento Seguro Pasajeros Menores',
            'campos' => [
                'vehiculos_impactados',
                'personas_impactadas',
                'estado_fuerza_participante',
                'crps_participantes',
                'kilometros_recorridos',
            ],
        ],

        'ACOMPAÑAMIENTOS' => [
            'titulo' => 'ACOMPAÑAMIENTOS (Escoltas, Caravanas, Emergencias, Otros)',
            'campos' => [
                'tipo_acompanamiento',
                'estado_fuerza_participante',
                'crps_participantes',
                'kilometros_recorridos',
            ],
        ],

        'ABANDERAMIENTOS' => [
            'titulo' => 'ABANDERAMIENTOS (Siniestros, Eventos, Otros)',
            'campos' => [
                'tipo_abanderamiento',
                'estado_fuerza_participante',
                'crps_participantes',
                'kilometros_recorridos',
            ],
        ],

        'AUXILIOS VIALES' => [
            'titulo' => 'AUXILIOS VIALES (Falla mecánica, Peatón, Otros)',
            'campos' => [
                'tipo_auxilio_vial',
                'estado_fuerza_participante',
                'crps_participantes',
                'kilometros_recorridos',
            ],
        ],






        'CABALLEROS DEL CAMINO' => [
            'titulo' => 'Caballeros del Camino',
            'campos' => [
                'acompanamientos',
                'abanderamientos',
                'auxilios_viales',
                'estado_fuerza_participante',
                'crps_participantes',
                'kilometros_recorridos',
            ],
        ],

        'PROXIMIDAD SOCIAL' => [
            'titulo' => 'Proximidad Social',
            'campos' => [
                'prox_empresas',
                'prox_tiendas_conveniencia',
                'prox_escuelas',
                'prox_hospitales',
            ],
        ],
    ],

    'all_campos' => [
        'vehiculos_inspeccionados',
        'personas_inspeccionadas',
        'vehiculos_impactados',
        'personas_impactadas',
        'estado_fuerza_participante',
        'crps_participantes',
        'kilometros_recorridos',
        'acompanamientos',
        'abanderamientos',
        'auxilios_viales',
        'prox_empresas',
        'prox_tiendas_conveniencia',
        'prox_escuelas',
        'prox_hospitales',
        'puestas_disposicion',
        'vehiculos_recuperados',
        'armas_aseguradas',
        'mercancia_recuperada',
        'decomiso_drogas',
        'antecedentes_personas',
        'antecedentes_vehiculos',
        'antecedentes_motos',
        'antecedentes_camiones',
        'tipo_acompanamiento',
        'tipo_abanderamiento',
        'tipo_auxilio_vial',
    ],
];
