@extends('adminlte::page')

@section('title', 'Pendientes de revisión')

@section('content_header')
    <h1>Dispositivos pendientes de revisión</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">Listado de pendientes</h3>
                    <div class="card-tools">
                        <a href="{{ route('guardianes_camino.index', ['fecha' => request('fecha', now()->format('Y-m-d'))]) }}" class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-arrow-left"></i> Volver al listado
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" action="{{ route('guardianes_camino.dispositivos.pendientes_revision') }}" id="formFiltroFecha" class="row mb-3" autocomplete="off">
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
                            <button type="submit" class="btn btn-primary mr-2">
                                <i class="fa-solid fa-filter"></i> Filtrar
                            </button>

                            <a href="{{ route('guardianes_camino.dispositivos.pendientes_revision') }}" class="btn btn-secondary">
                                <i class="fa-solid fa-rotate-left"></i> Limpiar
                            </a>
                        </div>
                    </form>

                    @if ($dispositivos->count() === 0)
                        <div class="alert alert-info">
                            No hay dispositivos pendientes de revisión.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th><center>ID</center></th>
                                        <th><center>Fecha</center></th>
                                        <th><center>Hora</center></th>
                                        <th><center>Dispositivo</center></th>
                                        <th><center>Lugar</center></th>
                                        <th><center>Destacamento</center></th>
                                        <th><center>Capturó</center></th>
                                        <th><center>Estado</center></th>
                                        <th><center>Acciones</center></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($dispositivos as $dispositivo)
                                        <tr>
                                            <td>{{ $dispositivo->id }}</td>
                                            <td>{{ optional($dispositivo->fecha)->format('d-m-Y') }}</td>
                                            <td>{{ $dispositivo->hora ?? 'Sin hora' }}</td>
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
                                            <td style="white-space: nowrap; text-align:center;">
                                                <a href="{{ route('guardianes_camino.dispositivos.show', $dispositivo->id) }}" class="btn btn-info btn-sm" title="Ver">
                                                    <i class="fa-regular fa-eye"></i>
                                                </a>

                                                <form action="{{ route('guardianes_camino.dispositivos.aprobar_revision', $dispositivo->id) }}" method="POST" style="display:inline-block;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success btn-sm" title="Aprobar">
                                                        <i class="fa-solid fa-check"></i>
                                                    </button>
                                                </form>

                                                <button
                                                    type="button"
                                                    class="btn btn-danger btn-sm btn-rechazar"
                                                    data-id="{{ $dispositivo->id }}"
                                                    data-url="{{ route('guardianes_camino.dispositivos.rechazar_revision', $dispositivo->id) }}"
                                                    title="Rechazar"
                                                >
                                                    <i class="fa-solid fa-xmark"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            {{ $dispositivos->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <form id="formRechazarRevision" method="POST" style="display:none;">
        @csrf
        <input type="hidden" name="observacion_revision" id="observacion_revision_hidden">
    </form>
@stop

@section('js')
<script>
    $(function () {
        $('#fecha').on('change', function () {
            $('#formFiltroFecha').submit();
        });
    });

    $(document).on('click', '.btn-rechazar', function () {
        let url = $(this).data('url');

        Swal.fire({
            title: 'Rechazar dispositivo',
            input: 'textarea',
            inputLabel: 'Observación de revisión',
            inputPlaceholder: 'Escribe el motivo del rechazo...',
            inputAttributes: {
                'aria-label': 'Escribe el motivo del rechazo'
            },
            showCancelButton: true,
            confirmButtonText: 'Rechazar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#d33',
            preConfirm: (observacion) => {
                if (!observacion || !observacion.trim()) {
                    Swal.showValidationMessage('Debes escribir una observación.');
                }
                return observacion;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $('#formRechazarRevision').attr('action', url);
                $('#observacion_revision_hidden').val(result.value);
                $('#formRechazarRevision').submit();
            }
        });
    });

    @if (session('success'))
        Swal.fire({
            position: 'center',
            icon: 'success',
            title: '{{ session('success') }}',
            showConfirmButton: false,
            timer: 2500
        });
    @endif

    @if (session('error'))
        Swal.fire({
            position: 'center',
            icon: 'error',
            title: '{{ session('error') }}',
            showConfirmButton: true
        });
    @endif
</script>
@stop
