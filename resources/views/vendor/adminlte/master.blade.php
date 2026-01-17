<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <link rel="icon" href="{{ asset('icon.ico') }}" type="image/x-icon">

    {{-- Base Meta Tags --}}
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Custom Meta Tags --}}
    @yield('meta_tags')

    {{-- Title --}}
    <title>
        @yield('title_prefix', config('adminlte.title_prefix', ''))
        @yield('title', config('adminlte.title', 'AdminLTE 3'))
        @yield('title_postfix', config('adminlte.title_postfix', ''))
    </title>

    {{-- Base Stylesheets --}}
    @if(config('adminlte.enabled_laravel_mix', false))
        <link rel="stylesheet" href="{{ mix(config('adminlte.laravel_mix_css_path', 'css/app.css')) }}">
    @else
        <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/overlayScrollbars/css/OverlayScrollbars.min.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">
    @endif

    {{-- DataTables CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @if(config('adminlte.google_fonts.allowed', true))
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
    @endif

    {{-- Extra Plugins CSS --}}
    @include('adminlte::plugins', ['type' => 'css'])

    {{-- Livewire Styles --}}
    @if(config('adminlte.livewire'))
        @if(intval(app()->version()) >= 7)
            @livewireStyles
        @else
            <livewire:styles />
        @endif
    @endif

    {{-- Custom Stylesheets --}}
    @yield('adminlte_css')

    {{-- ====== GLOBAL THEME (WELCOME-STYLE) ====== --}}
    <style>
        :root{
            --bg0:#070B14;
            --bg1:#0B1224;
            --text:#EAF0FF;
            --muted: rgba(234,240,255,.75);
            --muted2: rgba(234,240,255,.55);
            --stroke: rgba(255,255,255,.14);
            --card: rgba(255,255,255,.08);
            --card2: rgba(255,255,255,.06);
            --brand:#2DA8FF;
            --brand2:#7C5CFF;
            --ok:#19D38C;
            --warn:#FFCC66;
            --shadow: 0 18px 55px rgba(0,0,0,.45);
            --shadow2: 0 12px 35px rgba(0,0,0,.35);
            --radius: 18px;
            --radius2: 22px;
        }

        html, body{ height: 100%; }
        body{
            font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif !important;
            color: var(--text);
            background:
                radial-gradient(1200px 900px at 20% 10%, rgba(45,168,255,.25), transparent 60%),
                radial-gradient(1000px 800px at 80% 20%, rgba(124,92,255,.22), transparent 55%),
                radial-gradient(900px 650px at 60% 85%, rgba(25,211,140,.12), transparent 60%),
                linear-gradient(180deg, var(--bg0), var(--bg1) 60%, #050813) !important;
        }

        /* Content wrapper background */
        .content-wrapper, .main-footer{
            background: transparent !important;
        }

        /* Navbar glass */
        .main-header.navbar{
            background: linear-gradient(180deg, rgba(7,11,20,.88), rgba(7,11,20,.55)) !important;
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(255,255,255,.10) !important;
        }
        .main-header .nav-link{
            color: rgba(234,240,255,.82) !important;
            font-weight: 700;
        }
        .main-header .nav-link:hover{
            color: rgba(234,240,255,.95) !important;
        }

        /* Sidebar glass */
        .main-sidebar{
            background: rgba(0,0,0,.26) !important;
            backdrop-filter: blur(14px);
            border-right: 1px solid rgba(255,255,255,.10) !important;
        }
        .brand-link{
            background: transparent !important;
            border-bottom: 1px solid rgba(255,255,255,.10) !important;
        }
        .brand-link .brand-text{
            color: rgba(234,240,255,.92) !important;
            font-weight: 900 !important;
            letter-spacing: .2px;
        }

        /* Menu items */
        .nav-sidebar .nav-link{
            border-radius: 14px !important;
            margin: 6px 10px !important;
            color: rgba(234,240,255,.78) !important;
            transition: .18s ease;
        }
        .nav-sidebar .nav-link:hover{
            background: rgba(255,255,255,.06) !important;
            color: rgba(234,240,255,.92) !important;
            transform: translateY(-1px);
        }
        .nav-sidebar .nav-link.active{
            background: linear-gradient(135deg, rgba(45,168,255,.18), rgba(124,92,255,.14)) !important;
            border: 1px solid rgba(255,255,255,.12) !important;
            color: rgba(234,240,255,.95) !important;
        }
        .nav-sidebar .nav-treeview{
            padding-left: 6px !important;
        }
        .nav-sidebar .nav-treeview .nav-link{
            margin: 6px 10px 6px 18px !important;
            border-radius: 12px !important;
            background: rgba(0,0,0,.10) !important;
            border: 1px solid rgba(255,255,255,.08) !important;
        }

        /* Cards glass */
        .card{
            background: linear-gradient(180deg, rgba(255,255,255,.10), rgba(255,255,255,.05)) !important;
            border: 1px solid rgba(255,255,255,.12) !important;
            border-radius: var(--radius2) !important;
            box-shadow: var(--shadow2) !important;
        }
        .card-header{
            background: rgba(0,0,0,.14) !important;
            border-bottom: 1px solid rgba(255,255,255,.10) !important;
        }
        .card-title, .card-header .btn-tool{
            color: rgba(234,240,255,.92) !important;
        }

        /* Buttons */
        .btn{
            border-radius: 14px !important;
            font-weight: 800 !important;
        }
        .btn-primary{
            border: 1px solid rgba(45,168,255,.35) !important;
            background: linear-gradient(135deg, rgba(45,168,255,.25), rgba(124,92,255,.22)) !important;
            box-shadow: 0 18px 55px rgba(0,0,0,.30);
        }
        .btn-primary:hover{
            transform: translateY(-1px);
            border-color: rgba(45,168,255,.55) !important;
            background: linear-gradient(135deg, rgba(45,168,255,.34), rgba(124,92,255,.30)) !important;
        }

        /* Inputs */
        .form-control, .custom-select{
            background: rgba(0,0,0,.18) !important;
            border: 1px solid rgba(255,255,255,.12) !important;
            color: rgba(234,240,255,.92) !important;
            border-radius: 14px !important;
        }
        .form-control::placeholder{ color: rgba(234,240,255,.55) !important; }
        .form-control:focus, .custom-select:focus{
            box-shadow: none !important;
            border-color: rgba(45,168,255,.45) !important;
            background: rgba(0,0,0,.22) !important;
        }
        label{ color: rgba(234,240,255,.78) !important; font-weight: 700; }

        /* Tables */
        .table, table.dataTable{
            color: rgba(234,240,255,.90) !important;
        }
        .table thead th, table.dataTable thead th{
            background: rgba(0,0,0,.18) !important;
            border-bottom: 1px solid rgba(255,255,255,.12) !important;
            color: rgba(234,240,255,.92) !important;
        }
        .table td, .table th, table.dataTable td, table.dataTable th{
            border-top: 1px solid rgba(255,255,255,.08) !important;
        }
        .table-striped tbody tr:nth-of-type(odd){
            background: rgba(255,255,255,.03) !important;
        }

        /* DataTables controls */
        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select{
            background: rgba(0,0,0,.18) !important;
            border: 1px solid rgba(255,255,255,.12) !important;
            color: rgba(234,240,255,.92) !important;
            border-radius: 14px !important;
            outline: none !important;
        }
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate{
            color: rgba(234,240,255,.70) !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button{
            color: rgba(234,240,255,.85) !important;
            border-radius: 12px !important;
            border: 1px solid rgba(255,255,255,.10) !important;
            background: rgba(0,0,0,.12) !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current{
            background: linear-gradient(135deg, rgba(45,168,255,.22), rgba(124,92,255,.18)) !important;
            border-color: rgba(255,255,255,.14) !important;
        }

        /* Alerts */
        .alert{
            border-radius: var(--radius) !important;
            border: 1px solid rgba(255,255,255,.14) !important;
            background: rgba(0,0,0,.18) !important;
            color: rgba(234,240,255,.92) !important;
        }

        /* Modals */
        .modal-content{
            border-radius: var(--radius2) !important;
            background: linear-gradient(180deg, rgba(255,255,255,.10), rgba(255,255,255,.05)) !important;
            border: 1px solid rgba(255,255,255,.12) !important;
            box-shadow: var(--shadow) !important;
        }
        .modal-header{
            border-bottom: 1px solid rgba(255,255,255,.10) !important;
        }
        .modal-footer{
            border-top: 1px solid rgba(255,255,255,.10) !important;
        }

        /* Breadcrumb */
        .content-header .breadcrumb{
            background: rgba(0,0,0,.14) !important;
            border: 1px solid rgba(255,255,255,.10) !important;
            border-radius: 14px !important;
        }
        .breadcrumb-item a{ color: rgba(234,240,255,.78) !important; font-weight: 700; }
        .breadcrumb-item.active{ color: rgba(234,240,255,.92) !important; }

        /* Small polish */
        .content-header h1{
            color: rgba(234,240,255,.92) !important;
            font-weight: 900;
            letter-spacing: -.3px;
        }
    </style>

    {{-- Extra per-page theme overrides (optional) --}}
    @stack('styles')
</head>

<body class="@yield('classes_body')" @yield('body_data')>
    {{-- Body Content --}}
    @yield('body')

    {{-- Base Scripts --}}
    @if(config('adminlte.enabled_laravel_mix', false))
        <script src="{{ mix(config('adminlte.laravel_mix_js_path', 'js/app.js')) }}"></script>
    @else
        <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
        <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
        <script src="{{ asset('vendor/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>
        <script src="{{ asset('vendor/adminlte/dist/js/adminlte.min.js') }}"></script>
    @endif

    {{-- DataTables JS --}}
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

    {{-- Extra Plugins JS --}}
    @include('adminlte::plugins', ['type' => 'js'])

    {{-- Livewire Scripts --}}
    @if(config('adminlte.livewire'))
        @if(intval(app()->version()) >= 7)
            @livewireScripts
        @else
            <livewire:scripts />
        @endif
    @endif

    {{-- Custom Scripts --}}
    @yield('adminlte_js')

    {{-- Extra per-page scripts (optional) --}}
    @stack('scripts')
</body>
</html>
