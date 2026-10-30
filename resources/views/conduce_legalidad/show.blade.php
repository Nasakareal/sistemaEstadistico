@extends('adminlte::page')

@section('title', 'Operativo Conduce con Legalidad')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:10px;">
        <div>
            <h1 class="mb-1">{{ $operativo['nombre'] ?? 'Conduce con Legalidad' }}</h1>
            <div class="text-muted">Operativo #{{ $operativo['id'] }}</div>
        </div>
        <a href="{{ route('conduce_legalidad.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Volver</a>
    </div>
@stop

@section('content')
    <div class="card card-outline card-primary mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Datos del operativo</h3>
            <span class="badge {{ ($operativo['estado'] ?? '') === 'activo' ? 'badge-success' : 'badge-secondary' }} p-2">{{ strtoupper($operativo['estado'] ?? 'N/D') }}</span>
        </div>
        <div class="card-body">
            <div class="row data-grid">
                <div class="col-md-3"><span>Fecha</span><strong>{{ !empty($operativo['fecha']) ? \Carbon\Carbon::parse($operativo['fecha'])->format('d/m/Y') : '—' }}</strong></div>
                <div class="col-md-3"><span>Horario</span><strong>{{ substr((string) ($operativo['hora_inicio'] ?? ''), 0, 5) ?: '—' }} @if(!empty($operativo['hora_cierre'])) a {{ substr($operativo['hora_cierre'], 0, 5) }} @endif</strong></div>
                <div class="col-md-3"><span>Municipio</span><strong>{{ $operativo['municipio'] ?? '—' }}</strong></div>
                <div class="col-md-3"><span>Registros visibles</span><strong>{{ (int) ($operativo['total_capturas'] ?? 0) }}</strong></div>
                <div class="col-md-6"><span>Ubicación</span><strong>{{ collect([$operativo['lugar'] ?? null, $operativo['numero'] ?? null, $operativo['colonia'] ?? null, $operativo['codigo_postal'] ?? null])->filter()->implode(', ') ?: '—' }}</strong></div>
                <div class="col-md-6"><span>Objetivo</span><strong>{{ $operativo['objetivo'] ?? '—' }}</strong></div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Alimentaciones / registros</h3>
        @if(!empty($operativo['can_feed']))
            <span class="badge badge-success p-2"><i class="fa-solid fa-circle-check"></i> Puedes apoyar este operativo desde la app móvil</span>
        @endif
    </div>

    @forelse(($operativo['capturas'] ?? []) as $captura)
        @php
            $personas = $captura['personas'] ?? [];
            $vehiculos = $captura['vehiculos'] ?? [];
            $fotos = $captura['fotos'] ?? [];
        @endphp
        <div class="card card-outline card-info captura-card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
                <div>
                    <strong>{{ $captura['folio'] ?? ('Registro #' . $captura['id']) }}</strong>
                    <span class="text-muted ml-2">{{ !empty($captura['fecha']) ? \Carbon\Carbon::parse($captura['fecha'])->format('d/m/Y') : '' }} {{ substr((string) ($captura['hora'] ?? ''), 0, 5) }}</span>
                </div>
                <span class="badge badge-light">{{ $captura['agente_nombre'] ?? data_get($captura, 'creador.name', 'Agente no especificado') }}</span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-5 mb-3">
                        <div class="section-label">Lugar</div>
                        <div>{{ collect([$captura['lugar'] ?? null, $captura['municipio'] ?? null])->filter()->implode(', ') ?: '—' }}</div>
                        @if(!empty($captura['narrativa']))
                            <div class="section-label mt-3">Narrativa</div>
                            <div class="text-wrap">{{ $captura['narrativa'] }}</div>
                        @endif
                    </div>
                    <div class="col-md-7 mb-3">
                        <div class="section-label">Personas</div>
                        @forelse($personas as $persona)
                            <span class="detail-chip"><i class="fa-solid fa-user"></i> {{ $persona['nombre'] ?: collect([$persona['nombres'] ?? null, $persona['apellido_paterno'] ?? null, $persona['apellido_materno'] ?? null])->filter()->implode(' ') }}</span>
                        @empty
                            <span class="text-muted">Sin personas registradas</span>
                        @endforelse

                        <div class="section-label mt-3">Vehículos</div>
                        @forelse($vehiculos as $vehiculo)
                            <div class="vehicle-box">
                                <strong>{{ collect([$vehiculo['marca'] ?? null, $vehiculo['linea'] ?? null, $vehiculo['modelo'] ?? null])->filter()->implode(' ') ?: 'Vehículo' }}</strong>
                                <span>{{ $vehiculo['placas'] ?: 'SIN PLACAS' }}</span>
                                @if(!empty($vehiculo['corralon']))<small>Corralón: {{ $vehiculo['corralon'] }}</small>@endif
                            </div>
                        @empty
                            <span class="text-muted">Sin vehículos registrados</span>
                        @endforelse
                    </div>
                </div>

                @if(count($fotos))
                    <div class="photo-grid">
                        @foreach($fotos as $foto)
                            <a href="{{ $foto['foto_url'] }}" target="_blank" rel="noopener">
                                <img src="{{ $foto['foto_thumbnail_url'] ?: $foto['foto_url'] }}" alt="Evidencia {{ $loop->iteration }}">
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="alert alert-info">No hay registros visibles para tu usuario en este operativo.</div>
    @endforelse
@stop

@section('css')
<style>
    .data-grid > div { margin-bottom: 16px; }
    .data-grid span, .section-label { display: block; color: rgba(234,240,255,.6); font-size: .78rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
    .data-grid strong { display: block; color: #eaf0ff; margin-top: 4px; }
    .captura-card { border-radius: 12px; overflow: hidden; }
    .detail-chip { display: inline-block; margin: 4px 6px 4px 0; padding: 6px 10px; border-radius: 20px; background: rgba(59,130,246,.16); border: 1px solid rgba(96,165,250,.3); }
    .vehicle-box { display: inline-flex; flex-direction: column; margin: 4px 8px 4px 0; padding: 9px 12px; border-radius: 10px; background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.12); min-width: 190px; }
    .vehicle-box span { color: #93c5fd; font-weight: 800; }
    .photo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 10px; margin-top: 12px; }
    .photo-grid img { width: 100%; height: 120px; object-fit: cover; border-radius: 10px; border: 1px solid rgba(255,255,255,.15); }
    .text-wrap { white-space: pre-wrap; }
</style>
@stop
