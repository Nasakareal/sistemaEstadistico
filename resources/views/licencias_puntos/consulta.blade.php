<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Consulta de puntos de licencia</title>
    <link rel="icon" href="{{ asset('icon.ico') }}" type="image/x-icon">
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
    <style>
        :root {
            --bg0: #07111f;
            --bg1: #0b1d2f;
            --panel: rgba(255, 255, 255, .08);
            --panel2: rgba(255, 255, 255, .05);
            --stroke: rgba(255, 255, 255, .14);
            --text: #eef6ff;
            --muted: rgba(238, 246, 255, .68);
            --brand: #2f9fe8;
            --ok: #27d28f;
            --warn: #ffcc66;
            --danger: #ff6b7a;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            color: var(--text);
            background:
                radial-gradient(900px 620px at 18% 12%, rgba(47, 159, 232, .22), transparent 60%),
                radial-gradient(760px 540px at 78% 18%, rgba(112, 91, 255, .20), transparent 55%),
                linear-gradient(145deg, var(--bg0), var(--bg1) 54%, #080d1a);
            font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
        }

        .public-shell {
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 42px 16px;
        }

        .public-wrap {
            width: min(100%, 980px);
        }

        .public-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            margin-bottom: 22px;
        }

        .public-brand img {
            width: 48px;
            height: 48px;
            object-fit: contain;
            filter: drop-shadow(0 10px 24px rgba(0, 0, 0, .30));
        }

        .public-brand span {
            font-weight: 900;
            font-size: 18px;
            letter-spacing: 0;
        }

        .hero {
            text-align: center;
            margin-bottom: 24px;
        }

        .hero h1 {
            margin: 0;
            font-size: 34px;
            line-height: 1.15;
            font-weight: 900;
            letter-spacing: 0;
        }

        .hero p {
            margin: 8px auto 0;
            color: var(--muted);
            font-size: 17px;
            max-width: 640px;
        }

        .panel {
            background: linear-gradient(180deg, var(--panel), var(--panel2));
            border: 1px solid var(--stroke);
            border-radius: 8px;
            box-shadow: 0 24px 64px rgba(0, 0, 0, .36);
            overflow: hidden;
        }

        .panel + .panel {
            margin-top: 18px;
        }

        .panel-header {
            padding: 17px 22px;
            border-bottom: 1px solid rgba(255, 255, 255, .10);
            background: rgba(0, 0, 0, .14);
            font-size: 20px;
            font-weight: 800;
        }

        .panel-body {
            padding: 22px;
        }

        label {
            color: rgba(238, 246, 255, .82);
            font-weight: 800;
        }

        .form-control {
            min-height: 52px;
            color: var(--text);
            background: rgba(0, 0, 0, .20);
            border: 1px solid rgba(255, 255, 255, .14);
            border-radius: 8px;
        }

        .form-control:focus {
            color: var(--text);
            background: rgba(0, 0, 0, .25);
            border-color: rgba(47, 159, 232, .65);
            box-shadow: 0 0 0 .2rem rgba(47, 159, 232, .16);
        }

        .btn-consulta {
            min-height: 52px;
            border: 0;
            border-radius: 8px;
            padding: 0 24px;
            background: var(--brand);
            color: #ffffff;
            font-weight: 900;
        }

        .btn-consulta:hover,
        .btn-consulta:focus {
            color: #ffffff;
            background: #238ed6;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .summary-item {
            min-height: 128px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 8px;
            background: rgba(0, 0, 0, .12);
            padding: 16px;
        }

        .summary-label {
            color: var(--muted);
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .summary-value {
            margin-top: 8px;
            font-size: 32px;
            line-height: 1;
            font-weight: 900;
        }

        .summary-value.is-ok {
            color: var(--ok);
        }

        .summary-value.is-warn {
            color: var(--warn);
        }

        .summary-value.is-danger {
            color: var(--danger);
        }

        .summary-value.is-dark {
            color: #d6d9e6;
        }

        .table {
            color: rgba(238, 246, 255, .92);
            margin-bottom: 0;
        }

        .table th,
        .table td {
            border-top-color: rgba(255, 255, 255, .09);
            vertical-align: middle;
        }

        .table thead th {
            border-bottom-color: rgba(255, 255, 255, .12);
            background: rgba(0, 0, 0, .14);
            color: rgba(238, 246, 255, .90);
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background: rgba(255, 255, 255, .035);
        }

        .muted {
            color: var(--muted);
        }

        @media (max-width: 767.98px) {
            .public-shell {
                padding: 24px 12px;
            }

            .hero h1 {
                font-size: 27px;
            }

            .hero p {
                font-size: 15px;
            }

            .input-group {
                display: block;
            }

            .input-group .form-control,
            .input-group-append,
            .input-group-append .btn {
                width: 100%;
            }

            .input-group-append {
                margin-top: 10px;
                margin-left: 0;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <main class="public-shell">
        <div class="public-wrap">
            <div class="public-brand">
                <img src="{{ asset('guardiacivil.png') }}" alt="Seguridad Vial">
                <span>Coordinación del Agrupamiento de Seguridad Vial</span>
            </div>

            <section class="hero" aria-labelledby="consulta-title">
                <h1 id="consulta-title">Consulta de puntos de licencia</h1>
                <p>Saldo disponible, historial y fecha estimada de recuperacion.</p>
            </section>

            <section class="panel" aria-label="Buscar licencia">
                <div class="panel-header">Buscar licencia</div>
                <form method="POST" action="{{ route('licencias_puntos.consulta') }}">
                    @csrf
                    <div class="panel-body">
                        <label for="numero_licencia">Numero de licencia</label>
                        <div class="input-group">
                            <input
                                type="text"
                                name="numero_licencia"
                                id="numero_licencia"
                                class="form-control form-control-lg"
                                value="{{ request('numero_licencia') }}"
                                autocomplete="off"
                                required
                            >
                            <div class="input-group-append">
                                <button class="btn btn-consulta btn-lg" type="submit">
                                    <i class="fas fa-search"></i> Consultar
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </section>

            @if($saldoAsumido)
                <section class="panel" aria-label="Resultado de consulta">
                    <div class="panel-header">{{ $numeroConsultado }}</div>
                    <div class="panel-body">
                        <div class="summary-grid">
                            <div class="summary-item">
                                <span class="summary-label">Puntos disponibles</span>
                                <strong class="summary-value is-ok">8</strong>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Estado</span>
                                <strong class="summary-value">Vigente</strong>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Historial</span>
                                <strong class="summary-value">Sin movimientos</strong>
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            @if($cuenta)
                @php
                    $badgeClass = [
                        'normal' => 'is-ok',
                        'advertencia' => 'is-warn',
                        'critico' => 'is-danger',
                        'agotado' => 'is-dark',
                    ][$cuenta->nivel_saldo] ?? '';
                @endphp
                <section class="panel" aria-label="Saldo de licencia">
                    <div class="panel-header">{{ $cuenta->titular_nombre }}</div>
                    <div class="panel-body">
                        <div class="summary-grid">
                            <div class="summary-item">
                                <span class="summary-label">Puntos disponibles</span>
                                <strong class="summary-value {{ $badgeClass }}">{{ $cuenta->saldo_actual }}</strong>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Estado</span>
                                <strong class="summary-value">{{ $cuenta->estado_label }}</strong>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Fecha de recuperacion</span>
                                <strong class="summary-value">
                                    {{ $cuenta->fecha_recuperacion ? $cuenta->fecha_recuperacion->format('d/m/Y') : 'Saldo completo' }}
                                </strong>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="panel" aria-label="Historial de movimientos">
                    <div class="panel-header">Historial</div>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Movimiento</th>
                                    <th>Puntos</th>
                                    <th>Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($movimientos as $movimiento)
                                    <tr>
                                        <td>{{ $movimiento->fecha_movimiento ? $movimiento->fecha_movimiento->format('d/m/Y') : 'N/A' }}</td>
                                        <td>
                                            <strong>{{ optional($movimiento->infraccion)->nombre ?: str_replace('_', ' ', ucfirst($movimiento->tipo)) }}</strong>
                                            @if(optional($movimiento->infraccion)->fundamento_legal)
                                                <small class="d-block muted">{{ $movimiento->infraccion->fundamento_legal }}</small>
                                            @endif
                                            <small class="d-block muted">{{ $movimiento->descripcion }}</small>
                                        </td>
                                        <td>{{ $movimiento->puntos > 0 ? '+' : '' }}{{ $movimiento->puntos }}</td>
                                        <td>{{ $movimiento->saldo_nuevo }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center muted py-3">Sin movimientos.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        </div>
    </main>
</body>
</html>
