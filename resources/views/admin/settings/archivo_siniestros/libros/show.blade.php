@extends('adminlte::page')

@section('title', 'Detalle del libro')

@section('content_header')
    <h1>Libro de siniestros · {{ $libro->codigo }}</h1>
@stop

@section('content')
    @include('admin.settings.archivo_siniestros._nav')
    @include('admin.settings.archivo_siniestros._alerts')
    <div class="row">
    <div class="col-md-8">
        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title">Información del libro</h3><div class="card-tools">@can('gestionar archivo siniestros')<a class="btn btn-success btn-sm" href="{{ route('archivo_siniestros.libros.edit', $libro) }}"><i class="fa-solid fa-pen-to-square"></i> Editar</a>@endcan <a class="btn btn-secondary btn-sm" href="{{ route('archivo_siniestros.index') }}">Volver</a></div></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-2"><strong>Código:</strong><br>{{ $libro->codigo }}</div>
                    <div class="col-md-6 mb-2"><strong>Título:</strong><br>{{ $libro->titulo ?: 'Sin título' }}</div>
                    <div class="col-md-3 mb-2"><strong>No. de libro:</strong><br>{{ $libro->numero_libro ?: '—' }}</div>
                    <div class="col-md-3 mb-2"><strong>Año:</strong><br>{{ $libro->anio_inicio ?: '—' }} @if($libro->anio_fin)– {{ $libro->anio_fin }}@endif</div>
                    <div class="col-md-6 mb-2"><strong>Folios:</strong><br>{{ $libro->folio_inicial ?: '—' }} a {{ $libro->folio_final ?: '—' }}</div>
                    <div class="col-md-6 mb-2"><strong>Rango de fechas:</strong><br>{{ optional($libro->fecha_inicio)->format('d/m/Y') ?: '—' }} – {{ optional($libro->fecha_fin)->format('d/m/Y') ?: '—' }}</div>
                    <div class="col-md-6 mb-2"><strong>QR del libro:</strong><br><code class="text-break">{{ $libro->qr_uuid ?: 'Pendiente' }}</code></div>
                    <div class="col-12"><strong>Observaciones:</strong><br>{{ $libro->observaciones ?: 'Sin observaciones' }}</div>
                </div>
            </div>
        </div>
        <div class="card card-outline card-info">
            <div class="card-header"><h3 class="card-title">Documentos digitalizados</h3></div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2"><span>{{ $libro->paginas_digitalizadas }} / {{ $libro->total_paginas ?: '?' }} páginas</span><span class="badge badge-info">{{ str_replace('_', ' ', ucfirst($libro->estado_digitalizacion)) }}</span></div>
                @if($libro->total_paginas)
                    <div class="progress mb-3"><div class="progress-bar bg-info" role="progressbar" style="width:{{ min(100,round(100*$libro->paginas_digitalizadas/max(1,$libro->total_paginas))) }}%" aria-valuenow="{{ $libro->paginas_digitalizadas }}" aria-valuemin="0" aria-valuemax="{{ $libro->total_paginas }}"></div></div>
                @endif
                <div class="table-responsive"><table class="table table-sm table-striped table-bordered"><thead><tr><th>Documento</th><th>Páginas del libro</th><th>Revisión</th><th>Acción</th></tr></thead><tbody>
                @forelse($libro->documentos as $documento)
                    <tr><td>{{ $documento->nombre }}</td><td>{{ $documento->pagina_inicio ?: '—' }}–{{ $documento->pagina_fin ?: '—' }}</td><td>{{ str_replace('_',' ', ucfirst($documento->estado_revision)) }}</td><td><a class="btn btn-info btn-sm" target="_blank" rel="noopener" href="{{ route('archivo_siniestros.libros.documentos.show', [$libro, $documento]) }}"><i class="fa-solid fa-file-pdf"></i> Abrir</a></td></tr>
                @empty<tr><td colspan="4" class="text-center text-muted">Todavía no hay escaneos.</td></tr>@endforelse
                </tbody></table></div>
                @can('digitalizar archivo siniestros')
                <hr>
                <h5>Incorporar escaneo</h5>
                <form method="POST" action="{{ route('archivo_siniestros.libros.documentos.store', $libro) }}" enctype="multipart/form-data">@csrf
                    <div class="row">
                        <div class="col-md-5 form-group"><label for="nombre">Nombre del documento *</label><input name="nombre" id="nombre" maxlength="200" class="form-control" value="{{ old('nombre') }}" required placeholder="Escaneo páginas 1-30"></div>
                        <div class="col-md-3 form-group"><label for="pagina_inicio">Página inicial *</label><input type="number" name="pagina_inicio" id="pagina_inicio" min="1" class="form-control" value="{{ old('pagina_inicio') }}" required></div>
                        <div class="col-md-3 form-group"><label for="pagina_fin">Página final *</label><input type="number" name="pagina_fin" id="pagina_fin" min="1" class="form-control" value="{{ old('pagina_fin') }}" required></div>
                        <div class="col-md-8 form-group"><label for="archivo">Archivo PDF o imagen *</label><input type="file" name="archivo" id="archivo" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required><small class="form-text text-muted">Máximo 7 MB. Se almacenará de forma privada.</small></div>
                        <div class="col-md-4 form-group align-self-end"><button type="submit" class="btn btn-primary btn-block"><i class="fa-solid fa-upload"></i> Guardar escaneo</button></div>
                    </div>
                </form>
                @endcan
            </div>
        </div>
        <div class="card card-outline card-secondary">
            <div class="card-header"><h3 class="card-title">Folios indexados</h3><div class="card-tools"><a class="btn btn-info btn-sm" href="{{ route('archivo_siniestros.libros.folios.index', $libro) }}">Ver todos / registrar</a></div></div>
            <div class="card-body table-responsive p-0"><table class="table table-sm table-striped mb-0"><thead><tr><th>Año</th><th>Folio</th><th>Página física</th><th>Verificación</th></tr></thead><tbody>
                @forelse($libro->folios->take(10) as $folio)<tr><td>{{ $folio->anio }}</td><td>{{ $folio->folio }}</td><td>{{ $folio->numero_pagina ?: '—' }}</td><td>{{ $folio->estado_verificacion }}</td></tr>@empty<tr><td colspan="4" class="text-center text-muted py-3">No hay folios indexados.</td></tr>@endforelse
            </tbody></table></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-outline card-success"><div class="card-header"><h3 class="card-title">Ubicación física</h3></div><div class="card-body">
            <span class="badge badge-{{ $libro->estado_fisico === 'almacenado' ? 'success' : ($libro->estado_fisico === 'en_consulta' ? 'warning' : 'secondary') }}">{{ str_replace('_',' ',ucfirst($libro->estado_fisico)) }}</span>
            <hr>
            <strong>Ubicación confirmada</strong><p>@if($libro->bloqueActual){{ $libro->bloqueActual->anaquel->area->nombre ?? '—' }}<br>{{ $libro->bloqueActual->anaquel->codigo ?? '—' }} / {{ $libro->bloqueActual->codigo }} / Posición {{ $libro->posicion_actual }}@else{{ $libro->areaActual->nombre ?? 'Sin confirmar' }}@endif</p>
            <strong>Destino planeado</strong><p>@if($libro->bloqueDestino){{ $libro->bloqueDestino->anaquel->area->nombre ?? '—' }}<br>{{ $libro->bloqueDestino->anaquel->codigo ?? '—' }} / {{ $libro->bloqueDestino->codigo }} / Reserva {{ $libro->posicion_destino }}@else No asignado @endif</p>
            <a href="{{ route('archivo_siniestros.mapa', ['bloque_id' => $libro->bloque_actual_id ?: $libro->bloque_destino_id]) }}" class="btn btn-outline-info btn-block"><i class="fa-solid fa-map-location-dot"></i> Mostrar en el mapa</a>
        </div></div>
        @can('gestionar archivo siniestros')
        <div class="card card-outline card-warning"><div class="card-header"><h3 class="card-title">Asignar ubicación prevista</h3></div><div class="card-body">
            <form method="POST" action="{{ route('archivo_siniestros.libros.asignar_destino', $libro) }}">@csrf
                <div class="form-group"><label for="bloque_destino_id">Bloque de destino *</label><select name="bloque_destino_id" id="bloque_destino_id" class="form-control" required><option value="">Seleccione</option>@foreach($bloques as $bloque)<option value="{{ $bloque->id }}" {{ (string) old('bloque_destino_id', $libro->bloque_destino_id) === (string) $bloque->id ? 'selected' : '' }}>{{ $bloque->anaquel->area->nombre ?? 'Área' }} / {{ $bloque->anaquel->codigo ?? 'Anaquel' }} / {{ $bloque->codigo }}</option>@endforeach</select></div>
                <div class="form-group"><label for="posicion_destino">Reserva (opcional)</label><input type="number" min="1" name="posicion_destino" id="posicion_destino" value="{{ old('posicion_destino') }}" class="form-control" placeholder="Asignación automática"></div>
                <button class="btn btn-warning btn-block">Asignar destino</button>
            </form>
        </div></div>
        @if(!$libro->bloque_actual_id && $libro->bloque_destino_id)
        <div class="card card-outline card-success"><div class="card-header"><h3 class="card-title">Confirmar ubicación / devolución</h3></div><div class="card-body">
            <form method="POST" action="{{ route($libro->estado_fisico === 'en_consulta' ? 'archivo_siniestros.libros.devolver' : 'archivo_siniestros.libros.confirmar_ubicacion', $libro) }}">@csrf
                <div class="form-group"><label for="bloque_qr_uuid_confirm">UUID leído del QR del bloque *</label><input name="bloque_qr_uuid" id="bloque_qr_uuid_confirm" class="form-control" maxlength="36" required placeholder="Escanea el QR de la repisa"></div>
                <div class="form-group"><label for="posicion_confirm">Posición (opcional)</label><input type="number" name="posicion" id="posicion_confirm" class="form-control" min="1" placeholder="Dejar orden predeterminado"></div>
                <button class="btn btn-success btn-block"><i class="fa-solid fa-check"></i> Confirmar con QR</button>
            </form>
        </div></div>
        @endif
        @if($libro->bloque_actual_id)
        <div class="card card-outline card-warning"><div class="card-header"><h3 class="card-title">Retirar para consulta</h3></div><div class="card-body">
            <form method="POST" action="{{ route('archivo_siniestros.libros.retirar', $libro) }}">@csrf
                <div class="form-group"><label for="area_destino_id">Área a la que se traslada *</label><select id="area_destino_id" name="area_destino_id" class="form-control" required><option value="">Seleccione</option>@foreach($areas as $area)<option value="{{ $area->id }}">{{ $area->nombre }}</option>@endforeach</select></div>
                <div class="form-group"><label for="motivo">Motivo *</label><textarea name="motivo" id="motivo" rows="2" maxlength="1000" class="form-control" required>{{ old('motivo') }}</textarea></div>
                <button class="btn btn-warning btn-block"><i class="fa-solid fa-arrow-right-from-bracket"></i> Registrar retiro</button>
            </form>
        </div></div>
        @endif
        <div class="card card-outline card-secondary"><div class="card-header"><h3 class="card-title">Reubicar con QR</h3></div><div class="card-body">
            <form method="POST" action="{{ route('archivo_siniestros.libros.reubicar', $libro) }}">@csrf
                <div class="form-group"><label for="bloque_reubicar">Nuevo bloque *</label><select name="bloque_destino_id" id="bloque_reubicar" class="form-control" required><option value="">Seleccione</option>@foreach($bloques as $bloque)<option value="{{ $bloque->id }}">{{ $bloque->anaquel->codigo ?? 'Anaquel' }} / {{ $bloque->codigo }}</option>@endforeach</select></div>
                <div class="form-group"><label for="bloque_qr_uuid_reubicar">UUID del QR físico *</label><input name="bloque_qr_uuid" id="bloque_qr_uuid_reubicar" class="form-control" maxlength="36" required></div>
                <div class="form-group"><label for="posicion_reubicar">Posición (opcional)</label><input type="number" name="posicion" id="posicion_reubicar" min="1" class="form-control"></div>
                <button class="btn btn-secondary btn-block">Confirmar reubicación</button>
            </form>
        </div></div>
        @endcan
        <div class="card card-outline card-info"><div class="card-body"><a class="btn btn-info btn-block" href="{{ route('archivo_siniestros.libros.movimientos.index', $libro) }}"><i class="fa-solid fa-clock-rotate-left"></i> Historial de movimientos</a></div></div>
    </div>
</div>
@stop
