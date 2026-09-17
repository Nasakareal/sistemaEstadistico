@extends('adminlte::page')

@section('title', 'Bitácoras de Servicio')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1>Bitácoras de Servicio</h1>
            <small class="text-muted">
                Patrulla {{ $patrulla->numero_economico }}
                @if($patrulla->descripcion_vehiculo)
                    — {{ $patrulla->descripcion_vehiculo }}
                @endif
            </small>
        </div>

        <a href="{{ route('patrullas.show', $patrulla->id) }}"
           class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i>
            Volver a la Patrulla
        </a>
    </div>
@stop


@section('content')

    <div class="row">
        <div class="col-md-12">

            <div class="card card-outline card-primary">

                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa-solid fa-clipboard-list mr-1"></i>
                        Historial de Bitácoras
                    </h3>
                </div>

                <div class="card-body">

                    <div class="row mb-4">

                        <div class="col-md-3">
                            <div class="info-box mb-0">
                                <span class="info-box-icon bg-primary">
                                    <i class="fa-solid fa-car-side"></i>
                                </span>

                                <div class="info-box-content">
                                    <span class="info-box-text">
                                        Número Económico
                                    </span>

                                    <span class="info-box-number">
                                        {{ $patrulla->numero_economico }}
                                    </span>
                                </div>
                            </div>
                        </div>


                        <div class="col-md-3">
                            <div class="info-box mb-0">
                                <span class="info-box-icon bg-info">
                                    <i class="fa-solid fa-building-shield"></i>
                                </span>

                                <div class="info-box-content">
                                    <span class="info-box-text">
                                        Unidad
                                    </span>

                                    <span class="info-box-number">
                                        {{ $patrulla->unidad->nombre ?? '—' }}
                                    </span>
                                </div>
                            </div>
                        </div>


                        <div class="col-md-3">
                            <div class="info-box mb-0">
                                <span class="info-box-icon bg-secondary">
                                    <i class="fa-solid fa-id-card"></i>
                                </span>

                                <div class="info-box-content">
                                    <span class="info-box-text">
                                        Placas
                                    </span>

                                    <span class="info-box-number">
                                        {{ $patrulla->placas ?? '—' }}
                                    </span>
                                </div>
                            </div>
                        </div>


                        <div class="col-md-3">
                            <div class="info-box mb-0">
                                <span class="info-box-icon bg-success">
                                    <i class="fa-solid fa-clipboard-check"></i>
                                </span>

                                <div class="info-box-content">
                                    <span class="info-box-text">
                                        Bitácoras
                                    </span>

                                    <span class="info-box-number">
                                        {{ $bitacoras->count() }}
                                    </span>
                                </div>
                            </div>
                        </div>

                    </div>


                    <table id="bitacoras"
                           class="table table-striped table-bordered table-hover table-sm">

                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Fecha</th>
                                <th>Turno</th>
                                <th>Responsable</th>
                                <th>Hora Inicio</th>
                                <th>Hora Fin</th>
                                <th>Km Inicial</th>
                                <th>Km Final</th>
                                <th>Recorrido</th>
                                <th>Combustible</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach($bitacoras as $index => $bitacora)

                                <tr>

                                    <td>
                                        {{ $index + 1 }}
                                    </td>


                                    <td data-order="{{ optional($bitacora->fecha)->format('Y-m-d') }}">
                                        {{ optional($bitacora->fecha)->format('d-m-Y') ?? '—' }}
                                    </td>


                                    <td>
                                        {{ $bitacora->turno->nombre ?? '—' }}
                                    </td>


                                    <td>

                                        @if($bitacora->capturado_por_nombre)

                                            <strong>
                                                {{ $bitacora->capturado_por_nombre }}
                                            </strong>

                                            @if($bitacora->capturadoPor)
                                                <br>
                                                <small class="text-muted">
                                                    {{ $bitacora->capturadoPor->email }}
                                                </small>
                                            @endif

                                        @else

                                            <span class="text-muted">
                                                —
                                            </span>

                                        @endif

                                    </td>


                                    <td>

                                        @if($bitacora->hora_inicio)

                                            {{ substr($bitacora->hora_inicio, 0, 5) }}

                                        @else

                                            <span class="text-muted">
                                                —
                                            </span>

                                        @endif

                                    </td>


                                    <td>

                                        @if($bitacora->hora_fin)

                                            {{ substr($bitacora->hora_fin, 0, 5) }}

                                        @elseif($bitacora->estatus === 'abierta')

                                            <span class="badge badge-info">
                                                En servicio
                                            </span>

                                        @else

                                            <span class="text-muted">
                                                —
                                            </span>

                                        @endif

                                    </td>


                                    <td>

                                        @if(!is_null($bitacora->kilometraje_inicio))

                                            {{ number_format($bitacora->kilometraje_inicio) }}
                                            km

                                        @else

                                            <span class="text-muted">
                                                —
                                            </span>

                                        @endif

                                    </td>


                                    <td>

                                        @if(!is_null($bitacora->kilometraje_fin))

                                            {{ number_format($bitacora->kilometraje_fin) }}
                                            km

                                        @else

                                            <span class="text-muted">
                                                —
                                            </span>

                                        @endif

                                    </td>


                                    <td>

                                        @if(!is_null($bitacora->kilometros_recorridos))

                                            <strong>
                                                {{ number_format($bitacora->kilometros_recorridos) }}
                                                km
                                            </strong>

                                        @else

                                            <span class="text-muted">
                                                —
                                            </span>

                                        @endif

                                    </td>


                                    <td>

                                        @if(
                                            !is_null($bitacora->combustible_inicio)
                                            || !is_null($bitacora->combustible_fin)
                                        )

                                            <div>
                                                <small class="text-muted">
                                                    Inicio:
                                                </small>

                                                @if(!is_null($bitacora->combustible_inicio))
                                                    {{ number_format((float) $bitacora->combustible_inicio, 0) }}%
                                                @else
                                                    —
                                                @endif
                                            </div>

                                            <div>
                                                <small class="text-muted">
                                                    Fin:
                                                </small>

                                                @if(!is_null($bitacora->combustible_fin))
                                                    {{ number_format((float) $bitacora->combustible_fin, 0) }}%
                                                @else
                                                    —
                                                @endif
                                            </div>

                                        @else

                                            <span class="text-muted">
                                                —
                                            </span>

                                        @endif

                                    </td>


                                    <td>

                                        @if($bitacora->estatus === 'abierta')

                                            <span class="badge badge-success">
                                                <i class="fa-solid fa-circle-play"></i>
                                                Abierta
                                            </span>

                                        @elseif($bitacora->estatus === 'cerrada')

                                            <span class="badge badge-secondary">
                                                <i class="fa-solid fa-circle-check"></i>
                                                Cerrada
                                            </span>

                                        @else

                                            <span class="badge badge-warning">
                                                {{ ucfirst($bitacora->estatus ?? 'Sin estado') }}
                                            </span>

                                        @endif

                                    </td>


                                    <td>

                                        <div class="btn-group" role="group">

                                            <a
                                                href="{{ route('patrullas.bitacoras.show', [
                                                    'patrulla' => $patrulla->id,
                                                    'bitacoraServicioPatrulla' => $bitacora->id
                                                ]) }}"
                                                class="btn btn-info btn-sm"
                                                title="Ver bitácora"
                                            >
                                                <i class="fa-regular fa-eye"></i>
                                            </a>


                                            @can('editar patrullas')

                                                <a
                                                    href="{{ route('patrullas.bitacoras.edit', [
                                                        'patrulla' => $patrulla->id,
                                                        'bitacoraServicioPatrulla' => $bitacora->id
                                                    ]) }}"
                                                    class="btn btn-success btn-sm"
                                                    title="Editar bitácora"
                                                >
                                                    <i class="fa-regular fa-pen-to-square"></i>
                                                </a>

                                            @endcan


                                            @can('eliminar patrullas')

                                                <form
                                                    action="{{ route('patrullas.bitacoras.destroy', [
                                                        'patrulla' => $patrulla->id,
                                                        'bitacoraServicioPatrulla' => $bitacora->id
                                                    ]) }}"
                                                    method="POST"
                                                    style="display:inline-block;"
                                                >

                                                    @csrf
                                                    @method('DELETE')

                                                    <button
                                                        type="button"
                                                        class="btn btn-danger btn-sm delete-btn"
                                                        title="Eliminar bitácora"
                                                    >
                                                        <i class="fa-regular fa-trash-can"></i>
                                                    </button>

                                                </form>

                                            @endcan

                                        </div>

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>


                    @if($bitacoras->isEmpty())

                        <div class="text-center text-muted py-5">

                            <i class="fa-solid fa-clipboard-list fa-3x mb-3"></i>

                            <h5>
                                Sin bitácoras de servicio
                            </h5>

                            <p class="mb-0">
                                Esta patrulla todavía no cuenta con bitácoras registradas.
                            </p>

                        </div>

                    @endif

                </div>
            </div>

        </div>
    </div>

@stop


@section('css')

    <style>

        .table th,
        .table td {
            text-align: center;
            vertical-align: middle;
        }

        .info-box-number {
            font-size: 1rem;
        }

        #bitacoras td {
            white-space: nowrap;
        }

        #bitacoras td:nth-child(4) {
            white-space: normal;
            min-width: 190px;
        }

    </style>

@stop


@section('js')

    <script>

        $(function () {

            $('#bitacoras').DataTable({

                pageLength: 10,

                order: [
                    [1, 'desc'],
                    [4, 'desc']
                ],

                language: {

                    emptyTable: "No hay información",

                    info:
                        "Mostrando _START_ a _END_ de _TOTAL_ bitácoras",

                    infoEmpty:
                        "Mostrando 0 a 0 de 0 bitácoras",

                    infoFiltered:
                        "(Filtrado de _MAX_ bitácoras)",

                    lengthMenu:
                        "Mostrar _MENU_ bitácoras",

                    loadingRecords:
                        "Cargando...",

                    processing:
                        "Procesando...",

                    search:
                        "Buscador:",

                    zeroRecords:
                        "Sin resultados encontrados",

                    paginate: {
                        first: "Primero",
                        last: "Último",
                        next: "Siguiente",
                        previous: "Anterior"
                    }
                },

                responsive: true,

                lengthChange: true,

                autoWidth: false,

                buttons: [

                    {
                        extend: 'collection',
                        text: 'Opciones',

                        buttons: [

                            {
                                extend: 'copy',
                                text: 'Copiar'
                            },

                            {
                                extend: 'pdf',
                                text: 'PDF'
                            },

                            {
                                extend: 'csv',
                                text: 'CSV'
                            },

                            {
                                extend: 'excel',
                                text: 'Excel'
                            },

                            {
                                extend: 'print',
                                text: 'Imprimir'
                            }

                        ]
                    },

                    {
                        extend: 'colvis',
                        text: 'Visor de columnas'
                    }

                ]

            }).buttons().container().appendTo(
                '#bitacoras_wrapper .col-md-6:eq(0)'
            );

        });


        @if(session('success'))

            Swal.fire({
                position: 'center',
                icon: 'success',
                title: @json(session('success')),
                showConfirmButton: false,
                timer: 4000
            });

        @endif


        $(document).on(
            'click',
            '.delete-btn',
            function (e) {

                e.preventDefault();

                const form = $(this).closest('form');

                Swal.fire({

                    title:
                        '¿Eliminar esta bitácora?',

                    text:
                        'Se eliminará el registro histórico de servicio de esta patrulla.',

                    icon:
                        'warning',

                    showCancelButton:
                        true,

                    confirmButtonColor:
                        '#d33',

                    cancelButtonColor:
                        '#3085d6',

                    confirmButtonText:
                        'Sí, eliminar',

                    cancelButtonText:
                        'Cancelar'

                }).then((result) => {

                    if (result.isConfirmed) {
                        form.submit();
                    }

                });

            }
        );

    </script>

@stop
