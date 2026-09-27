@extends('adminlte::page')

@section('title', 'Eventos de Seguridad')

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h1 class="mb-1"><i class="fas fa-shield-alt text-danger mr-2"></i>Eventos de Seguridad</h1>
            <p class="text-muted mb-0">Intentos de acceso, autenticación, límites y patrones de escaneo.</p>
        </div>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary mt-2 mt-md-0">
            <i class="fas fa-arrow-left mr-1"></i> Configuraciones
        </a>
    </div>
@stop

@section('content')
    @php
        $severityClasses = [
            'info' => 'badge-info',
            'warning' => 'badge-warning',
            'high' => 'badge-danger',
            'critical' => 'badge-dark',
        ];
        $severityLabels = [
            'info' => 'Informativo',
            'warning' => 'Advertencia',
            'high' => 'Alto',
            'critical' => 'Crítico',
        ];
    @endphp

    <div class="alert alert-info">
        <i class="fas fa-info-circle mr-1"></i>
        La IP corresponde a <code>request()-&gt;ip()</code>. Si el sistema está detrás de Cloudflare, un balanceador o proxy,
        primero deben configurarse correctamente los proxies confiables. Una intención mostrada es una inferencia por la ruta solicitada,
        no una prueba de lo que pensaba la persona. No bloquees una IP sólo por volumen: puede ser una oficina, VPN o red móvil compartida.
    </div>

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner"><h3>{{ number_format($summary['total']) }}</h3><p>Eventos en 24 horas</p></div>
                <div class="icon"><i class="fas fa-list"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner"><h3>{{ number_format($summary['high']) }}</h3><p>Altos o críticos</p></div>
                <div class="icon"><i class="fas fa-triangle-exclamation"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner"><h3>{{ number_format($summary['failed_logins']) }}</h3><p>Logins fallidos</p></div>
                <div class="icon"><i class="fas fa-key"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-secondary">
                <div class="inner"><h3>{{ number_format($summary['ips']) }}</h3><p>IPs distintas</p></div>
                <div class="icon"><i class="fas fa-network-wired"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-danger">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-filter mr-1"></i>Filtros</h3></div>
        <div class="card-body">
            <form method="GET" action="{{ route('settings.security_events.index') }}">
                <div class="form-row">
                    <div class="form-group col-lg-2 col-md-4">
                        <label for="severity">Severidad</label>
                        <select id="severity" name="severity" class="form-control">
                            <option value="">Todas</option>
                            @foreach($severityLabels as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['severity'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-lg-2 col-md-4">
                        <label for="category">Categoría</label>
                        <select id="category" name="category" class="form-control">
                            <option value="">Todas</option>
                            @foreach($categories as $category)
                                <option value="{{ $category }}" @selected(($filters['category'] ?? '') === $category)>{{ $category }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-lg-2 col-md-4">
                        <label for="event">Evento</label>
                        <select id="event" name="event" class="form-control">
                            <option value="">Todos</option>
                            @foreach($eventCodes as $eventCode)
                                <option value="{{ $eventCode }}" @selected(($filters['event'] ?? '') === $eventCode)>{{ $eventCode }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-lg-2 col-md-4">
                        <label for="ip">IP exacta</label>
                        <input id="ip" name="ip" class="form-control" value="{{ $filters['ip'] ?? '' }}" placeholder="192.0.2.10">
                    </div>
                    <div class="form-group col-lg-2 col-md-4">
                        <label for="from">Desde</label>
                        <input id="from" name="from" type="date" class="form-control" value="{{ $filters['from'] ?? '' }}">
                    </div>
                    <div class="form-group col-lg-2 col-md-4">
                        <label for="to">Hasta</label>
                        <input id="to" name="to" type="date" class="form-control" value="{{ $filters['to'] ?? '' }}">
                    </div>
                    <div class="form-group col-lg-8 col-md-8">
                        <label for="search">Buscar en descripción, ruta, nombre de ruta o navegador</label>
                        <input id="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Ej. wp-admin, login, Android">
                    </div>
                    <div class="form-group col-lg-2 col-md-2">
                        <label for="user_id">ID usuario</label>
                        <input id="user_id" name="user_id" type="number" min="1" class="form-control" value="{{ $filters['user_id'] ?? '' }}">
                    </div>
                    <div class="form-group col-lg-2 col-md-2 d-flex align-items-end">
                        <button class="btn btn-danger mr-2" type="submit"><i class="fas fa-search mr-1"></i>Aplicar</button>
                        <a class="btn btn-outline-secondary" href="{{ route('settings.security_events.index') }}" title="Limpiar filtros"><i class="fas fa-eraser"></i></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-outline card-warning">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-location-crosshairs mr-1"></i>IPs con más actividad en 24 horas</h3>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead><tr><th>IP</th><th>Total</th><th>Altos/críticos</th><th>Último evento</th><th>Evaluación</th><th></th></tr></thead>
                <tbody>
                @forelse($topIps as $row)
                    @php $review = (int) $row->high_events >= $reviewThreshold; @endphp
                    <tr>
                        <td><code>{{ $row->ip_address }}</code></td>
                        <td>{{ number_format((int) $row->total_events) }}</td>
                        <td>{{ number_format((int) $row->high_events) }}</td>
                        <td>{{ \Carbon\Carbon::parse($row->last_seen)->format('d/m/Y H:i:s') }}</td>
                        <td>
                            <span class="badge {{ $review ? 'badge-danger' : 'badge-secondary' }}">
                                {{ $review ? 'Revisar posible bloqueo' : 'Observar' }}
                            </span>
                        </td>
                        <td><a href="{{ route('settings.security_events.index', ['ip' => $row->ip_address]) }}" class="btn btn-xs btn-outline-primary">Ver eventos</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">Aún no hay actividad registrada.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card card-outline card-dark">
        <div class="card-header d-flex align-items-center">
            <h3 class="card-title"><i class="fas fa-clock-rotate-left mr-1"></i>Historial</h3>
            <span class="badge badge-secondary ml-auto">{{ number_format($events->total()) }} filas agrupadas</span>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-striped table-hover table-sm security-events-table mb-0">
                <thead>
                <tr>
                    <th>Última vez</th><th>Severidad</th><th>Evento</th><th>IP / usuario</th><th>Solicitud</th><th>Veces</th><th>Detalle</th>
                </tr>
                </thead>
                <tbody>
                @forelse($events as $event)
                    <tr>
                        <td class="text-nowrap">{{ optional($event->last_seen_at)->format('d/m/Y H:i:s') }}</td>
                        <td><span class="badge {{ $severityClasses[$event->severity] ?? 'badge-secondary' }}">{{ $severityLabels[$event->severity] ?? $event->severity }}</span></td>
                        <td>
                            <code>{{ $event->event_code }}</code>
                            <div class="small text-muted">{{ $event->description }}</div>
                            <div class="small mt-1"><i class="fas fa-magnifying-glass mr-1"></i>{{ $event->assessmentLabel() }}</div>
                        </td>
                        <td>
                            <a href="{{ route('settings.security_events.index', ['ip' => $event->ip_address]) }}"><code>{{ $event->ip_address ?: 'sin IP' }}</code></a>
                            <div class="small text-muted">
                                @if($event->user)
                                    #{{ $event->user->id }} {{ $event->user->name }}
                                @else
                                    Sin usuario autenticado
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-light">{{ $event->method ?: '—' }}</span>
                            @if($event->status_code)<span class="badge badge-light">HTTP {{ $event->status_code }}</span>@endif
                            <div class="security-path" title="{{ $event->path }}">{{ $event->path ?: '—' }}</div>
                            @if($event->route_name)<div class="small text-muted">{{ $event->route_name }}</div>@endif
                            <div class="small text-info mt-1"><strong>Intención probable:</strong> {{ $event->intentLabel() }}</div>
                        </td>
                        <td><span class="badge badge-pill badge-primary">{{ number_format($event->occurrences) }}</span></td>
                        <td>
                            <details>
                                <summary>Ver</summary>
                                <div class="small mt-2"><strong>Primera vez:</strong> {{ optional($event->first_seen_at)->format('d/m/Y H:i:s') }}</div>
                                <div class="small"><strong>Request ID:</strong> {{ $event->request_id ?: '—' }}</div>
                                <div class="small text-break"><strong>Agente:</strong> {{ $event->user_agent ?: '—' }}</div>
                                @if($event->metadata)
                                    <pre class="security-json">{{ json_encode($event->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                @endif
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No hay eventos que coincidan con los filtros.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($events->hasPages())
            <div class="card-footer">{{ $events->links() }}</div>
        @endif
    </div>
@stop

@section('css')
<style>
    .security-events-table { min-width: 1180px; }
    .security-path { max-width: 330px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .security-json { max-width: 520px; max-height: 260px; overflow: auto; margin: .5rem 0 0; padding: .6rem; color: #e9ecef; background: #212529; border-radius: .35rem; font-size: 11px; }
    details summary { cursor: pointer; color: #007bff; }
</style>
@stop
