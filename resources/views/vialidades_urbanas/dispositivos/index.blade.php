@extends('adminlte::page')

@section('title', 'Detalles del Dispositivo')

@section('content_header')
    <h1>Detalles del dispositivo #{{ $dispositivo->id }}</h1>
@stop

@section('content')
    @php
        $vialidadUrbanaId = $vialidadUrbana ?? 1;
    @endphp

    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        {{ $dispositivo->asunto ?? 'SIN ASUNTO' }}
                    </h3>

                    <div class="card-tools">
                        <a href="{{ route('vialidades_urbanas.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-arrow-left"></i> Regresar
                        </a>

                        @can('crear operativos vialidades')
                            <a href="{{ route('vialidades_urbanas.dispositivos.create', [$vialidadUrbanaId, $dispositivo->id]) }}" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-plus"></i> Agregar detalle
                            </a>
                        @endcan

                        <a href="{{ route('vialidades_urbanas.dispositivos.edit', [$vialidadUrbanaId, $dispositivo->id]) }}" class="btn btn-success btn-sm">
                            <i class="fa-solid fa-pen"></i> Editar
                        </a>

                        <button type="button" class="btn btn-success btn-sm" id="btnCompartirTarjeta" data-url="{{ route('vialidades_urbanas.dispositivos.whatsapp', [$vialidadUrbanaId, $dispositivo->id]) }}">
                            <i class="fa-brands fa-whatsapp"></i> Compartir tarjeta
                        </button>
                    </div>
                </div>

                <div class="card-body">

                    <div class="mb-3">
                        <strong>Fecha:</strong> {{ $dispositivo->fecha }}<br>
                        <strong>Hora:</strong> {{ substr((string) $dispositivo->hora, 0, 5) }}<br>
                        <strong>Lugar:</strong> {{ $dispositivo->lugar ?? 'SIN LUGAR' }}<br>
                        <strong>Municipio:</strong> {{ $dispositivo->municipio ?? 'SIN MUNICIPIO' }}<br>
                        <strong>Catálogo:</strong> {{ optional($dispositivo->catalogo)->nombre ?? 'SIN CATÁLOGO' }}
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm" id="tablaDetallesDispositivo">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Tipo</th>
                                    <th>Título</th>
                                    <th>Contenido</th>
                                    <th>Ubicación</th>
                                    <th>Hora</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($dispositivo->detalles as $detalle)
                                    <tr>
                                        <td>{{ $detalle->orden }}</td>
                                        <td>{{ $detalle->tipo }}</td>
                                        <td>{{ $detalle->titulo ?? '-' }}</td>
                                        <td>{{ $detalle->contenido }}</td>
                                        <td>{{ $detalle->ubicacion ?? '-' }}</td>
                                        <td>{{ $detalle->hora ? substr((string) $detalle->hora, 0, 5) : '-' }}</td>
                                        <td class="text-center">
                                            <form action="{{ route('vialidades_urbanas.dispositivos.destroy', [$vialidadUrbanaId, $dispositivo->id, $detalle->id]) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar detalle?')">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            No hay detalles registrados.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <hr>

                    <h5>Fotos</h5>
                    <div class="row">
                        @forelse($dispositivo->fotos as $foto)
                            <div class="col-md-3 mb-3">
                                <img src="{{ asset('storage/' . $foto->ruta) }}" class="img-fluid rounded foto-thumb-grande">
                            </div>
                        @empty
                            <div class="col-12 text-muted text-center">
                                Sin fotos
                            </div>
                        @endforelse
                    </div>

                </div>

                <div class="card-footer text-center">
                    <div class="tarjeta-informante">
                        <div class="tarjeta-informante-titulo">INFORMA EL AGENTE</div>
                        <div class="tarjeta-informante-nombre">{{ optional($dispositivo->creador)->name ?? 'SIN USUARIO REGISTRADO' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
    .table td, .table th {
        vertical-align: middle;
        text-align: center;
    }

    .foto-thumb-grande {
        width: 100%;
        height: 220px;
        object-fit: cover;
        border-radius: 10px;
        border: 1px solid rgba(0,0,0,.12);
    }

    .tarjeta-informante {
        padding: 14px 20px;
        border-radius: 14px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.10);
        display: inline-block;
        min-width: 340px;
    }

    .tarjeta-informante-titulo {
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 1px;
        color: #cbd5e1;
        margin-bottom: 6px;
    }

    .tarjeta-informante-nombre {
        font-size: 20px;
        font-weight: 700;
        color: #ffffff;
    }
</style>
@stop

@section('js')
<script>
    $(function () {
        $('#btnCompartirTarjeta').on('click', async function () {
            const url = this.dataset.url;

            try {
                const resp = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!resp.ok) {
                    throw new Error('No se pudo obtener la tarjeta del dispositivo.');
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
                    text: error.message || 'No se pudo compartir la tarjeta.'
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
    });
</script>
@stop
