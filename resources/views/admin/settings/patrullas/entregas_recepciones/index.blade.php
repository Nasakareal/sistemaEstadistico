@extends('adminlte::page')

@section('title', 'Entrega y Recepción de Patrulla')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1>Entrega y Recepción de Patrulla</h1>
            <small class="text-muted">
                Unidad {{ $patrulla->numero_economico }}
                @if($patrulla->marca || $patrulla->linea || $patrulla->modelo)
                    — {{ trim(($patrulla->marca ?? '') . ' ' . ($patrulla->linea ?? '') . ' ' . ($patrulla->modelo ?? '')) }}
                @endif
            </small>
        </div>

        <a href="{{ route('patrullas.show', $patrulla->id) }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Volver a la Patrulla
        </a>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">

            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa-solid fa-right-left mr-1"></i>
                        Historial de Entregas y Recepciones
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
                                    <span class="info-box-text">Número Económico</span>
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
                                    <span class="info-box-text">Unidad</span>
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
                                    <span class="info-box-text">Placas</span>
                                    <span class="info-box-number">
                                        {{ $patrulla->placas ?? '—' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="info-box mb-0">
                                <span class="info-box-icon bg-success">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                </span>

                                <div class="info-box-content">
                                    <span class="info-box-text">Registros</span>
                                    <span class="info-box-number">
                                        {{ $entregasRecepciones->count() }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <table id="entregasRecepciones"
                           class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Entrega</th>
                                <th>Recibe</th>
                                <th>Kilometraje</th>
                                <th>Combustible</th>
                                <th>Estado</th>
                                <th>Novedades</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($entregasRecepciones as $index => $registro)
                                <tr>
                                    <td>
                                        {{ $index + 1 }}
                                    </td>

                                    <td data-order="{{ optional($registro->fecha)->format('Y-m-d') }}">
                                        {{ optional($registro->fecha)->format('d-m-Y') ?? '—' }}
                                    </td>

                                    <td>
                                        @if($registro->hora_real)
                                            {{ substr($registro->hora_real, 0, 5) }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if($registro->entrega_nombre)
                                            <strong>{{ $registro->entrega_nombre }}</strong>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif

                                        @if($registro->aceptada_entrega)
                                            <br>
                                            <span class="badge badge-success">
                                                <i class="fa-solid fa-check"></i> Confirmada
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        @if($registro->recibe_nombre)
                                            <strong>{{ $registro->recibe_nombre }}</strong>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif

                                        @if($registro->aceptada_recepcion)
                                            <br>
                                            <span class="badge badge-success">
                                                <i class="fa-solid fa-check"></i> Confirmada
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        @if(!is_null($registro->kilometraje))
                                            {{ number_format($registro->kilometraje) }} km
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if(!is_null($registro->nivel_combustible))
                                            {{ number_format((float) $registro->nivel_combustible, 0) }}%
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if($registro->aceptada_entrega && $registro->aceptada_recepcion)
                                            <span class="badge badge-success">
                                                Completa
                                            </span>
                                        @elseif($registro->aceptada_entrega || $registro->aceptada_recepcion)
                                            <span class="badge badge-warning">
                                                Pendiente
                                            </span>
                                        @else
                                            <span class="badge badge-secondary">
                                                Sin confirmar
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        @if($registro->tiene_novedades)
                                            <span class="badge badge-warning">
                                                <i class="fa-solid fa-triangle-exclamation"></i>
                                                Sí
                                            </span>
                                        @else
                                            <span class="badge badge-success">
                                                Sin novedad
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('patrullas.entregas_recepciones.show', [
                                                'patrulla' => $patrulla->id,
                                                'entregaRecepcionPatrulla' => $registro->id
                                            ]) }}"
                                               class="btn btn-info btn-sm"
                                               title="Ver">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>

                                            @can('editar patrullas')
                                                <a href="{{ route('patrullas.entregas_recepciones.edit', [
                                                    'patrulla' => $patrulla->id,
                                                    'entregaRecepcionPatrulla' => $registro->id
                                                ]) }}"
                                                   class="btn btn-success btn-sm"
                                                   title="Editar">
                                                    <i class="fa-regular fa-pen-to-square"></i>
                                                </a>
                                            @endcan

                                            @can('eliminar patrullas')
                                                <form action="{{ route('patrullas.entregas_recepciones.destroy', [
                                                    'patrulla' => $patrulla->id,
                                                    'entregaRecepcionPatrulla' => $registro->id
                                                ]) }}"
                                                      method="POST"
                                                      style="display:inline-block;">
                                                    @csrf
                                                    @method('DELETE')

                                                    <button type="button"
                                                            class="btn btn-danger btn-sm delete-btn"
                                                            title="Eliminar">
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

                    @if($entregasRecepciones->isEmpty())
                        <div class="text-center text-muted py-4">
                            <i class="fa-solid fa-right-left fa-3x mb-3"></i>
                            <h5>Sin registros de entrega y recepción</h5>
                            <p class="mb-0">
                                Esta patrulla todavía no cuenta con movimientos registrados.
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

        #entregasRecepciones td {
            white-space: nowrap;
        }

        #entregasRecepciones td:nth-child(4),
        #entregasRecepciones td:nth-child(5) {
            white-space: normal;
            min-width: 180px;
        }
    </style>
@stop

@section('js')
    <script>
        $(function () {
            $('#entregasRecepciones').DataTable({
                pageLength: 10,
                order: [[1, 'desc'], [2, 'desc']],
                language: {
                    emptyTable: "No hay información",
                    info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    infoEmpty: "Mostrando 0 a 0 de 0 registros",
                    infoFiltered: "(Filtrado de _MAX_ registros)",
                    lengthMenu: "Mostrar _MENU_ registros",
                    loadingRecords: "Cargando...",
                    processing: "Procesando...",
                    search: "Buscador:",
                    zeroRecords: "Sin resultados encontrados",
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
                '#entregasRecepciones_wrapper .col-md-6:eq(0)'
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

        $(document).on('click', '.delete-btn', function (e) {
            e.preventDefault();

            const form = $(this).closest('form');

            Swal.fire({
                title: '¿Eliminar este registro?',
                text: 'Se eliminará el registro histórico de entrega y recepción.',
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
