@extends('adminlte::page')

@section('title', 'Directorio red de apoyo')

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h1 class="mb-1">Directorio red de apoyo</h1>
            <p class="text-muted mb-0">Contactos regionales de los tres niveles de gobierno.</p>
        </div>
        @can('crear directorio red apoyo')
            <a href="{{ route('directorio_red_apoyo.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Nuevo contacto
            </a>
        @endcan
    </div>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fa-solid fa-handshake-angle"></i> Contactos por región
            </h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('directorio_red_apoyo.index') }}" class="mb-3">
                <div class="row">
                    <div class="col-lg-3 col-md-6">
                        <div class="form-group">
                            <label for="q">Buscar</label>
                            <input type="text"
                                   name="q"
                                   id="q"
                                   class="form-control"
                                   value="{{ $filtros['q'] ?? '' }}"
                                   placeholder="Institución, encargado, teléfono">
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="form-group">
                            <label for="region_id">Región operativa</label>
                            <select name="region_id" id="region_id" class="form-control">
                                <option value="">Todas</option>
                                @foreach($regiones as $region)
                                    <option value="{{ $region->id }}" {{ (string)($filtros['region_id'] ?? '') === (string)$region->id ? 'selected' : '' }}>
                                        {{ $region->nombre }} ({{ $region->hijas_count }} hijas)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="form-group">
                            <label for="delegacion_id">Delegación estatal</label>
                            <select name="delegacion_id" id="delegacion_id" class="form-control">
                                <option value="">Todas</option>
                                @foreach($delegacionesAgrupadas as $region)
                                    <optgroup label="{{ $region->nombre }}">
                                        <option value="{{ $region->id }}" {{ (string)($filtros['delegacion_id'] ?? '') === (string)$region->id ? 'selected' : '' }}>
                                            Toda la región
                                        </option>
                                        @foreach($region->hijas as $hija)
                                            <option value="{{ $hija->id }}" {{ (string)($filtros['delegacion_id'] ?? '') === (string)$hija->id ? 'selected' : '' }}>
                                                {{ $hija->nombre }}{{ $hija->municipio ? ' - ' . $hija->municipio : '' }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <div class="form-group">
                            <label for="nivel_gobierno">Nivel</label>
                            <select name="nivel_gobierno" id="nivel_gobierno" class="form-control">
                                <option value="">Todos</option>
                                @foreach($nivelesGobierno as $value => $label)
                                    <option value="{{ $value }}" {{ ($filtros['nivel_gobierno'] ?? '') === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-1 col-md-12 d-flex align-items-end">
                        <div class="form-group w-100">
                            <button type="submit" class="btn btn-info btn-block">
                                <i class="fa-solid fa-filter"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table id="directorio-red-apoyo" class="table table-striped table-bordered table-hover table-sm">
                    <thead>
                        <tr>
                            <th>Región</th>
                            <th>Adscripción</th>
                            <th>Nivel</th>
                            <th>Tipo</th>
                            <th>Institución</th>
                            <th>Encargado</th>
                            <th>Teléfono</th>
                            <th>Activo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($redApoyos as $redApoyo)
                            @php
                                $delegacion = $redApoyo->delegacion;
                                $regional = $delegacion ? ($delegacion->padre ?: $delegacion) : null;
                                $telefono = preg_replace('/\D+/', '', (string) $redApoyo->telefono);
                            @endphp
                            <tr>
                                <td>{{ $redApoyo->region ?: optional($regional)->nombre ?: 'Sin región' }}</td>
                                <td>
                                    @if($delegacion)
                                        {{ $delegacion->nombre }}
                                    @elseif($redApoyo->destacamento)
                                        {{ $redApoyo->destacamento->nombre }}
                                    @else
                                        {{ $redApoyo->nivel_gobierno === 'Estatal' ? 'General estatal' : 'Sin delegación' }}
                                    @endif
                                    @if($delegacion && $delegacion->delegacion_padre_id)
                                        <div class="small text-muted">Hija de {{ optional($delegacion->padre)->nombre }}</div>
                                    @endif
                                </td>
                                <td><span class="badge badge-light">{{ $redApoyo->nivel_gobierno ?: '—' }}</span></td>
                                <td>{{ $tiposApoyo[$redApoyo->tipo_apoyo] ?? $redApoyo->tipo_apoyo }}</td>
                                <td>
                                    <strong>{{ $redApoyo->institucion }}</strong>
                                    @if($redApoyo->cargo)
                                        <div class="small text-muted">{{ $redApoyo->cargo }}</div>
                                    @endif
                                </td>
                                <td>{{ $redApoyo->contacto ?: '—' }}</td>
                                <td>
                                    @if($telefono)
                                        <a href="https://wa.me/{{ $telefono }}" target="_blank">
                                            {{ $redApoyo->telefono }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $redApoyo->activo ? 'badge-success' : 'badge-secondary' }}">
                                        {{ $redApoyo->activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="{{ route('directorio_red_apoyo.show', $redApoyo) }}" class="btn btn-info btn-sm" title="Ver">
                                            <i class="fa-regular fa-eye"></i>
                                        </a>
                                        @can('editar directorio red apoyo')
                                            <a href="{{ route('directorio_red_apoyo.edit', $redApoyo) }}" class="btn btn-success btn-sm" title="Editar">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </a>
                                        @endcan
                                        @can('eliminar directorio red apoyo')
                                            <form action="{{ route('directorio_red_apoyo.destroy', $redApoyo) }}" method="POST" class="d-inline">
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
    </div>
@stop

@section('css')
    @include('admin.settings.directorio_red_apoyo._styles')
@stop

@section('js')
    <script>
        $(function () {
            $('#directorio-red-apoyo').DataTable({
                pageLength: 25,
                responsive: true,
                lengthChange: true,
                autoWidth: false,
                order: [],
                language: {
                    emptyTable: 'No hay contactos registrados',
                    info: 'Mostrando _START_ a _END_ de _TOTAL_ contactos',
                    infoEmpty: 'Mostrando 0 a 0 de 0 contactos',
                    infoFiltered: '(filtrado de _MAX_ contactos)',
                    lengthMenu: 'Mostrar _MENU_ contactos',
                    loadingRecords: 'Cargando...',
                    processing: 'Procesando...',
                    search: 'Buscar en tabla:',
                    zeroRecords: 'Sin resultados',
                    paginate: {
                        first: 'Primero',
                        last: 'Último',
                        next: 'Siguiente',
                        previous: 'Anterior'
                    }
                },
            });
        });

        $(document).on('click', '.delete-btn', function (event) {
            event.preventDefault();

            const form = $(this).closest('form');

            Swal.fire({
                title: '¿Eliminar contacto?',
                text: 'Se quitará del directorio regional de apoyo.',
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
