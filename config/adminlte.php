<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Title
    |--------------------------------------------------------------------------
    */
    'title' => 'Sistema Estadistico',
    'title_prefix' => '',
    'title_postfix' => '',

    /*
    |--------------------------------------------------------------------------
    | Favicon
    |--------------------------------------------------------------------------
    */
    'use_ico_only' => true,
    'use_full_favicon' => false,

    /*
    |--------------------------------------------------------------------------
    | Google Fonts
    |--------------------------------------------------------------------------
    */
    'google_fonts' => [
        'allowed' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Logo
    |--------------------------------------------------------------------------
    */
    'logo' => '<b style="color:white;">Seguridad Vial</b>',
    'logo_img' => 'guardiacivil.png',
    'logo_img_class' => 'brand-image img-circle elevation-3',
    'logo_img_xl' => null,
    'logo_img_xl_class' => 'brand-image-xs',
    'logo_img_alt' => 'Admin Logo',

    /*
    |--------------------------------------------------------------------------
    | Authentication Logo
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | Preloader Animation
    |--------------------------------------------------------------------------
    */
    'preloader' => [
        'enabled' => true,
        'mode' => 'fullscreen',
        'img' => [
            'path' => 'guardiacivil.png',
            'alt' => 'Sistema Estadistico',
            'effect' => 'animation__shake',
            'class' => 'custom-preloader-img',
            'width' => 300,
            'height' => 300,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Menu
    |--------------------------------------------------------------------------
    */
    'usermenu_enabled' => true,
    'usermenu_header' => false,
    'usermenu_header_class' => 'bg-primary',
    'usermenu_image' => false,
    'usermenu_desc' => false,
    'usermenu_profile_url' => false,

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    */
    'layout_topnav' => null,
    'layout_boxed' => null,
    'layout_fixed_sidebar' => null,
    'layout_fixed_navbar' => null,
    'layout_fixed_footer' => null,
    'layout_dark_mode' => false,

    /*
    |--------------------------------------------------------------------------
    | Authentication Views Classes
    |--------------------------------------------------------------------------
    */
    'classes_auth_card' => 'card-outline card-primary',
    'classes_auth_header' => '',
    'classes_auth_body' => '',
    'classes_auth_footer' => '',
    'classes_auth_icon' => '',
    'classes_auth_btn' => 'btn-flat btn-primary',

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Classes
    |--------------------------------------------------------------------------
    */
    'classes_body' => '',
    'classes_brand' => '',
    'classes_brand_text' => '',
    'classes_content_wrapper' => '',
    'classes_content_header' => '',
    'classes_content' => '',
    'classes_sidebar' => 'sidebar-dark-primary elevation-4',
    'classes_sidebar_nav' => '',
    'classes_topnav' => 'navbar-white navbar-light',
    'classes_topnav_nav' => 'navbar-expand',
    'classes_topnav_container' => 'container',

    /*
    |--------------------------------------------------------------------------
    | Sidebar
    |--------------------------------------------------------------------------
    */
    'sidebar_mini' => 'lg',
    'sidebar_collapse' => false,
    'sidebar_collapse_auto_size' => false,
    'sidebar_collapse_remember' => false,
    'sidebar_collapse_remember_no_transition' => true,
    'sidebar_scrollbar_theme' => 'os-theme-light',
    'sidebar_scrollbar_auto_hide' => 'l',
    'sidebar_nav_accordion' => true,
    'sidebar_nav_animation_speed' => 300,

    /*
    |--------------------------------------------------------------------------
    | Control Sidebar (Right Sidebar)
    |--------------------------------------------------------------------------
    */
    'right_sidebar' => false,
    'right_sidebar_icon' => 'fas fa-cogs',
    'right_sidebar_theme' => 'dark',
    'right_sidebar_slide' => true,
    'right_sidebar_push' => true,
    'right_sidebar_scrollbar_theme' => 'os-theme-light',
    'right_sidebar_scrollbar_auto_hide' => 'l',

    /*
    |--------------------------------------------------------------------------
    | URLs
    |--------------------------------------------------------------------------
    */
    'use_route_url' => false,
    'dashboard_url' => 'home',
    'logout_url' => 'logout',
    'login_url' => 'login',
    'register_url' => 'register',
    'password_reset_url' => 'password/reset',
    'password_email_url' => 'password/email',
    'profile_url' => false,
    'disable_darkmode_routes' => false,

    /*
    |--------------------------------------------------------------------------
    | Laravel Asset Bundling
    |--------------------------------------------------------------------------
    */
    'laravel_asset_bundling' => false,
    'laravel_css_path' => 'css/app.css',
    'laravel_js_path' => 'js/app.js',

    /*
    |--------------------------------------------------------------------------
    | Menu Items
    |--------------------------------------------------------------------------
    */
    'menu' => [
        // Navbar items:
        [
            'type' => 'navbar-search',
            'text' => 'search',
            'topnav_right' => true,
        ],
        [
            'type' => 'link',
            'text' => 'Scan',
            'url' => '#',
            'topnav_right' => true,
            'icon' => 'fa-solid fa-qrcode',
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

        // ===================== SIDEBAR =====================

        [
            'text'    => 'Accidentes',
            'icon'    => 'fa-solid fa-car-side',
            'classes' => 'bg-blue text-white',
            'can'     => 'ver hechos',
            'submenu' => [
                [
                    'text'    => 'Listado de Accidentes',
                    'icon'    => 'fa-solid fa-car-side',
                    'classes' => 'text-white',
                    'url'     => 'hechos',
                    'can'     => 'ver hechos',
                ],
                [
                    'text'    => 'Añadir un accidente',
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
                    'url'     => '#',
                ],
                [
                    'text'    => 'Añadir Actividad',
                    'icon'    => 'fa-solid fa-plus',
                    'classes' => 'text-white',
                    'url'     => '#',
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
                    'text'    => 'Ver Gráfico de Servicios',
                    'icon'    => 'fa-solid fa-chart-line',
                    'classes' => 'text-white',
                    'url'     => 'servicios/grafico',
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
            'text'    => 'Estadísticas',
            'icon'    => 'fa-solid fa-chart-pie',
            'classes' => 'bg-blue text-white',
            'can'     => 'ver estadisticas',
            'submenu' => [
                [
                    'text'    => 'Listado de Estadisticas',
                    'icon'    => 'fa-solid fa-chart-pie',
                    'classes' => 'text-white',
                    'url'     => 'admin/settings/estadisticas',
                    'can'     => 'ver estadisticas',
                ],
                [
                    'text'    => 'Parte de Novedades',
                    'icon'    => 'fa-solid fa-file-word',
                    'classes' => 'text-white',
                    'route'   => 'estadisticas.parteNovedades',
                    'can'     => 'ver estadisticas',
                ],
                [
                    'text'    => 'Mini Parte',
                    'icon'    => 'fa-solid fa-file-word',
                    'classes' => 'text-white',
                    'route'   => 'estadisticas.miniParte',
                    'can'     => 'ver estadisticas',
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

    /*
    |--------------------------------------------------------------------------
    | Menu Filters
    |--------------------------------------------------------------------------
    */
    'filters' => [
        JeroenNoten\LaravelAdminLte\Menu\Filters\GateFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\HrefFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\SearchFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ActiveFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ClassesFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\LangFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\DataFilter::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugins Initialization
    |--------------------------------------------------------------------------
    */
    'plugins' => [
        'FontAwesome' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css',
                ],
            ],
        ],

        'Datatables' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/1.10.19/js/dataTables.bootstrap4.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/1.10.19/css/dataTables.bootstrap4.min.css',
                ],
            ],
        ],

        'Select2' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.css',
                ],
            ],
        ],

        'Chartjs' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.0/Chart.bundle.min.js',
                ],
            ],
        ],

        'Sweetalert2' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
                ],
            ],
        ],

        'Pace' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/themes/blue/pace-theme-center-radar.min.css',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/pace.min.js',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | IFrame
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | Livewire
    |--------------------------------------------------------------------------
    */
    'livewire' => false,
];
