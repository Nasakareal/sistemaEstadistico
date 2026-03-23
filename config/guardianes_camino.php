<?php

return [
    'dispositivos' => [
        'PSV (PUESTO DE SEGURIDAD Y VIGILANCIA)' => [
            'titulo' => 'PSV (Puesto de Seguridad y Vigilancia)',
            'campos' => [
                'cantidad',
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
                'cantidad',
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
                'cantidad',
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
                'cantidad',
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
                'cantidad',
                'vehiculos_impactados',
                'estado_fuerza_participante',
                'crps_participantes',
                'kilometros_recorridos',
            ],
        ],

        'CORDILLERA' => [
            'titulo' => 'Cordillera',
            'campos' => [
                'cantidad',
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
                'cantidad',
                'vehiculos_impactados',
                'personas_impactadas',
                'estado_fuerza_participante',
                'crps_participantes',
                'kilometros_recorridos',
            ],
        ],

        'CABALLEROS DEL CAMINO' => [
            'titulo' => 'Caballeros del Camino',
            'campos' => [
                'cantidad',
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
        'cantidad',
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
    ],
];
