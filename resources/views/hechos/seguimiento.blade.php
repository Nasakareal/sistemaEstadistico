@extends('adminlte::page')

@section('title', 'Seguimiento de Hechos')

@section('content_header')
    <h1>Seguimiento de Hechos de Tránsito</h1>
@stop

@section('content')
    @php
        $periodoActual = strtoupper($periodo ?? 'SEMANA');
        $situacionActual = strtoupper($situacion ?? 'PENDIENTE');

        $labelsPeriodo = [
            'SEMANA' => 'Semana',
            'MES' => 'Mes',
            'ANIO' => 'Año',
        ];

        $labelsSituacion = [
            'PENDIENTE' => 'Pendientes',
            'TURNADO' => 'Turnados',
            'RESUELTO' => 'Resueltos',
        ];

        $clasesSituacion = [
            'PENDIENTE' => 'warning',
            'TURNADO' => 'info',
            'RESUELTO' => 'success',
        ];
    @endphp

    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Resumen General</h3>
                    <div class="card-tools">
                        <a href="{{ route('hechos.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-list"></i> Ir al listado de accidentes
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" action="{{ route('hechos.seguimiento') }}" class="row mb-4" autocomplete="off">
                        <div class="col-md-4">
                            <label for="periodo">Periodo:</label>
                            <select name="periodo" id="periodo" class="form-control">
                                <option value="SEMANA" {{ $periodoActual === 'SEMANA' ? 'selected' : '' }}>Semana</option>
                                <option value="MES" {{ $periodoActual === 'MES' ? 'selected' : '' }}>Mes</option>
                                <option value="ANIO" {{ $periodoActual === 'ANIO' ? 'selected' : '' }}>Año</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="situacion">Situación:</label>
                            <select name="situacion" id="situacion" class="form-control">
                                <option value="PENDIENTE" {{ $situacionActual === 'PENDIENTE' ? 'selected' : '' }}>Pendientes</option>
                                <option value="TURNADO" {{ $situacionActual === 'TURNADO' ? 'selected' : '' }}>Turnados</option>
                                <option value="RESUELTO" {{ $situacionActual === 'RESUELTO' ? 'selected' : '' }}>Resueltos</option>
                            </select>
                        </div>

                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary mr-2">
                                <i class="fa-solid fa-filter"></i> Filtrar
                            </button>

                            <a href="{{ route('hechos.seguimiento') }}" class="btn btn-secondary">
                                <i class="fa-solid fa-rotate-left"></i> Restablecer
                            </a>
                        </div>
                    </form>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>{{ $conteos['semana']['PENDIENTE'] ?? 0 }}</h3>
                                    <p>Pendientes de la semana</p>
                                </div>
                                <div class="icon">
                                    <i class="fa-solid fa-calendar-week"></i>
                                </div>
                                <a href="{{ route('hechos.seguimiento', ['periodo' => 'SEMANA', 'situacion' => 'PENDIENTE']) }}" class="small-box-footer">
                                    Ver pendientes <i class="fas fa-arrow-circle-right"></i>
                                </a>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>{{ $conteos['mes']['PENDIENTE'] ?? 0 }}</h3>
                                    <p>Pendientes del mes</p>
                                </div>
                                <div class="icon">
                                    <i class="fa-solid fa-calendar-days"></i>
                                </div>
                                <a href="{{ route('hechos.seguimiento', ['periodo' => 'MES', 'situacion' => 'PENDIENTE']) }}" class="small-box-footer">
                                    Ver pendientes <i class="fas fa-arrow-circle-right"></i>
                                </a>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>{{ $conteos['anio']['PENDIENTE'] ?? 0 }}</h3>
                                    <p>Pendientes del año</p>
                                </div>
                                <div class="icon">
                                    <i class="fa-solid fa-calendar"></i>
                                </div>
                                <a href="{{ route('hechos.seguimiento', ['periodo' => 'ANIO', 'situacion' => 'PENDIENTE']) }}" class="small-box-footer">
                                    Ver pendientes <i class="fas fa-arrow-circle-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Pendientes</span>
                                    <span class="info-box-number">
                                        {{ ($conteos[strtolower($periodoActual)]['PENDIENTE'] ?? 0) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-info">
                                    <i class="fa-solid fa-share"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Turnados</span>
                                    <span class="info-box-number">
                                        {{ ($conteos[strtolower($periodoActual)]['TURNADO'] ?? 0) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-success">
                                    <i class="fa-solid fa-circle-check"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Resueltos</span>
                                    <span class="info-box-number">
                                        {{ ($conteos[strtolower($periodoActual)]['RESUELTO'] ?? 0) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-light border mb-3">
                        <strong>Mostrando:</strong>
                        {{ $labelsSituacion[$situacionActual] ?? $situacionActual }}
                        del periodo
                        {{ $labelsPeriodo[$periodoActual] ?? $periodoActual }}
                    </div>

                    @if ($hechos->count() === 0)
                        <div class="alert alert-info">
                            No hay hechos en esta situación para el periodo seleccionado.
                        </div>
                    @endif

                    <table id="seguimiento_hechos" class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th><center>ID</center></th>
                                <th><center>Fecha y Hora</center></th>
                                <th><center>Ubicación</center></th>
                                <th><center>Foto Lugar</center></th>
                                <th><center>Situación</center></th>
                                <th><center>Creado por</center></th>
                                <th><center>Acciones</center></th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($hechos as $hecho)
                                <tr>
                                    <td>{{ $hecho->id }}</td>
                                    <td>{{ $hecho->fecha }} {{ $hecho->hora }}</td>
                                    <td>{{ $hecho->calle }}, {{ $hecho->colonia }}, {{ $hecho->municipio }}</td>

                                    <td>
                                        @php
                                            $foto = $hecho->foto_lugar;
                                            $urlFoto = $foto ? asset('storage/' . ltrim($foto, '/')) : null;
                                        @endphp

                                        @if ($urlFoto)
                                            <a href="{{ $urlFoto }}" target="_blank" rel="noopener">
                                                <img src="{{ $urlFoto }}" alt="foto_lugar" class="foto-thumb">
                                            </a>
                                        @else
                                            <span class="text-muted">Sin foto</span>
                                        @endif
                                    </td>

                                    <td>
                                        <span class="badge badge-{{ $clasesSituacion[$hecho->situacion] ?? 'secondary' }}">
                                            {{ $hecho->situacion }}
                                        </span>

                                        @if ($hecho->mostrar_captura && $hecho->estado_captura === 'INCOMPLETO')
                                            <br>
                                            <span class="badge badge-danger">CAPTURA INCOMPLETA</span>
                                        @endif
                                    </td>

                                    <td>{{ $hecho->creator ? $hecho->creator->name : 'Desconocido' }}</td>

                                    <td style="text-align: center">
                                        <a href="{{ route('hechos.show', $hecho->id) }}" class="btn btn-info btn-sm" title="Ver">
                                            <i class="fa-regular fa-eye"></i>
                                        </a>

                                        <a href="{{ route('hechos.edit', $hecho->id) }}" class="btn btn-success btn-sm" title="Editar">
                                            <i class="fa-solid fa-pencil"></i>
                                        </a>

                                        <a href="{{ route('hechos.descargar', $hecho->id) }}" class="btn btn-warning btn-sm" title="Descargar">
                                            <i class="fas fa-download"></i>
                                        </a>

                                        <button
                                            type="button"
                                            class="btn btn-success btn-sm btn-whatsapp"
                                            data-id="{{ $hecho->id }}"
                                            title="Compartir"
                                        >
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </button>

                                        <form action="{{ route('hechos.destroy', $hecho->id) }}" method="POST" style="display:inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="button"
                                                class="btn btn-danger btn-sm delete-btn"
                                                title="Eliminar"
                                            >
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-3">
                        {{ $hechos->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .table th, .table td {
            text-align: center;
            vertical-align: middle;
        }

        #seguimiento_hechos.table-hover tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.08) !important;
        }

        #seguimiento_hechos.table-hover tbody tr:hover td,
        #seguimiento_hechos.table-hover tbody tr:hover th {
            color: #ffffff !important;
        }

        #seguimiento_hechos.table-hover tbody tr:hover a {
            color: #ffffff !important;
        }

        select.form-control {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.06) !important;
            border-color: rgba(255, 255, 255, 0.15) !important;
        }

        select.form-control:focus {
            background-color: rgba(255, 255, 255, 0.10) !important;
            border-color: rgba(255, 255, 255, 0.30) !important;
            box-shadow: 0 0 0 .2rem rgba(255, 255, 255, 0.10) !important;
        }

        select.form-control option {
            color: #000000;
            background-color: #ffffff;
        }

        .foto-thumb {
            width: 72px;
            height: 52px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid rgba(0,0,0,.12);
            background: #f8f9fa;
        }

        .small-box .icon i {
            font-size: 52px;
        }

        .badge {
            font-size: .85rem;
            padding: .45em .7em;
        }
    </style>
@stop

@section('js')
    <script>
        $(function () {
            $('#seguimiento_hechos').DataTable({
                paging: false,
                info: false,
                order: [[1, "desc"]],
                language: {
                    emptyTable: "No hay información disponible",
                    info: "Mostrando _START_ a _END_ de _TOTAL_ hechos",
                    infoEmpty: "Mostrando 0 a 0 de 0 hechos",
                    infoFiltered: "(Filtrado de _MAX_ total hechos)",
                    lengthMenu: "Mostrar _MENU_ hechos",
                    loadingRecords: "Cargando...",
                    processing: "Procesando...",
                    search: "Buscador:",
                    zeroRecords: "No se encontraron resultados",
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
                            { extend: 'copy', text: 'Copiar' },
                            { extend: 'pdf', text: 'PDF' },
                            { extend: 'csv', text: 'CSV' },
                            { extend: 'excel', text: 'Excel' },
                            { extend: 'print', text: 'Imprimir' }
                        ]
                    },
                    { extend: 'colvis', text: 'Visor de columnas' }
                ],
            }).buttons().container().appendTo('#seguimiento_hechos_wrapper .col-md-6:eq(0)');
        });

        $(document).on('click', '.btn-whatsapp', function () {
            let id = $(this).data('id');

            fetch(`/hechos/${id}/compartir-nativo`)
                .then(res => res.json())
                .then(data => {
                    if (!data.texto) {
                        Swal.fire('Error', 'No se pudo generar el mensaje', 'error');
                        return;
                    }

                    let url = `https://wa.me/?text=${encodeURIComponent(data.texto)}`;
                    window.open(url, '_blank');
                })
                .catch(() => {
                    Swal.fire('Error', 'No se pudo compartir', 'error');
                });
        });

        $(document).on('click', '.delete-btn', function (e) {
            e.preventDefault();

            let form = $(this).closest('form');

            Swal.fire({
                title: '¿Estás seguro de eliminar este hecho?',
                text: "¡No podrás revertir esta acción!",
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

        @if (session('success'))
            Swal.fire({
                position: 'center',
                icon: 'success',
                title: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 3000
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

        @if (session('info'))
            Swal.fire({
                position: 'center',
                icon: 'info',
                title: '{{ session('info') }}',
                showConfirmButton: false,
                timer: 2500
            });
        @endif
    </script>
@stop
