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
                    'text'    => 'Cortes de Pendientes',
                    'icon'    => 'fa-solid fa-clipboard-list',
                    'classes' => 'text-white',
                    'url'     => 'hechos/pendientes/cortes',
                    'can'     => 'ver hechos',
                ],
            ],
        ],

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

        [
            'text'    => 'Dictamenes',
            'icon'    => 'fas fa-gavel',
            'classes' => 'bg-blue text-white',
            'can'     => 'ver dictamenes',
            'submenu' => [
                [
                    'text'    => 'Listado de Dictamenes',
                    'icon'    => 'fas fa-gavel',
                    'classes' => 'text-white',
                    'url'     => 'dictamenes',
                    'can'     => 'ver dictamenes',
                ],
                [
                    'text'    => 'Solicitar número Dictamen',
                    'icon'    => 'fa-solid fa-plus',
                    'classes' => 'text-white',
                    'url'     => 'dictamenes/create',
                    'can'     => 'crear dictamenes',
                ],
            ],
        ],

        [
            'text'    => 'Actividades',
            'icon'    => 'fas fa-tasks',
            'classes' => 'bg-blue text-white',
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
            'text'    => 'Oficios',
            'icon'    => 'fas fa-envelope-open-text',
            'classes' => 'bg-blue text-white',
            'can'     => 'ver oficios',
            'submenu' => [
                [
                    'text'    => 'Listado de Oficios',
                    'icon'    => 'fas fa-envelope-open-text',
                    'classes' => 'text-white',
                    'url'     => 'oficios',
                    'can'     => 'ver oficios',
                ],
                [
                    'text'    => 'Subir Oficio',
                    'icon'    => 'fa-solid fa-plus',
                    'classes' => 'text-white',
                    'url'     => 'oficios/create',
                    'can'     => 'crear oficios',
                ],
            ],
        ],

        [
            'text'    => 'Estadísticas Globales',
            'icon'    => 'fa-solid fa-chart-column',
            'classes' => 'bg-blue text-white',
            'can'     => 'ver estadisticas globales',
            'submenu' => [
                [
                    'text'    => 'Panel Global',
                    'icon'    => 'fa-solid fa-chart-line',
                    'classes' => 'text-white',
                    'url'     => 'estadisticas-globales',
                    'can'     => 'ver estadisticas globales',
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
                    'text'    => 'Mapa Predictivo',
                    'icon'    => 'fa-solid fa-triangle-exclamation',
                    'classes' => 'text-white',
                    'url'     => 'waze/riesgo',
                    'can'     => 'ver mapa',
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
