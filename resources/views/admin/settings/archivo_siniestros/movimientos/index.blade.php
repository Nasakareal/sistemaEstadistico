@extends('adminlte::page')

@section('title', 'Movimientos del libro')

@section('content_header')
    <h1>Historial de movimientos · {{ $libro->codigo }}</h1>
@stop

@section('content')
    @include('admin.settings.archivo_siniestros._nav')
    @include('admin.settings.archivo_siniestros._alerts')
    <div class="card card-outline card-primary">
    <div class="card-header"><h3 class="card-title">Trazabilidad del libro físico</h3><div class="card-tools"><a href="{{ route('archivo_siniestros.libros.show', $libro) }}" class="btn btn-secondary btn-sm">Volver al libro</a></div></div>
    <div class="card-body table-responsive p-0"><table class="table table-striped table-bordered mb-0"><thead><tr><th>Fecha</th><th>Movimiento</th><th>Origen</th><th>Destino</th><th>Responsable</th><th>Motivo</th></tr></thead><tbody>
        @forelse($movimientos as $movimiento)
            <tr>
                <td>{{ optional($movimiento->fecha_movimiento)->format('d/m/Y H:i') ?: '—' }}</td>
                <td><span class="badge badge-info">{{ str_replace('_', ' ', ucfirst($movimiento->tipo)) }}</span></td>
                <td>{{ $movimiento->areaOrigen->nombre ?? ($movimiento->bloqueOrigen->anaquel->area->nombre ?? '—') }}@if($movimiento->bloqueOrigen)<br>{{ $movimiento->bloqueOrigen->anaquel->codigo ?? '—' }} / {{ $movimiento->bloqueOrigen->codigo }} / P{{ $movimiento->origen_posicion }}@endif</td>
                <td>{{ $movimiento->areaDestino->nombre ?? ($movimiento->bloqueDestino->anaquel->area->nombre ?? '—') }}@if($movimiento->bloqueDestino)<br>{{ $movimiento->bloqueDestino->anaquel->codigo ?? '—' }} / {{ $movimiento->bloqueDestino->codigo }} / P{{ $movimiento->destino_posicion }}@endif</td>
                <td>{{ $movimiento->usuario->name ?? $movimiento->responsable_externo ?? '—' }}</td>
                <td>{{ $movimiento->motivo ?: '—' }}</td>
            </tr>
        @empty<tr><td colspan="6" class="text-center py-4">Aún no hay movimientos registrados.</td></tr>@endforelse
    </tbody></table></div>
    <div class="card-footer">{{ $movimientos->links('pagination::bootstrap-4') }}</div>
</div>
@stop
