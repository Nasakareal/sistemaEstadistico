@extends('adminlte::page')

@section('title', 'Hechos eliminados')

@section('content_header')
    <h1>Hechos de tránsito eliminados</h1>
@stop

@section('content')
    <div class="card card-outline card-warning">
        <div class="card-header">
            <h3 class="card-title">Archivo recuperable</h3>
            <div class="card-tools">
                <a href="{{ route('hechos.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left"></i> Volver a hechos
                </a>
            </div>
        </div>

        <div class="card-body">
            <p class="text-muted">
                Estos registros no se borraron definitivamente. Aquí se conserva el hecho, sus vehículos y sus datos relacionados.
            </p>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>ID original</th>
                            <th>Fecha del hecho</th>
                            <th>Folio</th>
                            <th>Ubicación</th>
                            <th>Vehículos</th>
                            <th>Eliminado</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($archivos as $archivo)
                            @php($hecho = $archivo->datos_hecho ?? [])
                            <tr>
                                <td>{{ $archivo->hecho_id_original }}</td>
                                <td>{{ $hecho['fecha'] ?? 'Sin fecha' }}</td>
                                <td>{{ $hecho['folio_c5i'] ?? 'Sin folio' }}</td>
                                <td>
                                    {{ $hecho['calle'] ?? '' }}
                                    {{ !empty($hecho['colonia']) ? ', '.$hecho['colonia'] : '' }}
                                    {{ !empty($hecho['municipio']) ? ', '.$hecho['municipio'] : '' }}
                                </td>
                                <td>
                                    <span class="badge badge-info">{{ $archivo->vehiculos->count() }}</span>
                                    @foreach($archivo->vehiculos->take(3) as $vehiculo)
                                        @php($datosVehiculo = $vehiculo->datos_vehiculo ?? [])
                                        <div class="small">
                                            {{ $datosVehiculo['marca'] ?? 'Vehículo' }}
                                            {{ $datosVehiculo['linea'] ?? '' }}
                                            {{ !empty($datosVehiculo['placas']) ? ' · '.$datosVehiculo['placas'] : '' }}
                                        </div>
                                    @endforeach
                                </td>
                                <td>
                                    {{ optional($archivo->eliminado_at)->format('d/m/Y H:i') }}
                                    <div class="small text-muted">{{ optional($archivo->eliminadoPor)->name ?? 'Usuario no disponible' }}</div>
                                </td>
                                <td>
                                    @if($archivo->restaurado_at)
                                        <span class="badge badge-success">Restaurado</span>
                                        <div class="small">Hecho #{{ $archivo->hecho_id_restaurado }}</div>
                                    @else
                                        <span class="badge badge-warning">Disponible</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!$archivo->restaurado_at)
                                        <form method="POST"
                                              action="{{ route('hechos.eliminados.restore', $archivo) }}"
                                              onsubmit="return confirm('¿Restaurar este hecho con sus vehículos y datos relacionados?');">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="fa-solid fa-rotate-left"></i> Restaurar
                                            </button>
                                        </form>
                                    @elseif($archivo->hecho_id_restaurado)
                                        <a href="{{ route('hechos.show', $archivo->hecho_id_restaurado) }}" class="btn btn-info btn-sm">
                                            <i class="fa-solid fa-eye"></i> Ver
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">No hay hechos eliminados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $archivos->links() }}
        </div>
    </div>
@stop
