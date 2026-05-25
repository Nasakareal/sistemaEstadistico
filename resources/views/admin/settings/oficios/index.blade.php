@extends('adminlte::page')

@section('title', 'Oficios')

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h1 class="mb-1">Oficios</h1>
            <p class="text-muted mb-0">Amparos, memorándums, oficios y circulares por unidad.</p>
        </div>
        <div class="btn-group">
            @can('crear oficios')
                <a href="{{ route('oficios.create') }}" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i> Nuevo
                </a>
            @endcan
        </div>
    </div>
@stop

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fa-solid fa-envelopes-bulk"></i> Bandeja documental
            </h3>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('oficios.index') }}" class="mb-3">
                <div class="row">
                    <div class="col-lg-4 col-md-6">
                        <div class="form-group">
                            <label for="buscar">Buscar</label>
                            <input type="text"
                                   name="buscar"
                                   id="buscar"
                                   class="form-control"
                                   value="{{ $filtros['buscar'] ?? '' }}"
                                   placeholder="Número, asunto, remitente o destinatario">
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <div class="form-group">
                            <label for="tipo">Tipo</label>
                            <select name="tipo" id="tipo" class="form-control">
                                <option value="">Todos</option>
                                @foreach($tipos as $value => $label)
                                    <option value="{{ $value }}" {{ ($filtros['tipo'] ?? '') === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <div class="form-group">
                            <label for="sentido">Movimiento</label>
                            <select name="sentido" id="sentido" class="form-control">
                                <option value="">Todos</option>
                                @foreach($sentidos as $value => $label)
                                    <option value="{{ $value }}" {{ ($filtros['sentido'] ?? '') === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if($puedeFiltrarUnidad)
                        <div class="col-lg-3 col-md-6">
                            <div class="form-group">
                                <label for="unidad_id">Unidad</label>
                                <select name="unidad_id" id="unidad_id" class="form-control">
                                    <option value="">Todas las unidades</option>
                                    @foreach($unidades as $unidad)
                                        <option value="{{ $unidad->id }}" {{ (string)($filtros['unidad_id'] ?? '') === (string)$unidad->id ? 'selected' : '' }}>
                                            {{ $unidad->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif

                    <div class="col-lg-1 col-md-12 d-flex align-items-end">
                        <div class="form-group w-100">
                            <button type="submit" class="btn btn-info btn-block">
                                <i class="fa-solid fa-filter"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <table id="oficios-table" class="table table-striped table-bordered table-hover table-sm">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Tipo</th>
                        <th>Movimiento</th>
                        <th>Fecha</th>
                        <th>Término</th>
                        @if($puedeFiltrarUnidad)
                            <th>Unidad</th>
                        @endif
                        <th>Creó</th>
                        <th>Asunto</th>
                        <th>Ruta</th>
                        <th>Respuesta</th>
                        <th>Archivo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($oficios as $oficio)
                        @php($pendienteContestacion = $oficio->pendiente_contestacion)
                        <tr class="{{ $pendienteContestacion ? 'oficio-row--pendiente-contestacion' : '' }}">
                            <td class="oficio-numero" title="{{ $oficio->numero_oficio }}">
                                {{ $oficio->numero_corto }}
                            </td>
                            <td>
                                <span class="badge badge-light">{{ $oficio->tipo_label }}</span>
                            </td>
                            <td>
                                <span class="oficio-badge oficio-badge--{{ $oficio->sentido }}">
                                    <i class="fa-solid {{ $oficio->sentido === 'entrada' ? 'fa-arrow-down' : 'fa-arrow-up' }}"></i>
                                    {{ $oficio->sentido_label }}
                                </span>
                            </td>
                            <td>{{ optional($oficio->fecha_documento)->format('d-m-Y') ?? optional($oficio->created_at)->format('d-m-Y') }}</td>
                            <td>
                                @if($oficio->termino_label)
                                    <span class="badge {{ $pendienteContestacion ? 'badge-danger' : 'badge-warning' }}">
                                        {{ $oficio->termino_label }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            @if($puedeFiltrarUnidad)
                                <td>{{ $oficio->unidad->nombre ?? 'Sin unidad' }}</td>
                            @endif
                            <td>{{ $oficio->creador->name ?? '—' }}</td>
                            <td>{{ $oficio->asunto ?? '—' }}</td>
                            <td>
                                <div><strong>De:</strong> {{ $oficio->remitente ?? '—' }}</div>
                                <div><strong>Para:</strong> {{ $oficio->destinatario ?? '—' }}</div>
                            </td>
                            <td>
                                @if($oficio->contestaA)
                                    <a href="{{ route('oficios.show', $oficio->contestaA) }}" class="badge badge-info">
                                        Contesta a {{ $oficio->contestaA->numero_corto }}
                                    </a>
                                @else
                                    <span class="badge badge-secondary">Original</span>
                                @endif

                                @if($oficio->contestaciones_count > 0)
                                    <a href="{{ route('oficios.show', $oficio) }}#contestaciones" class="badge badge-success">
                                        {{ $oficio->contestaciones_count }} contest.
                                    </a>
                                @elseif($pendienteContestacion)
                                    <span class="badge badge-danger">Falta contestar</span>
                                @endif
                            </td>
                            <td>
                                @if($oficio->pdf_path)
                                    <a href="{{ asset('storage/' . $oficio->pdf_path) }}" target="_blank" class="btn btn-warning btn-sm">
                                        <i class="fa-regular fa-file-pdf"></i>
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('oficios.show', $oficio) }}" class="btn btn-info btn-sm" title="Ver">
                                        <i class="fa-regular fa-eye"></i>
                                    </a>

                                    @can('editar oficios')
                                        <a href="{{ route('oficios.edit', $oficio) }}" class="btn btn-success btn-sm" title="Editar">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </a>
                                    @endcan

                                    @can('crear oficios')
                                        <a href="{{ route('oficios.create', ['contesta_a_id' => $oficio->id]) }}" class="btn btn-primary btn-sm" title="Contestar">
                                            <i class="fa-solid fa-reply"></i>
                                        </a>
                                    @endcan

                                    @can('eliminar oficios')
                                        <form action="{{ route('oficios.destroy', $oficio) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-danger btn-sm delete-btn" title="Eliminar">
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
        </div>
    </div>
@stop

@section('css')
    @include('admin.settings.oficios._styles')
@stop

@section('js')
    @include('admin.settings.oficios._alerts')
    <script>
        $(function () {
            $('#oficios-table').DataTable({
                pageLength: 10,
                responsive: true,
                lengthChange: true,
                autoWidth: false,
                order: [],
                language: {
                    emptyTable: "No hay oficios registrados",
                    info: "Mostrando _START_ a _END_ de _TOTAL_ oficios",
                    infoEmpty: "Mostrando 0 a 0 de 0 oficios",
                    infoFiltered: "(filtrado de _MAX_ oficios)",
                    lengthMenu: "Mostrar _MENU_ oficios",
                    loadingRecords: "Cargando...",
                    processing: "Procesando...",
                    search: "Buscar en tabla:",
                    zeroRecords: "Sin resultados",
                    paginate: {
                        first: "Primero",
                        last: "Último",
                        next: "Siguiente",
                        previous: "Anterior"
                    }
                },
                buttons: [
                    {
                        extend: 'collection',
                        text: 'Opciones',
                        buttons: [
                            { extend: 'copy', text: 'Copiar' },
                            { extend: 'excel', text: 'Excel' },
                            { extend: 'pdf', text: 'PDF' },
                            { extend: 'print', text: 'Imprimir' }
                        ]
                    },
                    { extend: 'colvis', text: 'Columnas' }
                ],
            }).buttons().container().appendTo('#oficios-table_wrapper .col-md-6:eq(0)');
        });

        $(document).on('click', '.delete-btn', function (event) {
            event.preventDefault();

            const form = $(this).closest('form');

            Swal.fire({
                title: '¿Eliminar oficio?',
                text: 'La relación con sus contestaciones quedará sin documento origen.',
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
