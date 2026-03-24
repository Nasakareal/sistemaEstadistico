@extends('adminlte::page')

@section('title', 'Guardianes del Camino')

@section('content_header')
    <h1>Operativo Único Guardianes del Camino</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Dispositivos registrados</h3>
                    <div class="card-tools">
                        <a href="{{ route('guardianes_camino.dispositivos.pendientes_revision', ['fecha' => request('fecha', now()->format('Y-m-d'))]) }}" class="btn btn-warning mr-2">
                            <i class="fa-solid fa-clipboard-check"></i> Pendientes de revisión
                        </a>

                        <a href="{{ route('guardianes_camino.dispositivos.create', ['fecha' => request('fecha', now()->format('Y-m-d'))]) }}" class="btn btn-primary">
                            <i class="fa-solid fa-plus"></i> Añadir dispositivo
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" action="{{ route('guardianes_camino.index') }}" id="formFiltroFecha" class="row mb-3" autocomplete="off">
                        <div class="col-md-4">
                            <label for="fecha">Fecha:</label>
                            <input
                                type="date"
                                id="fecha"
                                name="fecha"
                                class="form-control"
                                value="{{ request('fecha', now()->format('Y-m-d')) }}"
                            >
                        </div>

                        <div class="col-md-8 d-flex align-items-end">
                            <a href="{{ route('guardianes_camino.index') }}" class="btn btn-secondary mr-2">
                                <i class="fa-solid fa-rotate-left"></i> Hoy
                            </a>

                            <a href="{{ route('guardianes_camino.resumen', ['fecha' => request('fecha', now()->format('Y-m-d'))]) }}" class="btn btn-warning mr-2">
                                <i class="fa-solid fa-chart-column"></i> Resumen
                            </a>

                            <a href="{{ route('guardianes_camino.whatsapp', ['fecha' => request('fecha', now()->format('Y-m-d'))]) }}" class="btn btn-success">
                                <i class="fa-brands fa-whatsapp"></i> WhatsApp
                            </a>
                        </div>
                    </form>

                    @if ($dispositivos->count() === 0)
                        <div class="alert alert-info">
                            No hay dispositivos registrados para la fecha seleccionada.
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table id="guardianes_camino" class="table table-striped table-bordered table-hover table-sm">
                            <thead>
                                <tr>
                                    <th><center>ID</center></th>
                                    <th><center>Fecha y Hora</center></th>
                                    <th><center>Tipo de dispositivo</center></th>
                                    <th><center>Lugar</center></th>
                                    <th><center>Destacamento</center></th>
                                    <th><center>Capturó</center></th>
                                    <th><center>Estado revisión</center></th>
                                    <th><center>Revisado por</center></th>
                                    <th><center>Fecha revisión</center></th>
                                    <th><center>Acciones</center></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dispositivos as $dispositivo)
                                    <tr>
                                        <td>{{ $dispositivo->id }}</td>
                                        <td>
                                            {{ optional($dispositivo->fecha)->format('d-m-Y') }}
                                            <br>
                                            <small>{{ $dispositivo->hora ?? 'Sin hora' }}</small>
                                        </td>
                                        <td>{{ $dispositivo->catalogo->nombre ?? 'Sin tipo' }}</td>
                                        <td>{{ $dispositivo->lugar ?? 'Sin lugar' }}</td>
                                        <td>{{ $dispositivo->destacamento->nombre ?? 'Sin destacamento' }}</td>
                                        <td>{{ $dispositivo->usuario->name ?? 'Desconocido' }}</td>
                                        <td>
                                            @php
                                                $estadoRevision = $dispositivo->estado_revision ?? 'pendiente';
                                            @endphp

                                            @if ($estadoRevision === 'aprobado')
                                                <span class="badge badge-success">Aprobado</span>
                                            @elseif ($estadoRevision === 'rechazado')
                                                <span class="badge badge-danger">Rechazado</span>
                                            @else
                                                <span class="badge badge-warning">Pendiente</span>
                                            @endif
                                        </td>
                                        <td>{{ $dispositivo->revisadoPor->name ?? 'Sin revisar' }}</td>
                                        <td>
                                            @if ($dispositivo->revisado_at)
                                                {{ $dispositivo->revisado_at->format('d-m-Y H:i') }}
                                            @else
                                                Sin revisar
                                            @endif
                                        </td>
                                        <td style="text-align: center; white-space: nowrap;">
                                            <a href="{{ route('guardianes_camino.dispositivos.show', $dispositivo->id) }}" class="btn btn-info btn-sm" title="Ver">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>

                                            @can('editar operativos carreteras')
                                                <a href="{{ route('guardianes_camino.dispositivos.edit', $dispositivo->id) }}" class="btn btn-success btn-sm" title="Editar">
                                                    <i class="fa-solid fa-pencil"></i>
                                                </a>
                                            @endcan

                                            @can('editar operativos carreteras')
                                                @if (($dispositivo->estado_revision ?? 'pendiente') === 'pendiente')
                                                    <form action="{{ route('guardianes_camino.dispositivos.aprobar_revision', $dispositivo->id) }}" method="POST" style="display:inline-block;">
                                                        @csrf
                                                        <button type="submit" class="btn btn-primary btn-sm" title="Aprobar">
                                                            <i class="fa-solid fa-check"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            @endcan

                                            @can('eliminar operativos carreteras')
                                                <form action="{{ route('guardianes_camino.dispositivos.destroy', $dispositivo->id) }}" method="POST" style="display:inline-block;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn btn-danger btn-sm delete-btn" data-name="{{ $dispositivo->catalogo->nombre ?? 'Dispositivo' }}">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $dispositivos->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
<script>
    $(function () {
        $('#fecha').on('change', function () {
            $('#formFiltroFecha').submit();
        });
    });

    $(document).on('click', '.delete-btn', function (e) {
        e.preventDefault();

        let form = $(this).closest('form');
        let nombre = $(this).data('name');

        Swal.fire({
            title: '¿Estás seguro?',
            text: 'Se eliminará el dispositivo ' + nombre + '.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
</script>
@stop
