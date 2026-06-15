@extends('adminlte::page')

@section('title', 'Detalle de Delegación')

@section('content_header')
    <h1>Detalle de Delegación</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">

            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        {{ $delegacion->nombre }}
                        @if (!empty($delegacion->clave))
                            <span class="text-muted">({{ $delegacion->clave }})</span>
                        @endif
                    </h3>

                    <div class="card-tools">
                        <a href="{{ route('delegaciones.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </a>

                        @can('editar delegaciones')
                            <a href="{{ route('delegaciones.edit', $delegacion) }}" class="btn btn-success btn-sm">
                                <i class="fa-regular fa-pen-to-square"></i> Editar
                            </a>
                        @endcan

                        @can('eliminar delegaciones')
                            @if ($delegacion->activa)
                            <form action="{{ route('delegaciones.destroy', $delegacion) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-warning btn-sm deactivate-btn">
                                    <i class="fa-solid fa-ban"></i> Desactivar
                                </button>
                            </form>
                            @endif
                        @endcan
                    </div>
                </div>

                <div class="card-body">

                    <div class="row">
                        <div class="col-md-3">
                            <div class="small text-muted">Clave</div>
                            <div class="font-weight-bold">{{ $delegacion->clave ?: '—' }}</div>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted">Nombre</div>
                            <div class="font-weight-bold">{{ $delegacion->nombre }}</div>
                        </div>

                        <div class="col-md-3">
                            <div class="small text-muted">Municipio</div>
                            <div class="font-weight-bold">{{ $delegacion->municipio ?: '—' }}</div>
                        </div>

                        <div class="col-md-2">
                            <div class="small text-muted">Estado</div>
                            <div class="font-weight-bold">
                                @if ($delegacion->activa)
                                    <span class="badge badge-success">ACTIVA</span>
                                @else
                                    <span class="badge badge-secondary">INACTIVA</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-3 mt-3">
                            <div class="small text-muted">Latitud</div>
                            <div class="font-weight-bold">{{ $delegacion->lat ?: '—' }}</div>
                        </div>

                        <div class="col-md-3 mt-3">
                            <div class="small text-muted">Longitud</div>
                            <div class="font-weight-bold">{{ $delegacion->lng ?: '—' }}</div>
                        </div>

                        <div class="col-md-3 mt-3">
                            <div class="small text-muted">Delegación padre</div>
                            <div class="font-weight-bold">
                                {{ $delegacion->padre ? ($delegacion->padre->nombre_con_clave ?? $delegacion->padre->nombre) : '—' }}
                            </div>
                        </div>

                        <div class="col-md-3 mt-3">
                            <div class="small text-muted">Fecha de creación</div>
                            <div class="font-weight-bold">
                                {{ $delegacion->created_at ? $delegacion->created_at->format('d-m-Y H:i') : '—' }}
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <h5>Ubicación de la delegación</h5>

                        @if ($delegacion->lat && $delegacion->lng)
                            <div id="mapa_delegacion"></div>
                        @else
                            <div class="alert alert-warning mb-0">
                                Esta delegación aún no tiene coordenadas registradas.
                            </div>
                        @endif
                    </div>

                    <hr>

                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h5 class="mb-0">Delegaciones hijas</h5>

                        @can('editar delegaciones')
                            <a href="{{ route('delegaciones.edit', $delegacion) }}" class="btn btn-outline-primary btn-sm">
                                <i class="fa-regular fa-pen-to-square"></i> Gestionar hijas
                            </a>
                        @endcan
                    </div>

                    @if ($delegacion->hijas->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover table-sm" id="hijas">
                                <thead>
                                    <tr>
                                        <th>Número</th>
                                        <th>Clave</th>
                                        <th>Nombre</th>
                                        <th>Municipio</th>
                                        <th>Latitud</th>
                                        <th>Longitud</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($delegacion->hijas as $i => $hija)
                                        <tr>
                                            <td>{{ $i + 1 }}</td>
                                            <td>{{ $hija->clave ?: '—' }}</td>
                                            <td>{{ $hija->nombre }}</td>
                                            <td>{{ $hija->municipio ?: '—' }}</td>
                                            <td>{{ $hija->lat ?: '—' }}</td>
                                            <td>{{ $hija->lng ?: '—' }}</td>
                                            <td>
                                                @if ($hija->activa)
                                                    <span class="badge badge-success">ACTIVA</span>
                                                @else
                                                    <span class="badge badge-secondary">INACTIVA</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    @can('ver delegaciones')
                                                        <a href="{{ route('delegaciones.show', $hija) }}" class="btn btn-info btn-sm">
                                                            <i class="fa-regular fa-eye"></i>
                                                        </a>
                                                    @endcan

                                                    @can('editar delegaciones')
                                                        <a href="{{ route('delegaciones.edit', $hija) }}" class="btn btn-success btn-sm">
                                                            <i class="fa-regular fa-pen-to-square"></i>
                                                        </a>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info mb-0">
                            No hay delegaciones hijas activas.
                        </div>
                    @endif

                </div>
            </div>

        </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

    <style>
        .table th,
        .table td {
            text-align: center;
            vertical-align: middle;
        }

        #mapa_delegacion {
            width: 100%;
            height: 420px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
    </style>
@stop

@section('js')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        $(function () {
            if ($('#hijas').length) {
                $('#hijas').DataTable({
                    pageLength: 10,
                    language: {
                        emptyTable: "No hay información",
                        info: "Mostrando _START_ a _END_ de _TOTAL_ Hijas",
                        infoEmpty: "Mostrando 0 a 0 de 0 Hijas",
                        infoFiltered: "(Filtrado de _MAX_ total Hijas)",
                        lengthMenu: "Mostrar _MENU_ Hijas",
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
                    autoWidth: false
                });
            }
        });

        @if ($delegacion->lat && $delegacion->lng)
            const latDelegacion = {{ $delegacion->lat }};
            const lngDelegacion = {{ $delegacion->lng }};
            const mapaDelegacion = L.map('mapa_delegacion').setView([latDelegacion, lngDelegacion], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(mapaDelegacion);

            L.marker([latDelegacion, lngDelegacion])
                .addTo(mapaDelegacion)
                .bindPopup(`{{ $delegacion->nombre }}<br>{{ $delegacion->municipio ?: '' }}`)
                .openPopup();
        @endif

        @if (session('success'))
            Swal.fire({
                position: 'center',
                icon: 'success',
                title: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 15000
            });
        @endif

        $(document).on('click', '.deactivate-btn', function (e) {
            e.preventDefault();

            let form = $(this).closest('form');

            Swal.fire({
                title: '¿Desactivar esta delegación?',
                text: 'No se borrarán registros históricos; dejará de salir en listados, feed, usuarios y mapa.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f0ad4e',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, desactivar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    </script>
@stop
