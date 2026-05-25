@extends('adminlte::page')

@section('title', 'Detalle de oficio')

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h1 class="mb-1">{{ $oficio->tipo_label }}</h1>
            <p class="text-muted mb-0 oficio-numero">{{ $oficio->numero_oficio }}</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('oficios.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </a>
            @can('crear oficios')
                <a href="{{ route('oficios.create', ['contesta_a_id' => $oficio->id]) }}" class="btn btn-primary">
                    <i class="fa-solid fa-reply"></i> Contestar
                </a>
            @endcan
            @can('editar oficios')
                <a href="{{ route('oficios.edit', $oficio) }}" class="btn btn-success">
                    <i class="fa-regular fa-pen-to-square"></i> Editar
                </a>
            @endcan
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa-regular fa-file-lines"></i> Información principal
                    </h3>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <span class="badge badge-light">{{ $oficio->tipo_label }}</span>
                        <span class="oficio-badge oficio-badge--{{ $oficio->sentido }}">
                            <i class="fa-solid {{ $oficio->sentido === 'entrada' ? 'fa-arrow-down' : 'fa-arrow-up' }}"></i>
                            {{ $oficio->sentido_label }}
                        </span>
                    </div>

                    <div class="oficio-meta">
                        <div class="oficio-meta__item">
                            <div class="oficio-meta__label">Unidad</div>
                            <div class="oficio-meta__value">{{ $oficio->unidad->nombre ?? 'Sin unidad' }}</div>
                        </div>
                        <div class="oficio-meta__item">
                            <div class="oficio-meta__label">Fecha</div>
                            <div class="oficio-meta__value">{{ optional($oficio->fecha_documento)->format('d-m-Y') ?? 'Sin fecha' }}</div>
                        </div>
                        <div class="oficio-meta__item">
                            <div class="oficio-meta__label">Término</div>
                            <div class="oficio-meta__value">{{ $oficio->termino_label ?? 'Sin término' }}</div>
                        </div>
                        <div class="oficio-meta__item">
                            <div class="oficio-meta__label">Remitente</div>
                            <div class="oficio-meta__value">{{ $oficio->remitente ?? '—' }}</div>
                        </div>
                        <div class="oficio-meta__item">
                            <div class="oficio-meta__label">Destinatario</div>
                            <div class="oficio-meta__value">{{ $oficio->destinatario ?? '—' }}</div>
                        </div>
                    </div>

                    <hr>

                    <h5 class="font-weight-bold">Asunto</h5>
                    <p>{{ $oficio->asunto ?? '—' }}</p>

                    <h5 class="font-weight-bold">Notas internas</h5>
                    <p class="mb-0">{!! nl2br(e($oficio->descripcion ?? '—')) !!}</p>
                </div>
            </div>

            <div class="card card-outline card-primary" id="contestaciones">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa-solid fa-reply-all"></i> Contestaciones
                    </h3>
                </div>
                <div class="card-body">
                    @if($oficio->contestaA)
                        <div class="alert alert-info">
                            <strong>Este documento contesta a:</strong>
                            <a href="{{ route('oficios.show', $oficio->contestaA) }}">
                                {{ $oficio->contestaA->tipo_label }} · {{ $oficio->contestaA->numero_oficio }}
                            </a>
                        </div>
                    @elseif($oficio->pendiente_contestacion)
                        <div class="alert alert-danger">
                            Este documento es una entrada y aún no tiene contestación registrada.
                        </div>
                    @else
                        <div class="alert alert-secondary">
                            Este documento está registrado como origen, no como contestación.
                        </div>
                    @endif

                    @if($oficio->contestaciones->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-sm table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th>Número</th>
                                        <th>Movimiento</th>
                                        <th>Asunto</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($oficio->contestaciones as $respuesta)
                                        <tr>
                                            <td>{{ optional($respuesta->fecha_documento)->format('d-m-Y') ?? '—' }}</td>
                                            <td>{{ $respuesta->tipo_label }}</td>
                                            <td class="oficio-numero">{{ $respuesta->numero_corto }}</td>
                                            <td>{{ $respuesta->sentido_label }}</td>
                                            <td>{{ $respuesta->asunto ?? '—' }}</td>
                                            <td class="text-right">
                                                <a href="{{ route('oficios.show', $respuesta) }}" class="btn btn-info btn-sm">
                                                    <i class="fa-regular fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">Aún no hay documentos registrados como contestación.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa-solid fa-paperclip"></i> Archivos
                    </h3>
                </div>
                <div class="card-body">
                    @if($oficio->pdf_path)
                        <a href="{{ asset('storage/' . $oficio->pdf_path) }}" target="_blank" class="btn btn-warning btn-block mb-3">
                            <i class="fa-regular fa-file-pdf"></i> Abrir PDF
                        </a>
                    @else
                        <div class="alert alert-secondary">Sin PDF adjunto.</div>
                    @endif

                    @if($oficio->fotos)
                        <div class="oficio-fotos">
                            @foreach($oficio->fotos as $foto)
                                <a href="{{ asset('storage/' . $foto) }}" target="_blank" class="oficio-foto">
                                    <img src="{{ asset('storage/' . $foto) }}" alt="Foto del oficio">
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa-regular fa-clock"></i> Auditoría
                    </h3>
                </div>
                <div class="card-body">
                    <div class="oficio-meta">
                        <div class="oficio-meta__item">
                            <div class="oficio-meta__label">Capturó</div>
                            <div class="oficio-meta__value">{{ $oficio->creador->name ?? '—' }}</div>
                        </div>
                        <div class="oficio-meta__item">
                            <div class="oficio-meta__label">Registro</div>
                            <div class="oficio-meta__value">{{ optional($oficio->created_at)->format('d-m-Y H:i') }}</div>
                        </div>
                        <div class="oficio-meta__item">
                            <div class="oficio-meta__label">Última edición</div>
                            <div class="oficio-meta__value">{{ optional($oficio->updated_at)->format('d-m-Y H:i') }}</div>
                        </div>
                        <div class="oficio-meta__item">
                            <div class="oficio-meta__label">Editó</div>
                            <div class="oficio-meta__value">{{ $oficio->actualizador->name ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    @include('admin.settings.oficios._styles')
@stop

@section('js')
    @include('admin.settings.oficios._alerts')
@stop
