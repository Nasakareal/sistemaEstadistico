<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <link rel="icon" href="{{ asset('icon.ico') }}" type="image/x-icon">

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @yield('meta_tags')

    <title>
        @yield('title_prefix', config('adminlte.title_prefix', ''))
        @yield('title', config('adminlte.title', 'Seguridad Vial'))
        @yield('title_postfix', config('adminlte.title_postfix', ''))
    </title>

    @if(config('adminlte.enabled_laravel_mix', false))
        <link rel="stylesheet" href="{{ mix(config('adminlte.laravel_mix_css_path', 'css/app.css')) }}">
    @else
        <link rel="stylesheet" href="{{ asset('vendor/overlayScrollbars/css/OverlayScrollbars.min.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @include('adminlte::plugins', ['type' => 'css'])

    @if(config('adminlte.livewire'))
        @if(intval(app()->version()) >= 7)
            @livewireStyles
        @else
            <livewire:styles />
        @endif
    @endif

    @yield('adminlte_css')

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

        .content-wrapper, .main-footer{
            background: transparent !important;
        }

        .main-header.navbar{
            background: linear-gradient(180deg, rgba(7,11,20,.88), rgba(7,11,20,.55)) !important;
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(255,255,255,.10) !important;
        }
        .main-header .nav-link{
            color: rgba(234,240,255,.82) !important;
            font-weight: 800;
        }
        .main-header .nav-link:hover{
            color: rgba(234,240,255,.95) !important;
        }

        .main-sidebar{
            background: rgba(0,0,0,.26) !important;
            backdrop-filter: blur(14px);
            border-right: 1px solid rgba(255,255,255,.10) !important;
            z-index: 1040 !important;
        }

        .sidebar-overlay{
            background: rgba(0,0,0,.55) !important;
            z-index: 1039 !important;
        }

        .main-header{
            z-index: 1038 !important;
        }

        @media (max-width: 991.98px){
            .main-sidebar{
                background: #0B1224 !important;
                backdrop-filter: none !important;
            }
            body.sidebar-open .content-wrapper{
                filter: blur(2px);
            }
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

        .nav-sidebar{
    padding-top: 6px !important;
}

.nav-sidebar .nav-item{
    margin: 6px 10px !important;
}

.nav-sidebar .nav-link{
    display: flex !important;
    align-items: center !important;
    gap: 10px !important;
    padding: 10px 12px !important;
    border-radius: 14px !important;
    margin: 0 !important;
    background: rgba(0,0,0,.12) !important;
    border: 1px solid rgba(255,255,255,.10) !important;
    color: rgba(234,240,255,.92) !important;
    transition: .18s ease !important;
}

.nav-sidebar .nav-link:hover{
    transform: translateY(-1px);
    background: rgba(255,255,255,.06) !important;
    border-color: rgba(255,255,255,.14) !important;
    color: rgba(234,240,255,.95) !important;
}

.nav-sidebar .nav-link.active{
    background: linear-gradient(135deg, rgba(45,168,255,.35), rgba(124,92,255,.24)) !important;
    border-color: rgba(45,168,255,.35) !important;
    color: rgba(234,240,255,.98) !important;
}

.nav-sidebar .nav-link > i{
    width: 28px !important;
    height: 28px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    border-radius: 10px !important;
    background: rgba(255,255,255,.06) !important;
    border: 1px solid rgba(255,255,255,.10) !important;
    margin: 0 !important;
    font-size: 15px !important;
    flex: 0 0 auto !important;
}

.nav-sidebar .nav-link p{
    margin: 0 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    width: 100% !important;
    font-weight: 900 !important;
    letter-spacing: .15px !important;
    white-space: nowrap !important;
    font-size: 14px !important;
}

.nav-sidebar .nav-link p i.right{
    display: none !important;
}

.nav-sidebar .nav-link p::after{
    content: "›";
    font-size: 20px;
    line-height: 1;
    opacity: .85;
    margin-left: 10px;
    transform: translateY(-1px);
}

.nav-sidebar .menu-open > .nav-link p::after{
    content: "⌄";
    font-size: 16px;
    transform: translateY(-1px);
}

.nav-sidebar .nav-treeview{
    padding-left: 0 !important;
    margin: 6px 0 0 0 !important;
    border-top: 1px solid rgba(255,255,255,.08) !important;
}

.nav-sidebar .nav-treeview .nav-item{
    margin: 6px 0 0 0 !important;
}

.nav-sidebar .nav-treeview .nav-link{
    margin: 0 0 0 10px !important;
    padding: 9px 12px !important;
    border-radius: 13px !important;
    background: rgba(0,0,0,.10) !important;
}

.nav-sidebar .nav-treeview .nav-link > i{
    width: 26px !important;
    height: 26px !important;
    font-size: 14px !important;
}

.badge.right{
    position: static !important;
    margin-left: 8px !important;
    padding: 3px 8px !important;
    border-radius: 999px !important;
    font-weight: 900 !important;
    font-size: 12px !important;
    border: 1px solid rgba(255,255,255,.14) !important;
    background: rgba(0,0,0,.18) !important;
}

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

        .btn{
            border-radius: 14px !important;
            font-weight: 900 !important;
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
        label{ color: rgba(234,240,255,.78) !important; font-weight: 800; }

        input[type="date"].form-control::-webkit-calendar-picker-indicator{
            filter: invert(1);
            opacity: .9;
            cursor: pointer;
        }

        .table{
            color: rgba(234,240,255,.90) !important;
        }
        .table thead th{
            background: rgba(0,0,0,.18) !important;
            border-bottom: 1px solid rgba(255,255,255,.12) !important;
            color: rgba(234,240,255,.92) !important;
        }
        .table td, .table th{
            border-top: 1px solid rgba(255,255,255,.08) !important;
        }
        .table-striped tbody tr:nth-of-type(odd){
            background: rgba(255,255,255,.03) !important;
        }
        .table-hover tbody tr:hover{
            background: rgba(255,255,255,.06) !important;
        }
        .table-hover tbody tr:hover td,
        .table-hover tbody tr:hover th,
        .table-hover tbody tr:hover a{
            color: rgba(234,240,255,.95) !important;
        }

        .alert{
            border-radius: var(--radius) !important;
            border: 1px solid rgba(255,255,255,.14) !important;
            background: rgba(0,0,0,.18) !important;
            color: rgba(234,240,255,.92) !important;
        }

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

        .content-header .breadcrumb{
            background: rgba(0,0,0,.14) !important;
            border: 1px solid rgba(255,255,255,.10) !important;
            border-radius: 14px !important;
        }
        .breadcrumb-item a{ color: rgba(234,240,255,.78) !important; font-weight: 800; }
        .breadcrumb-item.active{ color: rgba(234,240,255,.92) !important; }

        .content-header h1{
            color: rgba(234,240,255,.92) !important;
            font-weight: 900;
            letter-spacing: -.3px;
        }
    </style>
    <style>
        .main-header .nav-link {
            position: relative;
        }

        .main-header .nav-link .hechos-revision-badge {
            position: absolute;
            top: 2px;
            right: -10px;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 6px;
            border-radius: 999px;
        }
    </style>

    @stack('styles')
</head>

<body class="@yield('classes_body')" @yield('body_data')>
    @yield('body')

    @if(config('adminlte.enabled_laravel_mix', false))
        <script src="{{ mix(config('adminlte.laravel_mix_js_path', 'js/app.js')) }}"></script>
    @else
        <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
        <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
        <script src="{{ asset('vendor/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>
        <script src="{{ asset('vendor/adminlte/dist/js/adminlte.min.js') }}"></script>
    @endif

    @include('adminlte::plugins', ['type' => 'js'])

    @if(config('adminlte.livewire'))
        @if(intval(app()->version()) >= 7)
            @livewireScripts
        @else
            <livewire:scripts />
        @endif
    @endif

    @yield('adminlte_js')
<script>
document.addEventListener('DOMContentLoaded', function () {

    function findRevisionBell() {
        return Array.from(document.querySelectorAll('.main-header .nav-link')).find(link => {
            const text = (link.textContent || '').replace(/\s+/g, ' ').trim();
            return text.includes('Revisión');
        });
    }

    function updateHechosRevisionBadge() {
        const bell = findRevisionBell();

        if (!bell) return;

        fetch('{{ route('hechos.pendientes_revision.count') }}', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            document.querySelectorAll('.hechos-revision-badge').forEach(el => {
                if (!bell.contains(el)) {
                    el.remove();
                }
            });

            let badge = bell.querySelector('.hechos-revision-badge');

            if (parseInt(data.count) > 0) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'badge badge-danger ml-1 hechos-revision-badge';
                    bell.appendChild(badge);
                }

                badge.textContent = data.count;
            } else {
                if (badge) {
                    badge.remove();
                }
            }
        })
        .catch(() => {});
    }

    updateHechosRevisionBadge();
    setInterval(updateHechosRevisionBadge, 15000);

});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const bell = document.getElementById('guardianesBell');
    if (!bell) return;

    const badge = document.createElement('span');
    badge.classList.add('badge', 'badge-danger', 'navbar-badge');
    badge.style.display = 'none';

    bell.appendChild(badge);

    async function actualizarCampana() {
        try {
            const response = await fetch("{{ route('guardianes_camino.countPendientesRevision') }}");
            const data = await response.json();

            if (data.total > 0) {
                badge.innerText = data.total;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        } catch (e) {
            console.error('Error campana guardianes:', e);
        }
    }

    actualizarCampana();
    setInterval(actualizarCampana, 30000); // cada 30s
});
</script>
    @stack('scripts')
</body>
</html>
