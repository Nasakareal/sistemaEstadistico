@extends('adminlte::page')

@section('title', 'Detalle del Consolidado de Operativos')

@section('content_header')
<div class="d-flex align-items-center justify-content-between">
    <div>
        <h1 class="mb-0">Detalle del Consolidado de Operativos</h1>
        <small class="text-muted">Consulta del consolidado capturado.</small>
    </div>

    <div class="d-flex align-items-center">
        <a href="{{ route('operativos.index') }}" class="btn btn-secondary mr-2">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>

        @can('editar operativos')
            <a href="{{ route('operativos.edit', $capturaUuid) }}" class="btn btn-success mr-2">
                <i class="fas fa-edit mr-1"></i> Editar
            </a>
        @endcan
    </div>
</div>
@stop

@section('content')

@php
    $todasLasFotos = collect();

    foreach ($operativos as $item) {
        if ($item->fotos && $item->fotos->count()) {
            foreach ($item->fotos as $foto) {
                $todasLasFotos->push([
                    'url' => asset('storage/' . $foto->foto_path),
                    'nombre' => $foto->foto_nombre_original ?: 'FOTO',
                    'operativo' => $item->catalogo->nombre ?? 'SIN NOMBRE',
                    'descripcion' => $item->descripcion ?? '',
                ]);
            }
        }
    }
@endphp

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

<div class="card shadow-sm">
    <div class="card-body">

        <div class="card card-outline card-primary mb-4">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info-circle mr-1"></i> Datos generales
                </h3>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Fecha</label>
                            <input type="date" class="form-control"
                                value="{{ !empty($primero->fecha) ? \Carbon\Carbon::parse($primero->fecha)->format('Y-m-d') : '' }}"
                                disabled>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Hora</label>
                            <input type="time" class="form-control"
                                value="{{ !empty($primero->hora) ? \Carbon\Carbon::parse($primero->hora)->format('H:i') : '' }}"
                                disabled>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Descripción general</label>
                            <input type="text" class="form-control"
                                value="{{ $primero->descripcion ?? '' }}" disabled>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-0">
                            <label>Tramos / Municipios</label>
                            <input type="text" class="form-control"
                                value="{{ $primero->lugar ?? '' }}" disabled>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group mb-0">
                            <label>Unidad</label>
                            <input type="text" class="form-control"
                                value="{{ $primero->unidad->nombre ?? 'SIN UNIDAD' }}" disabled>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group mb-0">
                            <label>Delegación</label>
                            <input type="text" class="form-control"
                                value="{{ $primero->delegacion->nombre ?? 'SIN DELEGACIÓN' }}" disabled>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-outline card-primary mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="card-title">
                    <i class="fas fa-shield-alt mr-1"></i> Operativos capturados
                </h3>

                <span class="badge badge-light border">
                    {{ $operativos->count() }} operativos con captura
                </span>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0" id="tabla_operativos">
                        <thead>
                            <tr class="text-center align-middle">
                                <th style="min-width: 240px;">Operativo</th>
                                <th style="min-width: 110px;">Realizados</th>
                                <th style="min-width: 120px;">Vehículos</th>
                                <th style="min-width: 120px;">Personas</th>
                                <th style="min-width: 120px;">Veh. impact.</th>
                                <th style="min-width: 120px;">Pers. impact.</th>
                                <th style="min-width: 120px;">Edo. fuerza</th>
                                <th style="min-width: 110px;">KM</th>
                                <th style="min-width: 220px;">CRP´s</th>
                                <th style="min-width: 260px;">Fotos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($operativos as $item)
                                <tr class="fila-operativo activa">
                                    <td class="align-middle bg-white">
                                        <strong>{{ $item->catalogo->nombre ?? 'SIN NOMBRE' }}</strong>
                                    </td>

                                    <td class="text-center">{{ $item->dispositivos_realizados ?? 0 }}</td>
                                    <td class="text-center">{{ $item->vehiculos_inspeccionados ?? 0 }}</td>
                                    <td class="text-center">{{ $item->personas_inspeccionadas ?? 0 }}</td>
                                    <td class="text-center">{{ $item->vehiculos_impactados ?? 0 }}</td>
                                    <td class="text-center">{{ $item->personas_impactadas ?? 0 }}</td>
                                    <td class="text-center">{{ $item->estado_fuerza_participante ?? 0 }}</td>
                                    <td class="text-center">{{ number_format((float)($item->kilometros_recorridos ?? 0), 2) }}</td>
                                    <td>{{ $item->crps_participantes ?: 'SIN REGISTRO' }}</td>

                                    <td>
                                        @if($item->fotos && $item->fotos->count())
                                            <div class="fotos-miniaturas-wrap">
                                                @foreach($item->fotos as $foto)
                                                    @php
                                                        $indiceGlobal = $todasLasFotos->search(function ($f) use ($foto) {
                                                            return $f['url'] === asset('storage/' . $foto->foto_path);
                                                        });
                                                    @endphp

                                                    <button type="button"
                                                            class="btn-foto-miniatura"
                                                            onclick="abrirCarrusel({{ $indiceGlobal !== false ? $indiceGlobal : 0 }})"
                                                            title="{{ $foto->foto_nombre_original ?: 'FOTO' }}">
                                                        <img src="{{ asset('storage/'.$foto->foto_path) }}"
                                                             alt="{{ $foto->foto_nombre_original ?: 'FOTO' }}">
                                                    </button>
                                                @endforeach
                                            </div>

                                            <div class="mt-2">
                                                <button type="button"
                                                        class="btn btn-xs btn-outline-light"
                                                        onclick="abrirCarruselPorOperativo('{{ $item->id }}')">
                                                    <i class="fas fa-images mr-1"></i> Ver carrusel
                                                </button>
                                            </div>

                                            <input type="hidden"
                                                   class="primer-indice-operativo"
                                                   id="operativo_{{ $item->id }}_primer_indice"
                                                   value="
                                                   @php
                                                       $primerIndice = 0;
                                                       foreach ($item->fotos as $foto) {
                                                           $busqueda = $todasLasFotos->search(function ($f) use ($foto) {
                                                               return $f['url'] === asset('storage/' . $foto->foto_path);
                                                           });
                                                           if ($busqueda !== false) {
                                                               $primerIndice = $busqueda;
                                                               break;
                                                           }
                                                       }
                                                       echo $primerIndice;
                                                   @endphp
                                                   ">
                                        @else
                                            <span class="text-muted">Sin fotografías</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">
                                        No hay operativos capturados para este consolidado.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if($todasLasFotos->count())
        <div class="card card-outline card-warning mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="card-title">
                    <i class="fas fa-camera mr-1"></i> Galería de fotografías
                </h3>

                <span class="badge badge-light border">
                    {{ $todasLasFotos->count() }} fotografías
                </span>
            </div>

            <div class="card-body">
                <div class="row">
                    @foreach($todasLasFotos as $index => $foto)
                        <div class="col-md-3 col-sm-4 col-6 mb-3">
                            <div class="galeria-card" onclick="abrirCarrusel({{ $index }})">
                                <img src="{{ $foto['url'] }}" alt="{{ $foto['nombre'] }}">
                                <div class="galeria-overlay">
                                    <div class="galeria-titulo">{{ $foto['operativo'] }}</div>
                                    <small>{{ $foto['nombre'] }}</small>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <div class="card card-outline card-info mb-4">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-bar mr-1"></i> Totales generales
                </h3>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Antecedentes personas</label>
                            <input type="number" class="form-control"
                                value="{{ $primero->antecedentes_personas ?? 0 }}" disabled>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Antecedentes vehículos</label>
                            <input type="number" class="form-control"
                                value="{{ $primero->antecedentes_vehiculos ?? 0 }}" disabled>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Antecedentes motos</label>
                            <input type="number" class="form-control"
                                value="{{ $primero->antecedentes_motos ?? 0 }}" disabled>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Antecedentes camiones</label>
                            <input type="number" class="form-control"
                                value="{{ $primero->antecedentes_camiones ?? 0 }}" disabled>
                        </div>
                    </div>
                </div>

                <div class="row mb-0">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Puestas a disposición</label>
                            <input type="number" class="form-control"
                                value="{{ $primero->puestas_disposicion ?? 0 }}" disabled>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Vehículos recuperados</label>
                            <input type="number" class="form-control"
                                value="{{ $primero->vehiculos_recuperados ?? 0 }}" disabled>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Armas aseguradas</label>
                            <input type="number" class="form-control"
                                value="{{ $primero->armas_aseguradas ?? 0 }}" disabled>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Mercancía recuperada</label>
                            <input type="number" class="form-control"
                                value="{{ $primero->mercancia_recuperada ?? 0 }}" disabled>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Decomiso de drogas</label>
                            <input type="number" class="form-control"
                                value="{{ $primero->decomiso_drogas ?? 0 }}" disabled>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-outline card-secondary mb-4">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-sticky-note mr-1"></i> Observaciones
                </h3>
            </div>

            <div class="card-body">
                <textarea class="form-control" rows="4" disabled>{{ $primero->observaciones }}</textarea>
            </div>
        </div>

        <div class="card card-outline card-success mb-0">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fab fa-whatsapp mr-1"></i> Texto para WhatsApp
                </h3>
            </div>

            <div class="card-body">
                <textarea class="form-control" rows="12" id="whatsappTexto" readonly>{{ $whatsappTexto }}</textarea>

                <div class="mt-3 text-right">
                    <button type="button" class="btn btn-success" id="btnCopiarWhatsapp">
                        <i class="fas fa-copy mr-1"></i> Copiar texto
                    </button>
                </div>
            </div>
        </div>

    </div>

    <div class="card-footer text-right">
        <a href="{{ route('operativos.index') }}" class="btn btn-secondary">
            Volver
        </a>
    </div>
</div>

@if($todasLasFotos->count())
<div class="modal fade" id="modalCarruselFotos" tabindex="-1" role="dialog" aria-labelledby="modalCarruselFotosLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content modal-fotos">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="modalCarruselFotosLabel">
                    <i class="fas fa-images mr-1"></i> Fotografías del operativo
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body pt-2">
                <div id="carouselFotosOperativo" class="carousel slide" data-ride="carousel" data-interval="false">
                    <ol class="carousel-indicators">
                        @foreach($todasLasFotos as $index => $foto)
                            <li data-target="#carouselFotosOperativo"
                                data-slide-to="{{ $index }}"
                                class="{{ $index === 0 ? 'active' : '' }}">
                            </li>
                        @endforeach
                    </ol>

                    <div class="carousel-inner">
                        @foreach($todasLasFotos as $index => $foto)
                            <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                                <div class="carousel-img-wrap">
                                    <img src="{{ $foto['url'] }}" class="d-block w-100 carousel-img-principal" alt="{{ $foto['nombre'] }}">
                                </div>

                                <div class="carousel-caption-custom">
                                    <h5 class="mb-1">{{ $foto['operativo'] }}</h5>
                                    <p class="mb-0">{{ $foto['nombre'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <a class="carousel-control-prev" href="#carouselFotosOperativo" role="button" data-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="sr-only">Anterior</span>
                    </a>

                    <a class="carousel-control-next" href="#carouselFotosOperativo" role="button" data-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="sr-only">Siguiente</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@stop

@section('css')
<style>
    #tabla_operativos {
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
        background: transparent;
    }

    #tabla_operativos td,
    #tabla_operativos th {
        vertical-align: middle;
        border-color: rgba(255, 255, 255, 0.12) !important;
    }

    #tabla_operativos thead th {
        position: sticky;
        top: 0;
        z-index: 5;
        background: linear-gradient(180deg, #3b434f 0%, #313844 100%) !important;
        color: #f4f6f9 !important;
        font-weight: 700;
        box-shadow: inset 0 -1px 0 rgba(255, 255, 255, 0.08);
        text-align: center;
    }

    #tabla_operativos tbody tr {
        background: linear-gradient(90deg, rgba(42, 50, 66, 0.96) 0%, rgba(58, 63, 88, 0.96) 100%);
        transition: background-color .2s ease, box-shadow .2s ease;
    }

    #tabla_operativos tbody tr:hover {
        background: linear-gradient(90deg, rgba(50, 60, 79, 0.98) 0%, rgba(70, 76, 105, 0.98) 100%);
    }

    #tabla_operativos tbody td {
        color: #e9eef5;
        background: transparent !important;
    }

    #tabla_operativos tbody td.bg-white,
    #tabla_operativos tbody td:first-child {
        background: linear-gradient(180deg, #f2f4f7 0%, #e4e8ee 100%) !important;
        color: #253041 !important;
        border-right: 1px solid rgba(0, 0, 0, 0.08) !important;
    }

    #tabla_operativos tbody td.bg-white strong,
    #tabla_operativos tbody td:first-child strong {
        color: #46566d !important;
        font-weight: 700;
        letter-spacing: .3px;
    }

    .fila-operativo.activa {
        background: linear-gradient(90deg, rgba(25, 92, 160, 0.18) 0%, rgba(79, 70, 229, 0.18) 100%) !important;
        box-shadow: inset 0 0 0 1px rgba(110, 168, 254, 0.18);
    }

    .fila-operativo.activa td:first-child {
        border-left: 4px solid #4ea3ff !important;
        background: linear-gradient(180deg, #f7faff 0%, #e8f2ff 100%) !important;
    }

    .card {
        border-radius: .85rem;
        overflow: hidden;
    }

    .card-header {
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .table-responsive {
        background: linear-gradient(90deg, #263142 0%, #3a3c58 100%);
        border-radius: 0 0 .75rem .75rem;
    }

    .table td,
    .table th {
        padding: .85rem .75rem;
    }

    .form-control[disabled],
    textarea.form-control[disabled],
    textarea.form-control[readonly] {
        background-color: #f8f9fa !important;
        color: #495057 !important;
        opacity: 1;
    }

    .btn-outline-light {
        border-radius: 999px;
    }

    .card.card-outline.card-primary,
    .card.card-outline.card-info,
    .card.card-outline.card-secondary,
    .card.card-outline.card-success,
    .card.card-outline.card-warning {
        border-top-width: 3px;
    }

    .fotos-miniaturas-wrap {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .btn-foto-miniatura {
        border: 0;
        padding: 0;
        width: 58px;
        height: 58px;
        border-radius: 10px;
        overflow: hidden;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(0,0,0,.22);
        background: #1f2937;
    }

    .btn-foto-miniatura img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform .2s ease;
    }

    .btn-foto-miniatura:hover img {
        transform: scale(1.06);
    }

    .galeria-card {
        position: relative;
        height: 210px;
        border-radius: 16px;
        overflow: hidden;
        cursor: pointer;
        box-shadow: 0 6px 18px rgba(0,0,0,.18);
        background: #dfe6ee;
    }

    .galeria-card img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform .25s ease;
    }

    .galeria-card:hover img {
        transform: scale(1.04);
    }

    .galeria-overlay {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        padding: 12px;
        background: linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(0,0,0,.82) 100%);
        color: #fff;
    }

    .galeria-titulo {
        font-weight: 700;
        font-size: .92rem;
        line-height: 1.2;
    }

    .modal-fotos {
        background: #111827;
        color: #fff;
        border-radius: 18px;
        overflow: hidden;
    }

    .carousel-img-wrap {
        width: 100%;
        height: 72vh;
        background: #0b1220;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 16px;
        overflow: hidden;
    }

    .carousel-img-principal {
        width: 100%;
        height: 100%;
        object-fit: contain;
        background: #0b1220;
    }

    .carousel-caption-custom {
        margin-top: 14px;
        text-align: center;
        color: #e5e7eb;
    }

    .carousel-indicators li {
        width: 10px;
        height: 10px;
        border-radius: 50%;
    }

    .carousel-control-prev,
    .carousel-control-next {
        width: 8%;
    }

    .carousel-control-prev-icon,
    .carousel-control-next-icon {
        background-size: 70% 70%;
        background-color: rgba(0, 0, 0, 0.35);
        border-radius: 50%;
        width: 46px;
        height: 46px;
    }
</style>
@stop

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btnCopiar = document.getElementById('btnCopiarWhatsapp');
        const textarea = document.getElementById('whatsappTexto');

        if (btnCopiar && textarea) {
            btnCopiar.addEventListener('click', async function () {
                try {
                    await navigator.clipboard.writeText(textarea.value);

                    Swal.fire({
                        icon: 'success',
                        title: 'Texto copiado',
                        text: 'El texto para WhatsApp se copió correctamente.',
                        timer: 1800,
                        showConfirmButton: false
                    });
                } catch (error) {
                    textarea.select();
                    document.execCommand('copy');

                    Swal.fire({
                        icon: 'success',
                        title: 'Texto copiado',
                        text: 'El texto para WhatsApp se copió correctamente.',
                        timer: 1800,
                        showConfirmButton: false
                    });
                }
            });
        }
    });

    function abrirCarrusel(indice) {
        $('#modalCarruselFotos').modal('show');
        $('#carouselFotosOperativo').carousel(parseInt(indice));
    }

    function abrirCarruselPorOperativo(operativoId) {
        const input = document.getElementById('operativo_' + operativoId + '_primer_indice');
        const indice = input ? parseInt(input.value || 0) : 0;
        abrirCarrusel(indice);
    }
</script>
@stop
