<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal de Gruas')</title>
    <style>
        :root {
            --bg: #0f172a;
            --panel: #172033;
            --panel-2: #202b42;
            --text: #edf2ff;
            --muted: #aab6ce;
            --line: rgba(255, 255, 255, .12);
            --primary: #38bdf8;
            --danger: #ef4444;
            --ok: #22c55e;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        a { color: inherit; }
        .grua-shell { min-height: 100vh; }
        .grua-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 22px;
            border-bottom: 1px solid var(--line);
            background: rgba(15, 23, 42, .92);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .grua-brand { font-weight: 900; letter-spacing: .2px; }
        .grua-user { color: var(--muted); font-size: 13px; }
        .grua-main { width: min(1180px, calc(100% - 28px)); margin: 22px auto; }
        .grua-heading { margin-bottom: 16px; }
        .grua-heading h1 { margin: 0; font-size: 24px; line-height: 1.15; }
        .grua-heading p { margin: 6px 0 0; color: var(--muted); }

        .panel {
            border: 1px solid var(--line);
            background: var(--panel);
            border-radius: 8px;
            overflow: hidden;
        }

        .panel-body { padding: 16px; }
        .table-wrap { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 760px; }
        th, td { padding: 11px 10px; border-bottom: 1px solid var(--line); text-align: left; vertical-align: middle; }
        th { color: var(--muted); font-size: 12px; text-transform: uppercase; }
        tr:last-child td { border-bottom: 0; }
        .muted { color: var(--muted); }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 999px; font-size: 12px; font-weight: 800; }
        .badge-ok { background: rgba(34, 197, 94, .16); color: #bbf7d0; border: 1px solid rgba(34, 197, 94, .32); }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 36px;
            border: 1px solid transparent;
            border-radius: 6px;
            padding: 8px 12px;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
            color: var(--text);
            background: var(--panel-2);
        }

        .btn-primary { background: var(--primary); color: #06111f; }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-ghost { border-color: var(--line); background: transparent; }
        .actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .form-row { display: flex; flex-direction: column; gap: 6px; }
        .form-row.full { grid-column: 1 / -1; }
        label { color: var(--muted); font-size: 13px; font-weight: 800; }
        input, select, textarea {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 6px;
            background: #101827;
            color: var(--text);
            padding: 10px;
        }
        textarea { min-height: 90px; resize: vertical; }
        .alert { padding: 12px 14px; border-radius: 8px; margin-bottom: 14px; }
        .alert-success { background: rgba(34, 197, 94, .14); border: 1px solid rgba(34, 197, 94, .3); }
        .alert-danger { background: rgba(239, 68, 68, .14); border: 1px solid rgba(239, 68, 68, .3); }
        .empty { padding: 28px 16px; text-align: center; color: var(--muted); }
        .pager { margin-top: 14px; }

        @media (max-width: 760px) {
            .grua-topbar { align-items: flex-start; flex-direction: column; }
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
    @yield('css')
</head>
<body>
    <div class="grua-shell">
        <header class="grua-topbar">
            <div>
                <div class="grua-brand">Portal de Gruas</div>
                @if(Auth::guard('grua')->check())
                    <div class="grua-user">
                        {{ Auth::guard('grua')->user()->nombre }}
                        @if(Auth::guard('grua')->user()->grua)
                            · {{ Auth::guard('grua')->user()->grua->nombre }}
                        @endif
                    </div>
                @endif
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-ghost">Salir</button>
            </form>
        </header>

        <main class="grua-main">
            <div class="grua-heading">
                @yield('content_header')
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @yield('content')
        </main>
    </div>
    @yield('js')
</body>
</html>
