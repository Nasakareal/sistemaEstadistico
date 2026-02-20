@extends('adminlte::page')

@section('title', 'Alertas Waze')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1 class="mb-0">Alertas Waze</h1>

    <div class="d-flex gap-2">
        <form method="GET" action="{{ route('waze.alerts.index') }}" class="d-flex" style="gap:8px;">
            <select name="solo" class="form-control">
                <option value="">Todas</option>
                <option value="accidentes" {{ request('solo')=='accidentes' ? 'selected' : '' }}>Solo accidentes</option>
            </select>

            <select name="tipo" class="form-control">
                <option value="">Tipo (todos)</option>
                <option value="ACCIDENT" {{ request('tipo')=='ACCIDENT' ? 'selected' : '' }}>ACCIDENT</option>
                <option value="HAZARD" {{ request('tipo')=='HAZARD' ? 'selected' : '' }}>HAZARD</option>
                <option value="JAM" {{ request('tipo')=='JAM' ? 'selected' : '' }}>JAM</option>
                <option value="ROAD_CLOSED" {{ request('tipo')=='ROAD_CLOSED' ? 'selected' : '' }}>ROAD_CLOSED</option>
            </select>

            <button class="btn btn-primary">Filtrar</button>
        </form>

        <form method="POST" action="{{ route('waze.alerts.read_all') }}">
            @csrf
            <button class="btn btn-secondary">
                <i class="fas fa-check-double"></i> Marcar todo como leído
            </button>
        </form>
    </div>
</div>
@stop

@section('content')
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th style="width:60px;">Estado</th>
                    <th>Tipo</th>
                    <th>Subtipo</th>
                    <th>Lugar</th>
                    <th>Fecha</th>
                    <th style="width:160px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($alerts as $a)
                    @php
                        $street = $a->street ?: 'Sin calle';
                        $city = $a->city ?: '';
                        $place = trim($street . ($city ? ', ' . $city : ''));
                        $mapsUrl = ($a->lat && $a->lng) ? ('https://www.google.com/maps/search/?api=1&query=' . $a->lat . ',' . $a->lng) : null;
                    @endphp

                    <tr class="{{ (int)$a->is_read === 0 ? 'font-weight-bold' : '' }}">
                        <td>
                            @if((int)$a->is_read === 0)
                                <span class="badge badge-danger">NUEVA</span>
                            @else
                                <span class="badge badge-secondary">LEÍDA</span>
                            @endif
                        </td>
                        <td>{{ $a->type }}</td>
                        <td>{{ $a->subtype }}</td>
                        <td>{{ $place }}</td>
                        <td>{{ optional($a->published_at)->format('Y-m-d H:i') }}</td>
                        <td class="d-flex" style="gap:8px;">
                            @if($mapsUrl)
                                <a class="btn btn-sm btn-info" target="_blank" href="{{ $mapsUrl }}">
                                    <i class="fas fa-map-marker-alt"></i> Mapa
                                </a>
                            @endif

                            @if((int)$a->is_read === 0)
                                <form method="POST" action="{{ route('waze.alerts.read', $a->id) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-success">
                                        <i class="fas fa-check"></i> Leída
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">No hay alertas.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-3">
            {{ $alerts->links() }}
        </div>
    </div>
</div>
@stop
