@extends('adminlte::page')

@section('title', 'Vialidades Urbanas')

@section('content_header')
    <h1>Vialidades Urbanas</h1>
@stop

@section('content')
    @php
        $vialidadUrbanaId = $vialidadUrbana ?? 1;
    @endphp

    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Dispositivos activos registrados</h3>

                    <div class="card-tools">
                        @can('crear operativos vialidades')
                            <a href="{{ route('vialidades_urbanas.create') }}" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-plus"></i> Nuevo dispositivo
                            </a>
                        @endcan

                        <a href="{{ route('vialidades_urbanas.resumen', $vialidadUrbanaId) }}?fecha={{ $fechaSeleccionada ?? now('America/Mexico_City')->format('Y-m-d') }}" class="btn btn-info btn-sm">
                            <i class="fa-solid fa-chart-column"></i> Resumen
                        </a>

                        <button
                            type="button"
                            class="btn btn-success btn-sm"
                            id="btnCompartirResumen"
                            data-url="{{ route('vialidades_urbanas.whatsapp', $vialidadUrbanaId) }}?fecha={{ $fechaSeleccionada ?? now('America/Mexico_City')->format('Y-m-d') }}"
                        >
                            <i class="fa-brands fa-whatsapp"></i> Compartir resumen
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" action="{{ route('vialidades_urbanas.index') }}" class="row mb-3" autocomplete="off">
                        <div class="col-md-4">
                            <label for="fecha_filtro">Filtrar por fecha:</label>
                            <input
                                type="date"
                                id="fecha_filtro"
                                name="fecha"
                                class="form-control"
                                value="{{ $fechaSeleccionada ?? now('America/Mexico_City')->format('Y-m-d') }}"
                            >
                        </div>

                        <div class="col-md-8 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary mr-2">
                                <i class="fa-solid fa-filter"></i> Filtrar
                            </button>

                            <a href="{{ route('vialidades_urbanas.index') }}" class="btn btn-secondary">
                                <i class="fa-solid fa-rotate-left"></i> Hoy
                            </a>
                        </div>
                    </form>

                    <div class="row mb-3">
                        <div class="col-md-3 col-6">
                            <div class="small-box bg-primary">
                                <div class="inner">
                                    <h3>{{ $dispositivos->total() }}</h3>
                                    <p>Dispositivos</p>
                                </div>
                                <div class="icon">
                                    <i class="fa-solid fa-road-barrier"></i>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3>{{ $dispositivos->sum('elementos') }}</h3>
                                    <p>Elementos</p>
                                </div>
                                <div class="icon">
                                    <i class="fa-solid fa-users"></i>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>{{ $dispositivos->sum('motopatrullas') + $dispositivos->sum('unidades_motorizadas') + $dispositivos->sum('patrullas') }}</h3>
                                    <p>Unidades</p>
                                </div>
                                <div class="icon">
                                    <i class="fa-solid fa-motorcycle"></i>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3>{{ $dispositivos->sum('fenix') }}</h3>
                                    <p>Fénix</p>
                                </div>
                                <div class="icon">
                                    <i class="fa-solid fa-shield-halved"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="vialidadesUrbanas" class="table table-striped table-bordered table-hover table-sm">
                            <thead>
                                <tr>
                                    <th class="text-center">ID</th>
                                    <th class="text-center">Fecha y Hora</th>
                                    <th class="text-center">Asunto</th>
                                    <th class="text-center">Catálogo</th>
                                    <th class="text-center">Lugar</th>
                                    <th class="text-center">Portada</th>
                                    <th class="text-center">Estado de Fuerza</th>
                                    <th class="text-center">Creado por</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($dispositivos as $dispositivo)
                                    @php
                                        $fechaMostrar = !empty($dispositivo->fecha)
                                            ? \Carbon\Carbon::parse($dispositivo->fecha)->format('Y-m-d')
                                            : '';

                                        $horaMostrar = !empty($dispositivo->hora)
                                            ? substr((string) $dispositivo->hora, 0, 5)
                                            : '';

                                        $fotoPortada = optional($dispositivo->fotoPortada)->ruta;
                                        $urlFoto = $fotoPortada ? asset('storage/' . ltrim($fotoPortada, '/')) : null;

                                        $estadoFuerza = [];
                                        if ((int) $dispositivo->elementos > 0) $estadoFuerza[] = (int) $dispositivo->elementos . ' ELEM';
                                        if ((int) $dispositivo->crp > 0) $estadoFuerza[] = (int) $dispositivo->crp . ' CRP';
                                        if ((int) $dispositivo->motopatrullas > 0) $estadoFuerza[] = (int) $dispositivo->motopatrullas . ' MOTO';
                                        if ((int) $dispositivo->fenix > 0) $estadoFuerza[] = (int) $dispositivo->fenix . ' FÉNIX';
                                        if ((int) $dispositivo->unidades_motorizadas > 0) $estadoFuerza[] = (int) $dispositivo->unidades_motorizadas . ' U.M.';
                                        if ((int) $dispositivo->patrullas > 0) $estadoFuerza[] = (int) $dispositivo->patrullas . ' PAT';
                                        if ((int) $dispositivo->gruas > 0) $estadoFuerza[] = (int) $dispositivo->gruas . ' GRÚAS';
                                    @endphp

                                    <tr>
                                        <td>{{ $dispositivo->id }}</td>

                                        <td>{{ trim($fechaMostrar . ' ' . $horaMostrar) }}</td>

                                        <td>{{ $dispositivo->asunto }}</td>

                                        <td>{{ optional($dispositivo->catalogo)->nombre ?? 'SIN CATÁLOGO' }}</td>

                                        <td>
                                            {{ $dispositivo->lugar ?? 'SIN LUGAR' }}
                                            @if(!empty($dispositivo->municipio))
                                                <br>
                                                <small class="text-muted">{{ $dispositivo->municipio }}</small>
                                            @endif
                                        </td>

                                        <td>
                                            @if ($urlFoto)
                                                <a href="{{ $urlFoto }}" target="_blank" rel="noopener">
                                                    <img src="{{ $urlFoto }}" alt="portada" class="foto-thumb">
                                                </a>
                                            @else
                                                <span class="text-muted">Sin foto</span>
                                            @endif
                                        </td>

                                        <td>
                                            @if(count($estadoFuerza))
                                                {!! implode('<br>', $estadoFuerza) !!}
                                            @else
                                                <span class="text-muted">Sin datos</span>
                                            @endif
                                        </td>

                                        <td>{{ optional($dispositivo->creador)->name ?? 'Desconocido' }}</td>

                                        <td class="text-center">
                                            <a href="{{ route('vialidades_urbanas.edit', $dispositivo->id) }}" class="btn btn-info btn-sm" title="Ver / Editar">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>

                                            @can('editar operativos vialidades')
                                                <a href="{{ route('vialidades_urbanas.edit', $dispositivo->id) }}" class="btn btn-success btn-sm" title="Editar">
                                                    <i class="fa-solid fa-pencil"></i>
                                                </a>
                                            @endcan

                                            <button
                                                type="button"
                                                class="btn btn-success btn-sm btn-compartir-dispositivo"
                                                data-url="{{ route('vialidades_urbanas.whatsapp', $dispositivo->id) }}?fecha={{ $fechaMostrar }}"
                                                title="Compartir por WhatsApp"
                                            >
                                                <i class="fa-brands fa-whatsapp"></i>
                                            </button>

                                            @can('eliminar operativos vialidades')
                                                <form action="{{ route('vialidades_urbanas.update', $dispositivo->id) }}" method="POST" style="display:none;"></form>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($dispositivos->isEmpty())
                        <div class="text-center text-muted mt-3">
                            No hay dispositivos registrados para la fecha seleccionada.
                        </div>
                    @endif

                    <div class="mt-3">
                        {{ $dispositivos->links() }}
                    </div>
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

        #vialidadesUrbanas.table-hover tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.08) !important;
        }

        #vialidadesUrbanas.table-hover tbody tr:hover td,
        #vialidadesUrbanas.table-hover tbody tr:hover th {
            color: #ffffff !important;
        }

        #vialidadesUrbanas.table-hover tbody tr:hover a,
        #vialidadesUrbanas.table-hover tbody tr:hover button {
            color: #ffffff !important;
        }

        input[type="date"].form-control {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.06) !important;
            border-color: rgba(255, 255, 255, 0.15) !important;
        }

        input[type="date"].form-control:focus {
            background-color: rgba(255, 255, 255, 0.10) !important;
            border-color: rgba(255, 255, 255, 0.30) !important;
            box-shadow: 0 0 0 .2rem rgba(255, 255, 255, 0.10) !important;
        }

        input[type="date"].form-control::-webkit-calendar-picker-indicator {
            filter: invert(1);
            opacity: 0.9;
            cursor: pointer;
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
    </style>
@stop

@section('js')
    <script>
        $(function () {
            if ($.fn.DataTable.isDataTable('#vialidadesUrbanas')) {
                $('#vialidadesUrbanas').DataTable().destroy();
            }

            $('#vialidadesUrbanas').DataTable({
                paging: false,
                info: false,
                order: [[0, 'desc']],
                language: {
                    emptyTable: "No hay información disponible",
                    loadingRecords: "Cargando...",
                    processing: "Procesando...",
                    search: "Buscar:",
                    zeroRecords: "No se encontraron resultados"
                },
                responsive: true,
                lengthChange: false,
                autoWidth: false
            });

            $(document).on('click', '.btn-compartir-dispositivo', async function () {
                const url = this.dataset.url;

                try {
                    const resp = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    if (!resp.ok) {
                        throw new Error('No se pudo obtener la información del dispositivo.');
                    }

                    const data = await resp.json();
                    const texto = (data.texto || '').trim();

                    if (navigator.share) {
                        await navigator.share({ text: texto });
                        return;
                    }

                    const waUrl = 'https://wa.me/?text=' + encodeURIComponent(texto);
                    window.open(waUrl, '_blank');
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'No se pudo compartir el dispositivo.'
                    });
                }
            });

            $('#btnCompartirResumen').on('click', async function () {
                const url = this.dataset.url;

                try {
                    const resp = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    if (!resp.ok) {
                        throw new Error('No se pudo obtener el resumen.');
                    }

                    const data = await resp.json();
                    const texto = (data.texto || '').trim();

                    if (navigator.share) {
                        await navigator.share({ text: texto });
                        return;
                    }

                    const waUrl = 'https://wa.me/?text=' + encodeURIComponent(texto);
                    window.open(waUrl, '_blank');
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'No se pudo compartir el resumen.'
                    });
                }
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
        });
    </script>
@stop
