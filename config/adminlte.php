<?php

return [

    'title' => 'Seguridad Vial',
    'title_prefix' => '',
    'title_postfix' => '',

    'use_ico_only' => true,
    'use_full_favicon' => false,

    'google_fonts' => [
        'allowed' => true,
    ],

    'logo' => '<b style="color:white;">Seguridad Vial</b>',
    'logo_img' => 'guardiacivil.png',
    'logo_img_class' => 'brand-image img-circle elevation-3',
    'logo_img_xl' => null,
    'logo_img_xl_class' => 'brand-image-xs',
    'logo_img_alt' => 'Seguridad Vial',

    'auth_logo' => [
        'enabled' => false,
        'img' => [
            'path' => 'vendor/adminlte/dist/img/AdminLTELogo.png',
            'alt' => 'Auth Logo',
            'class' => '',
            'width' => 50,
            'height' => 50,
        ],
    ],

    'preloader' => [
        'enabled' => true,
        'mode' => 'fullscreen',
        'img' => [
            'path' => 'guardiacivil.png',
            'alt' => 'Seguridad Vial',
            'effect' => 'animation__shake',
            'class' => 'custom-preloader-img',
            'width' => 300,
            'height' => 300,
        ],
    ],

    'usermenu_enabled' => true,
    'usermenu_header' => false,
    'usermenu_header_class' => 'bg-primary',
    'usermenu_image' => false,
    'usermenu_desc' => false,
    'usermenu_profile_url' => false,

    'layout_topnav' => null,
    'layout_boxed' => null,
    'layout_fixed_sidebar' => true,
    'layout_fixed_navbar' => true,
    'layout_fixed_footer' => null,
    'layout_dark_mode' => true,

    'classes_auth_card' => 'card-outline card-primary',
    'classes_auth_header' => '',
    'classes_auth_body' => '',
    'classes_auth_footer' => '',
    'classes_auth_icon' => '',
    'classes_auth_btn' => 'btn-flat btn-primary',

    'classes_body' => 'sv-body',
    'classes_brand' => 'sv-brand',
    'classes_brand_text' => 'sv-brand-text',
    'classes_content_wrapper' => 'sv-content-wrapper',
    'classes_content_header' => 'sv-content-header',
    'classes_content' => 'sv-content',
    'classes_sidebar' => 'sidebar-dark-primary sv-sidebar',
    'classes_sidebar_nav' => 'sv-sidebar-nav',
    'classes_topnav' => 'navbar-dark sv-topnav',
    'classes_topnav_nav' => 'navbar-expand',
    'classes_topnav_container' => 'container-fluid',

    'sidebar_mini' => 'lg',
    'sidebar_collapse' => false,
    'sidebar_collapse_auto_size' => false,
    'sidebar_collapse_remember' => false,
    'sidebar_collapse_remember_no_transition' => true,
    'sidebar_scrollbar_theme' => 'os-theme-light',
    'sidebar_scrollbar_auto_hide' => 'l',
    'sidebar_nav_accordion' => true,
    'sidebar_nav_animation_speed' => 300,

    'right_sidebar' => false,
    'right_sidebar_icon' => 'fas fa-cogs',
    'right_sidebar_theme' => 'dark',
    'right_sidebar_slide' => true,
    'right_sidebar_push' => true,
    'right_sidebar_scrollbar_theme' => 'os-theme-light',
    'right_sidebar_scrollbar_auto_hide' => 'l',

    'use_route_url' => false,
    'dashboard_url' => 'home',
    'logout_url' => 'logout',
    'login_url' => 'login',
    'register_url' => 'register',
    'password_reset_url' => 'password/reset',
    'password_email_url' => 'password/email',
    'profile_url' => false,
    'disable_darkmode_routes' => false,

    'laravel_asset_bundling' => false,
    'laravel_css_path' => 'css/app.css',
    'laravel_js_path' => 'js/app.js',

    'menu' => [

        [
            'type' => 'link',
            'text' => 'Waze',
            'route' => 'waze.alerts.index',
            'topnav_right' => true,
            'icon' => 'fas fa-bell',
            'id'   => 'wazeBellLink',
            'waze_badge' => true,
        ],
        [
            'type' => 'link',
            'text' => 'Revisión',
            'route' => 'hechos.pendientes_revision',
            'topnav_right' => true,
            'icon' => 'fas fa-bell',
            'id'   => 'hechosRevisionBell',
            'can'  => 'menu-hechos-pendientes-revision',
        ],
        [
            'type' => 'link',
            'text' => 'Guardianes',
            'route' => 'guardianes_camino.dispositivos.pendientes_revision',
            'topnav_right' => true,
            'icon' => 'fas fa-bell',
            'id'   => 'guardianesBell',
            'can'  => 'menu-guardianes-pendientes-revision',
        ],
        [
            'type' => 'fullscreen-widget',
            'topnav_right' => true,
        ],
        [
            'text'        => 'Perfil',
            'route'       => 'profile',
            'icon'        => 'fas fa-fw fa-user',
            'topnav_user' => true,
        ],
        [
            'text'        => 'Cambiar Contraseña',
            'route'       => 'password.change',
            'icon'        => 'fas fa-fw fa-lock',
            'topnav_user' => true,
        ],

        [
            'text'    => 'Siniestros',
            'icon'    => 'fa-solid fa-car-side',
            'classes' => 'bg-blue text-white',
            'can'     => 'ver hechos',
            'submenu' => [
                [
                    'text'    => 'Listado de Siniestros',
                    'icon'    => 'fa-solid fa-car-side',
                    'classes' => 'text-white',
                    'url'     => 'hechos',
                    'can'     => 'ver hechos',
                ],
                [
                    'text'    => 'Seguimiento de Siniestros',
                    'icon'    => 'fa-solid fa-chart-line',
                    'classes' => 'text-white',
                    'url'     => 'hechos/seguimiento',
                    'can'     => 'ver hechos',
                ],
                [
                    'text'    => 'Añadir un siniestro',
                    'icon'    => 'fa-solid fa-plus',
                    'classes' => 'text-white',
                    'url'     => 'hechos/create',
                    'can'     => 'crear hechos',
                ],
                [
                    'text'    => 'Búsqueda',
                    'icon'    => 'fas fa-search',
                    'classes' => 'text-white',
                    'url'     => 'busqueda',
                    'can'     => 'ver hechos',
                ],
                [
                    'text'    => 'Cortes Pendientes Siniestros',
                    'icon'    => 'fa-solid fa-clipboard-list',
                    'classes' => 'text-white',
                    'route'   => 'hechos.pendientes.cortes.index',
                    'can'     => 'menu-pendientes-cortes-siniestros',
                ],
                [
                    'text'    => 'Cortes Pendientes Delegaciones',
                    'icon'    => 'fa-solid fa-clipboard-list',
                    'classes' => 'text-white',
                    'route'   => 'hechos.pendientes.delegaciones.cortes.index',
                    'can'     => 'menu-pendientes-cortes-delegaciones',
                ],
            ],
        ],
        /*
        [
            'text'    => 'Pase de Lista',
            'icon'    => 'fas fa-user-check',
            'classes' => 'bg-blue text-white',
            'can'     => 'ver listas',
            'submenu' => [
                [
                    'text'    => 'Pases de lista',
                    'icon'    => 'fa-solid fa-clipboard-list',
                    'classes' => 'text-white',
                    'url'     => 'listas',
                    'can'     => 'ver listas',
                ],
                [
                    'text'    => 'Añadir un Pase',
                    'icon'    => 'fa-solid fa-plus',
                    'classes' => 'text-white',
                    'url'     => 'listas/create',
                    'can'     => 'crear listas',
                ],
            ],
        ],
        */
        [
            'text'    => 'Puestas a Disposición',
            'icon'    => 'fas fa-gavel',
            'classes' => 'bg-blue text-white',
            'can'     => 'menu-puestas-disposicion',
            'submenu' => [
                [
                    'text'    => 'Listado de Puestas a Disposición',
                    'icon'    => 'fas fa-folder-open',
                    'classes' => 'text-white',
                    'url'     => 'puestas-disposicion',
                    'can'     => 'ver puestas a disposicion',
                ],
                [
                    'text'    => 'Agregar Puesta a Disposición',
                    'icon'    => 'fa-solid fa-plus',
                    'classes' => 'text-white',
                    'url'     => 'puestas-disposicion/create',
                    'can'     => 'menu-puestas-disposicion-crear',
                ],
                [
                    'text'    => 'Listado de Dictámenes',
                    'icon'    => 'fas fa-gavel',
                    'classes' => 'text-white',
                    'url'     => 'dictamenes',
                    'can'     => 'menu-dictamenes',
                ],
                [
                    'text'    => 'Solicitar número Dictamen',
                    'icon'    => 'fa-solid fa-plus',
                    'classes' => 'text-white',
                    'url'     => 'dictamenes/create',
                    'can'     => 'menu-dictamenes-crear',
                ],
            ],
        ],
        [
            'text'    => 'Actividades',
            'icon'    => 'fas fa-tasks',
            'classes' => 'bg-blue text-white',
            'can'     => 'menu-actividades',
            'submenu' => [
                [
                    'text'    => 'Listado de Actividades',
                    'icon'    => 'fas fa-tasks',
                    'classes' => 'text-white',
                    'url'     => 'actividades',
                ],
                [
                    'text'    => 'Añadir Actividad',
                    'icon'    => 'fa-solid fa-plus',
                    'classes' => 'text-white',
                    'url'     => 'actividades/create',
                ],
            ],
        ],
        /*
        [
            'text'    => 'Operativos',
            'icon'    => 'fa-solid fa-shield-halved',
            'classes' => 'bg-blue text-white',
            'can'     => 'ver operativos',
            'submenu' => [
                [
                    'text'    => 'Listado de Operativos',
                    'icon'    => 'fa-solid fa-list',
                    'classes' => 'text-white',
                    'url'     => 'operativos',
                    'can'     => 'ver operativos',
                ],
                [
                    'text'    => 'Añadir Operativo',
                    'icon'    => 'fa-solid fa-plus',
                    'classes' => 'text-white',
                    'url'     => 'operativos/create',
                    'can'     => 'crear operativos',
                ],
            ],
        ],
        */
        [
            'text'    => 'Grúas',
            'icon'    => 'fa-solid fa-truck-moving',
            'classes' => 'bg-blue text-white',
            'can'     => 'ver gruas',
            'submenu' => [
                [
                    'text'    => 'Listado de Grúas',
                    'icon'    => 'fa-solid fa-truck-moving',
                    'classes' => 'text-white',
                    'url'     => 'gruas',
                    'can'     => 'ver gruas',
                ],
                [
                    'text'    => 'Vehículos en Corralón',
                    'icon'    => 'fa-solid fa-warehouse',
                    'classes' => 'text-white',
                    'route'   => 'liberaciones_corralon.index',
                    'can'     => 'ver gruas',
                ],
                [
                    'text'    => 'Usuarios de Grúa',
                    'icon'    => 'fa-solid fa-user-lock',
                    'classes' => 'text-white',
                    'route'   => 'grua_usuarios.index',
                    'can'     => 'crear gruas',
                ],
                [
                    'text'    => 'Tramos',
                    'icon'    => 'fa-solid fa-road',
                    'classes' => 'text-white',
                    'url'     => 'tramos',
                    'can'     => 'ver gruas',
                ],
                [
                    'text'    => 'Tramos Lookup',
                    'icon'    => 'fa-solid fa-magnifying-glass-location',
                    'classes' => 'text-white',
                    'url'     => 'tramos-lookup',
                    'can'     => 'ver gruas',
                ],
                [
                    'text'    => 'Ver Gráfico de Servicios',
                    'icon'    => 'fa-solid fa-chart-line',
                    'classes' => 'text-white',
                    'url'     => 'servicios/grafico',
                    'can'     => 'ver gruas',
                ],
                [
                    'text'    => 'Calendario SCT',
                    'icon'    => 'fa-solid fa-calendar-days',
                    'classes' => 'text-white',
                    'url'     => 'grua-guardias-sct',
                    'can'     => 'ver gruas',
                ],
            ],
        ],

        [
            'text'    => 'Formatos',
            'icon'    => 'fas fa-file-alt',
            'classes' => 'bg-blue text-white',
            'can'     => 'ver formatos',
            'submenu' => [
                [
                    'text'    => 'Listado de Formatos',
                    'icon'    => 'fas fa-file-alt',
                    'classes' => 'text-white',
                    'url'     => 'formatos',
                    'can'     => 'ver formatos',
                ],
                [
                    'text'    => 'Subir Formato',
                    'icon'    => 'fa-solid fa-plus',
                    'classes' => 'text-white',
                    'url'     => 'formatos/create',
                    'can'     => 'crear formatos',
                ],
            ],
        ],

        [
            'text'    => 'Vialidades Urbanas',
            'icon'    => 'fa-solid fa-traffic-light',
            'classes' => 'bg-blue text-white',
            'can'     => 'menu-vialidades-urbanas',
            'submenu' => [
                [
                    'text'    => 'Panel Vialidades Urbanas',
                    'icon'    => 'fa-solid fa-list-check',
                    'classes' => 'text-white',
                    'url'     => 'vialidades-urbanas',
                    'can'     => 'menu-vialidades-urbanas',
                ],
                [
                    'text'    => 'Resumen',
                    'icon'    => 'fa-solid fa-chart-column',
                    'classes' => 'text-white',
                    'url'     => 'vialidades-urbanas/1/resumen',
                    'can'     => 'menu-vialidades-urbanas',
                ],
                [
                    'text'    => 'Estadísticas Vialidades',
                    'icon'    => 'fa-solid fa-file-excel',
                    'classes' => 'text-info',
                    'route'   => 'settings.estadisticas_vialidad.index',
                    'can'     => 'menu-vialidades-urbanas',
                ],
            ],
        ],
        [
            'text'    => 'Oficios',
            'icon'    => 'fas fa-envelope-open-text',
            'classes' => 'bg-blue text-white',
            'can'     => 'ver oficios',
            'submenu' => [
                [
                    'text'    => 'Listado de Oficios',
                    'icon'    => 'fas fa-envelope-open-text',
                    'classes' => 'text-white',
                    'url'     => 'admin/settings/oficios',
                    'can'     => 'ver oficios',
                ],
                [
                    'text'    => 'Subir Oficio',
                    'icon'    => 'fa-solid fa-plus',
                    'classes' => 'text-white',
                    'url'     => 'admin/settings/oficios/create',
                    'can'     => 'crear oficios',
                ],
            ],
        ],
        [
            'text'    => 'Estadísticas Globales',
            'icon'    => 'fa-solid fa-chart-column',
            'classes' => 'bg-blue text-white',
            'can'     => 'menu-estadisticas-generales',
            'submenu' => [

                [
                    'text' => 'Siniestros',
                    'icon' => 'fa-solid fa-car-burst',
                    'can'  => 'menu-estadisticas-siniestros',
                    'submenu' => [
                        [
                            'text'    => 'Panel Global',
                            'icon'    => 'fa-solid fa-chart-line',
                            'classes' => 'text-white',
                            'url'     => 'estadisticas-globales',
                            'can'     => 'menu-estadisticas-globales',
                        ],
                        [
                            'text'    => 'Resumen Ejecutivo',
                            'route'   => 'resumen_ejecutivo.index',
                            'icon'    => 'fa-solid fa-chart-line',
                            'can'     => 'ver estadisticas',
                        ],
                        [
                            'text'    => 'Actividades',
                            'icon'    => 'fa-solid fa-clipboard-list',
                            'classes' => 'text-info',
                            'url'     => 'estadisticas-actividades',
                            'can'     => 'menu-estadisticas-actividades-siniestros',
                        ],
                        [
                            'text'    => 'Aseguramientos',
                            'icon'    => 'fa-solid fa-boxes-stacked',
                            'classes' => 'text-info',
                            'url'     => 'estadisticas-aseguramientos?unidad_slug=siniestros',
                            'can'     => 'menu-estadisticas-siniestros',
                        ],
                        [
                            'text'    => 'Mapa de Choques por Zona',
                            'route'   => 'hechos.zonas.index',
                            'icon'    => 'fa-solid fa-draw-polygon',
                            'can'     => 'ver mapa',
                        ],
                    ],
                ],

                [
                    'text' => 'Fomento a la Cultura Vial',
                    'icon' => 'fa-solid fa-school',
                    'can'  => 'menu-estadisticas-actividades-fomento',
                    'submenu' => [
                        [
                            'text'    => 'Panel Fomento',
                            'icon'    => 'fa-solid fa-chart-line',
                            'classes' => 'text-white',
                            'route'   => 'settings.estadisticas_fomento.index',
                            'can'     => 'menu-estadisticas-actividades-fomento',
                        ],
                        [
                            'text'    => 'Actividades',
                            'icon'    => 'fa-solid fa-clipboard-list',
                            'classes' => 'text-info',
                            'url'     => 'estadisticas-actividades',
                            'can'     => 'menu-estadisticas-actividades-fomento',
                        ],
                        [
                            'text'    => 'Aseguramientos',
                            'icon'    => 'fa-solid fa-boxes-stacked',
                            'classes' => 'text-info',
                            'url'     => 'estadisticas-aseguramientos?unidad_slug=fomento-cultura-vial',
                            'can'     => 'menu-estadisticas-actividades-fomento',
                        ],
                        [
                            'text'    => 'Servicios por personal',
                            'icon'    => 'fa-solid fa-ranking-star',
                            'classes' => 'text-info',
                            'route'   => 'settings.estadisticas_fomento.servicios_personal',
                            'can'     => 'menu-estadisticas-actividades-fomento',
                        ],
                    ],
                ],

                [
                    'text' => 'Vialidades Urbanas',
                    'icon' => 'fa-solid fa-traffic-light',
                    'can'  => 'menu-estadisticas-actividades-vialidades',
                    'submenu' => [
                        [
                            'text'    => 'Actividades',
                            'icon'    => 'fa-solid fa-clipboard-list',
                            'classes' => 'text-info',
                            'url'     => 'estadisticas-actividades',
                            'can'     => 'menu-estadisticas-actividades-vialidades',
                        ],
                        [
                            'text'    => 'Aseguramientos',
                            'icon'    => 'fa-solid fa-boxes-stacked',
                            'classes' => 'text-info',
                            'url'     => 'estadisticas-aseguramientos?unidad_slug=vialidades-urbanas',
                            'can'     => 'menu-estadisticas-actividades-vialidades',
                        ],
                    ],
                ],

                [
                    'text' => 'Delegaciones',
                    'icon' => 'fa-solid fa-building-shield',
                    'submenu' => [
                        [
                            'text'    => 'Panel Delegaciones',
                            'icon'    => 'fa-solid fa-chart-line',
                            'classes' => 'text-white',
                            'url'     => 'estadisticas-globales',
                            'can'     => 'menu-estadisticas-globales',
                        ],
                        [
                            'text'    => 'Actividades',
                            'icon'    => 'fa-solid fa-clipboard-list',
                            'classes' => 'text-info',
                            'url'     => 'estadisticas-actividades',
                            'can'     => 'menu-estadisticas-actividades-delegaciones',
                        ],
                        [
                            'text'    => 'Actividades Físicas',
                            'icon'    => 'fa-solid fa-person-running',
                            'classes' => 'text-info',
                            'route'   => 'settings.estadisticas_delegaciones.actividades_fisicas',
                            'can'     => 'menu-estadisticas-delegaciones',
                        ],
                        [
                            'text'    => 'Aseguramientos',
                            'icon'    => 'fa-solid fa-boxes-stacked',
                            'classes' => 'text-info',
                            'url'     => 'estadisticas-aseguramientos?unidad_slug=delegaciones',
                            'can'     => 'menu-estadisticas-delegaciones',
                        ],
                    ],
                ],

                [
                    'text' => 'Carreteras',
                    'icon' => 'fa-solid fa-road',
                    'submenu' => [
                        [
                            'text'    => 'Panel Carreteras',
                            'icon'    => 'fa-solid fa-chart-line',
                            'classes' => 'text-white',
                            'url'     => 'estadisticas-carreteras',
                            'can'     => 'menu-estadisticas-carreteras',
                        ],
                        [
                            'text'    => 'Aseguramientos',
                            'icon'    => 'fa-solid fa-boxes-stacked',
                            'classes' => 'text-info',
                            'url'     => 'estadisticas-aseguramientos?unidad_slug=carreteras',
                            'can'     => 'menu-estadisticas-carreteras',
                        ],
                    ],
                ],

            ],
        ],

        [
            'text'    => 'Guardianes del Camino',
            'icon'    => 'fa-solid fa-road',
            'classes' => 'bg-blue text-white',
            'can'     => 'menu-guardianes-camino',
            'submenu' => [
                [
                    'text'    => 'Listado de Dispositivos',
                    'icon'    => 'fa-solid fa-list-check',
                    'classes' => 'text-white',
                    'route'   => 'guardianes_camino.index',
                    'can'     => 'menu-guardianes-camino',
                ],
                [
                    'text'    => 'Pendientes de Revisión',
                    'icon'    => 'fa-solid fa-clipboard-check',
                    'classes' => 'text-white',
                    'route'   => 'guardianes_camino.dispositivos.pendientes_revision',
                    'can'     => 'menu-guardianes-pendientes-revision',
                ],
            ],
        ],

        [
            'text'    => 'Mapa',
            'icon'    => 'fa-solid fa-map-location-dot',
            'classes' => 'bg-blue text-white',
            'can'     => 'ver mapa',
            'submenu' => [
                [
                    'text'    => 'Mapa Patrullas',
                    'icon'    => 'fa-solid fa-map-location-dot',
                    'classes' => 'text-white',
                    'url'     => 'mapa',
                    'can'     => 'ver mapa',
                ],
                [
                    'text'    => 'Mapa Incidencias',
                    'icon'    => 'fa-solid fa-fire',
                    'classes' => 'text-white',
                    'url'     => 'mapa-incidencias',
                    'can'     => 'ver mapa',
                ],
                [
                    'text'    => 'Mapa Colonias',
                    'icon'    => 'fa-solid fa-draw-polygon',
                    'classes' => 'text-white',
                    'url'     => 'mapa-colonias',
                    'can'     => 'ver mapa',
                ],
                [
                    'text'    => 'Mapa Predictivo',
                    'icon'    => 'fa-solid fa-triangle-exclamation',
                    'classes' => 'text-white',
                    'url'     => 'waze/riesgo',
                    'can'     => 'ver mapa',
                ],
            ],
        ],

        [
            'text'    => 'Puntos de Licencia',
            'icon'    => 'fa-solid fa-id-card-clip',
            'classes' => 'bg-blue text-white',
            'can'     => 'ver puntos licencias',
            'submenu' => [
                [
                    'text'    => 'Panel de puntos',
                    'icon'    => 'fa-solid fa-gauge-high',
                    'classes' => 'text-white',
                    'route'   => 'licencias_puntos.index',
                    'can'     => 'ver puntos licencias',
                ],
                [
                    'text'    => 'Cursos de recuperacion',
                    'icon'    => 'fa-solid fa-chalkboard-user',
                    'classes' => 'text-white',
                    'route'   => 'licencias_puntos.cursos.index',
                    'can'     => 'ver cursos puntos licencias',
                ],
                [
                    'text'    => 'Penalizaciones',
                    'icon'    => 'fa-solid fa-list-check',
                    'classes' => 'text-white',
                    'route'   => 'settings.licencias_puntos.infracciones.index',
                    'can'     => 'ver catalogo infracciones puntos licencias',
                ],
                [
                    'text'    => 'Consulta ciudadana',
                    'icon'    => 'fa-solid fa-magnifying-glass',
                    'classes' => 'text-white',
                    'route'   => 'licencias_puntos.consulta',
                ],
            ],
        ],
        [
            'text'    => 'Mis puntos de licencia',
            'icon'    => 'fa-solid fa-id-card-clip',
            'classes' => 'bg-blue text-white',
            'route'   => 'ciudadano.licencias_puntos.index',
            'can'     => 'ver portal ciudadano puntos licencias',
        ],

        [
            'text'    => 'Módulos de Exámenes',
            'icon'    => 'fa-solid fa-id-card',
            'classes' => 'bg-blue text-white',
            'can'     => 'menu-modulo-examenes',
            'submenu' => [
                [
                    'text'    => 'Exámenes Diarios',
                    'icon'    => 'fa-solid fa-clipboard-list',
                    'classes' => 'text-white',
                    'url'     => 'modulo-examenes-diarios',
                    'can'     => 'menu-modulo-examenes',
                ],
                [
                    'text'    => 'Capturar Exámenes',
                    'icon'    => 'fa-solid fa-plus',
                    'classes' => 'text-white',
                    'url'     => 'modulo-examenes-diarios/create',
                    'can'     => 'menu-modulo-examenes-crear',
                ],

                [
                    'text'    => 'Imprimir Constancias',
                    'icon'    => 'fa-solid fa-print',
                    'classes' => 'text-white',
                    'url'     => 'constancias-manejo',
                    'can'     => 'menu-modulo-examenes',
                ],
                [
                    'text'    => 'Exámenes en Línea',
                    'icon'    => 'fa-solid fa-qrcode',
                    'classes' => 'text-white',
                    'url'     => 'constancias-manejo/examenes',
                    'can'     => 'menu-modulo-examenes',
                ],
                [
                    'text'    => 'Nuevo Examen',
                    'icon'    => 'fa-solid fa-plus',
                    'classes' => 'text-white',
                    'url'     => 'constancias-manejo/examenes/create',
                    'can'     => 'menu-modulo-examenes-crear',
                ],
                [
                    'text'    => 'Aprobados por Activar',
                    'icon'    => 'fa-solid fa-clock',
                    'classes' => 'text-warning',
                    'url'     => 'constancias-manejo/examenes?estatus=APROBADO&sin_constancia=1',
                    'can'     => 'menu-modulo-examenes',
                ],
            ],
        ],

        [
            'text'    => 'Configuraciones',
            'icon'    => 'fas fa-fw fa-gear',
            'classes' => 'bg-blue text-white',
            'can'     => 'ver configuraciones',
            'submenu' => [
                [
                    'text'    => 'Listado de Configuraciones',
                    'icon'    => 'fas fa-fw fa-gear',
                    'classes' => 'text-white',
                    'url'     => 'admin/settings',
                    'can'     => 'ver configuraciones',
                ],
                [
                    'text'    => 'Reconstructor de Tránsito 2D',
                    'icon'    => 'fa-solid fa-route',
                    'classes' => 'text-white',
                    'route'   => 'settings.reconstructor_transito.index',
                    'can'     => 'ver configuraciones',
                ],
                [
                    'text'    => 'Listado de Usuarios',
                    'icon'    => 'fa-solid fa-user',
                    'classes' => 'text-white',
                    'url'     => 'admin/settings/users',
                    'can'     => 'ver usuarios',
                ],
                [
                    'text'    => 'Listado de Roles',
                    'icon'    => 'fa-regular fa-flag',
                    'classes' => 'text-white',
                    'url'     => 'admin/settings/roles',
                    'can'     => 'ver roles',
                ],
                [
                    'text'    => 'Banco de Preguntas',
                    'icon'    => 'fa-solid fa-circle-question',
                    'classes' => 'text-white',
                    'url'     => 'admin/settings/constancias/preguntas',
                    'can'     => 'menu-modulo-examenes',
                ],
                [
                    'text'    => 'Oficios',
                    'icon'    => 'fas fa-envelope-open-text',
                    'classes' => 'text-white',
                    'url'     => 'admin/settings/oficios',
                    'can'     => 'ver oficios',
                ],
                [
                    'text'    => 'Catalogo de sanciones',
                    'icon'    => 'fa-solid fa-id-card-clip',
                    'classes' => 'text-white',
                    'route'   => 'settings.licencias_puntos.infracciones.index',
                    'can'     => 'ver catalogo infracciones puntos licencias',
                ],
                [
                    'text'    => 'Red de apoyo',
                    'icon'    => 'fa-solid fa-handshake-angle',
                    'classes' => 'text-white',
                    'url'     => 'admin/settings/directorio-red-apoyo',
                    'can'     => 'ver directorio red apoyo',
                ],
            ],
        ],
    ],

    'filters' => [
        JeroenNoten\LaravelAdminLte\Menu\Filters\GateFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\HrefFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\SearchFilter::class,
        App\AdminLte\Filters\WazeBellBadgeFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ActiveFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ClassesFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\LangFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\DataFilter::class,
    ],

    'plugins' => [

        'SVTheme' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'css',
                    'asset' => true,
                    'location' => 'css/sv-theme.css',
                ],
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => 'js/sv-theme.js',
                ],
            ],
        ],

        'FontAwesome' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css',
                ],
            ],
        ],

        'Sweetalert2' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js',
                ],
            ],
        ],

        'Datatables' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => 'https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => 'https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap4.min.css',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => 'https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css',
                ],

                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap4.min.js',
                ],

                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap4.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js',
                ],

                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js',
                ],
            ],
        ],

        'Select2' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
                ],
            ],
        ],

        'Chartjs' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
                ],
            ],
        ],

        'Pace' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => 'https://cdnjs.cloudflare.com/ajax/libs/pace/1.2.4/themes/blue/pace-theme-center-radar.min.css',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'https://cdnjs.cloudflare.com/ajax/libs/pace/1.2.4/pace.min.js',
                ],
            ],
        ],
    ],

    'iframe' => [
        'default_tab' => [
            'url' => null,
            'title' => null,
        ],
        'buttons' => [
            'close' => true,
            'close_all' => true,
            'close_all_other' => true,
            'scroll_left' => true,
            'scroll_right' => true,
            'fullscreen' => true,
        ],
        'options' => [
            'loading_screen' => 1000,
            'auto_show_new_tab' => true,
            'use_navbar_items' => true,
        ],
    ],

    'livewire' => false,
];
