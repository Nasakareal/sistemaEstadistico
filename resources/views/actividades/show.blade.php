@extends('adminlte::page')

@section('title', 'Detalle de Actividad')

@section('content_header')
    <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
        <h1 class="mb-0">Detalle de Actividad</h1>

        <div class="btn-group">
            @can('editar actividades')
                <a href="{{ route('actividades.edit', $actividad->id) }}" class="btn btn-success">
                    <i class="fa-solid fa-pen-to-square"></i> Editar
                </a>
            @endcan

            <a href="{{ route('actividades.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </a>

            @can('eliminar actividades')
                <form action="{{ route('actividades.destroy', $actividad->id) }}" method="POST" id="form-eliminar-actividad" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fa-solid fa-trash"></i> Eliminar
                    </button>
                </form>
            @endcan
        </div>
    </div>
@stop

@section('content')
    @php
        $fotoPath = $actividad->foto_path ?? null;
        $urlFoto = $fotoPath ? asset('storage/' . ltrim($fotoPath, '/')) : null;
    @endphp

    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary mb-4">
                <div class="card-header">
                    <h3 class="card-title">Información General</h3>
                </div>

                <div class="card-body">
                    <div class="row" style="row-gap:18px;">
                        <div class="col-md-4">
                            <label class="help-muted d-block">Nombre</label>
                            <div class="form-control-like">{{ $actividad->nombre ?? '—' }}</div>
                        </div>

                        <div class="col-md-4">
                            <label class="help-muted d-block">Categoría</label>
                            <div class="form-control-like">{{ optional($actividad->categoria)->nombre ?? '—' }}</div>
                        </div>

                        <div class="col-md-4">
                            <label class="help-muted d-block">Subcategoría</label>
                            <div class="form-control-like">{{ optional($actividad->subcategoria)->nombre ?? 'Sin subcategoría' }}</div>
                        </div>

                        <div class="col-md-3">
                            <label class="help-muted d-block">Cantidad</label>
                            <div class="form-control-like">{{ (int) ($actividad->cantidad ?? 1) }}</div>
                        </div>

                        <div class="col-md-3">
                            <label class="help-muted d-block">Fecha</label>
                            <div class="form-control-like">
                                {{ $actividad->fecha ? \Carbon\Carbon::parse($actividad->fecha)->format('Y-m-d') : '—' }}
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="help-muted d-block">Hora</label>
                            <div class="form-control-like">
                                {{ $actividad->hora ? \Illuminate\Support\Str::of($actividad->hora)->substr(0,5) : '—' }}
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="help-muted d-block">Estado de revisión</label>
                            <div class="form-control-like">{{ $actividad->estado_revision ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-info mb-4">
                <div class="card-header">
                    <h3 class="card-title">Ubicación</h3>
                </div>

                <div class="card-body">
                    <div class="row" style="row-gap:18px;">
                        <div class="col-md-6">
                            <label class="help-muted d-block">Lugar</label>
                            <div class="form-control-like">{{ $actividad->lugar ?? '—' }}</div>
                        </div>

                        <div class="col-md-3">
                            <label class="help-muted d-block">Municipio</label>
                            <div class="form-control-like">{{ $actividad->municipio ?? '—' }}</div>
                        </div>

                        <div class="col-md-3">
                            <label class="help-muted d-block">Carretera</label>
                            <div class="form-control-like">{{ $actividad->carretera ?? '—' }}</div>
                        </div>

                        <div class="col-md-4">
                            <label class="help-muted d-block">Tramo</label>
                            <div class="form-control-like">{{ $actividad->tramo ?? '—' }}</div>
                        </div>

                        <div class="col-md-2">
                            <label class="help-muted d-block">Kilómetro</label>
                            <div class="form-control-like">{{ $actividad->kilometro ?? '—' }}</div>
                        </div>

                        <div class="col-md-2">
                            <label class="help-muted d-block">Latitud</label>
                            <div class="form-control-like">{{ $actividad->lat ?? '—' }}</div>
                        </div>

                        <div class="col-md-2">
                            <label class="help-muted d-block">Longitud</label>
                            <div class="form-control-like">{{ $actividad->lng ?? '—' }}</div>
                        </div>

                        <div class="col-md-2">
                            <label class="help-muted d-block">Fuente ubicación</label>
                            <div class="form-control-like">{{ $actividad->fuente_ubicacion ?? '—' }}</div>
                        </div>

                        <div class="col-md-12">
                            <label class="help-muted d-block">Coordenadas / referencia</label>
                            <div class="form-control-like text-wrap-block">{{ $actividad->coordenadas_texto ?? '—' }}</div>
                        </div>

                        <div class="col-md-12">
                            <label class="help-muted d-block">Nota geo</label>
                            <div class="form-control-like text-wrap-block">{{ $actividad->nota_geo ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-success mb-4">
                <div class="card-header">
                    <h3 class="card-title">Descripción de la Actividad</h3>
                </div>

                <div class="card-body">
                    <div class="row" style="row-gap:18px;">
                        <div class="col-md-6">
                            <label class="help-muted d-block">Qué ocasiona / motivo</label>
                            <div class="form-control-like text-wrap-block">{{ $actividad->motivo ?? '—' }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="help-muted d-block">Narrativa</label>
                            <div class="form-control-like text-wrap-block">{{ $actividad->narrativa ?? '—' }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="help-muted d-block">Acciones realizadas</label>
                            <div class="form-control-like text-wrap-block">{{ $actividad->acciones_realizadas ?? '—' }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="help-muted d-block">Observaciones</label>
                            <div class="form-control-like text-wrap-block">{{ $actividad->observaciones ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-warning mb-4">
                <div class="card-header">
                    <h3 class="card-title">Participación</h3>
                </div>

                <div class="card-body">
                    <div class="row" style="row-gap:18px;">
                        <div class="col-md-4">
                            <label class="help-muted d-block">Personas alcanzadas</label>
                            <div class="form-control-like">{{ (int) ($actividad->personas_alcanzadas ?? 0) }}</div>
                        </div>

                        <div class="col-md-4">
                            <label class="help-muted d-block">Personas participantes</label>
                            <div class="form-control-like">{{ (int) ($actividad->personas_participantes ?? 0) }}</div>
                        </div>

                        <div class="col-md-4">
                            <label class="help-muted d-block">Personas detenidas</label>
                            <div class="form-control-like">{{ (int) ($actividad->personas_detenidas ?? 0) }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="help-muted d-block">Elementos participantes</label>
                            <div class="form-control-like text-wrap-block">{{ $actividad->elementos_participantes_texto ?? '—' }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="help-muted d-block">Patrullas participantes</label>
                            <div class="form-control-like text-wrap-block">{{ $actividad->patrullas_participantes_texto ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-secondary mb-4">
                <div class="card-header">
                    <h3 class="card-title">Datos Administrativos</h3>
                </div>

                <div class="card-body">
                    <div class="row" style="row-gap:18px;">
                        <div class="col-md-3">
                            <label class="help-muted d-block">Unidad</label>
                            <div class="form-control-like">{{ optional($actividad->unidad)->nombre ?? '—' }}</div>
                        </div>

                        <div class="col-md-3">
                            <label class="help-muted d-block">Delegación</label>
                            <div class="form-control-like">{{ optional($actividad->delegacion)->nombre ?? '—' }}</div>
                        </div>

                        <div class="col-md-3">
                            <label class="help-muted d-block">Destacamento</label>
                            <div class="form-control-like">{{ optional($actividad->destacamento)->nombre ?? '—' }}</div>
                        </div>

                        <div class="col-md-3">
                            <label class="help-muted d-block">Sync status</label>
                            <div class="form-control-like">{{ $actividad->sync_status ?? '—' }}</div>
                        </div>

                        <div class="col-md-4">
                            <label class="help-muted d-block">Creado por</label>
                            <div class="form-control-like">{{ optional($actividad->creador)->name ?? '—' }}</div>
                        </div>

                        <div class="col-md-4">
                            <label class="help-muted d-block">Actualizado por</label>
                            <div class="form-control-like">{{ optional($actividad->actualizador)->name ?? '—' }}</div>
                        </div>

                        <div class="col-md-4">
                            <label class="help-muted d-block">Revisado por</label>
                            <div class="form-control-like">{{ optional($actividad->revisor)->name ?? '—' }}</div>
                        </div>

                        <div class="col-md-3">
                            <label class="help-muted d-block">Fecha de registro</label>
                            <div class="form-control-like">{{ optional($actividad->created_at)->format('Y-m-d H:i') ?? '—' }}</div>
                        </div>

                        <div class="col-md-3">
                            <label class="help-muted d-block">Última actualización</label>
                            <div class="form-control-like">{{ optional($actividad->updated_at)->format('Y-m-d H:i') ?? '—' }}</div>
                        </div>

                        <div class="col-md-3">
                            <label class="help-muted d-block">Revisado el</label>
                            <div class="form-control-like">{{ optional($actividad->revisado_at)->format('Y-m-d H:i') ?? '—' }}</div>
                        </div>

                        <div class="col-md-3">
                            <label class="help-muted d-block">UUID cliente</label>
                            <div class="form-control-like">{{ $actividad->client_uuid ?? '—' }}</div>
                        </div>

                        <div class="col-md-12">
                            <label class="help-muted d-block">Observación de revisión</label>
                            <div class="form-control-like text-wrap-block">{{ $actividad->observacion_revision ?? '—' }}</div>
                        </div>

                        <div class="col-md-12">
                            <label class="help-muted d-block">Sync error</label>
                            <div class="form-control-like text-wrap-block">{{ $actividad->sync_error ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Foto</h3>
                </div>

                <div class="card-body">
                    @if ($urlFoto)
                        <a href="{{ $urlFoto }}" target="_blank" rel="noopener">
                            <img src="{{ $urlFoto }}" alt="foto" class="foto-big">
                        </a>

                        <div class="row mt-3" style="row-gap:18px;">
                            <div class="col-md-6">
                                <label class="help-muted d-block">Archivo original</label>
                                <div class="form-control-like text-wrap-block">{{ $actividad->foto_nombre_original ?? '—' }}</div>
                            </div>

                            <div class="col-md-6">
                                <label class="help-muted d-block">Hash de foto</label>
                                <div class="form-control-like text-wrap-block">{{ $actividad->foto_hash ?? '—' }}</div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning mb-0">
                            No hay foto registrada para esta actividad.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .help-muted {
            color: rgba(234,240,255,.65);
        }

        .form-control-like {
            color: #eaf0ff;
            background-color: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 12px;
            padding: .6rem .75rem;
            min-height: 42px;
            display: flex;
            align-items: center;
        }

        .text-wrap-block {
            white-space: pre-wrap;
            word-break: break-word;
            align-items: flex-start;
        }

        .foto-big {
            width: 100%;
            max-width: 900px;
            height: auto;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,.16);
            background: rgba(255,255,255,.06);
            display: block;
        }
    </style>
@stop

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('form-eliminar-actividad');
            if (!form) return;

            form.addEventListener('submit', function (e) {
                if (typeof Swal === 'undefined') {
                    if (!confirm('¿Seguro que deseas eliminar esta actividad?')) {
                        e.preventDefault();
                    }
                    return;
                }

                e.preventDefault();

                Swal.fire({
                    icon: 'warning',
                    title: '¿Eliminar actividad?',
                    text: 'Esta acción no se puede deshacer.',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@stop
