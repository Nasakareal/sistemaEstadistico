@extends('adminlte::page')

@section('title', 'Detalle de Actividad')

@section('content_header')
    <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
        <h1 class="mb-0">Detalle de Actividad</h1>

        <div class="btn-group">
            <button type="button" class="btn btn-secondary" onclick="compartirActividad({{ $actividad->id }})">
                <i class="fa-solid fa-share-nodes"></i> Compartir
            </button>

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
        $fotosActividad = $actividad->relationLoaded('fotos')
            ? $actividad->fotos
            : $actividad->fotos()->orderBy('orden')->orderBy('id')->get();

        if ($fotosActividad->isEmpty() && (!empty($actividad->foto_path) || !empty($actividad->foto_thumbnail_path))) {
            $fotosActividad = collect([
                (object) [
                    'id' => 'legacy',
                    'foto_path' => $actividad->foto_path,
                    'foto_thumbnail_path' => $actividad->foto_thumbnail_path,
                    'foto_archivo_zip_path' => $actividad->foto_archivo_zip_path,
                    'foto_archivada_at' => $actividad->foto_archivada_at,
                    'foto_nombre_original' => $actividad->foto_nombre_original,
                    'foto_hash' => $actividad->foto_hash,
                    'orden' => 0,
                ]
            ]);
        }

        $vehiculosActividad = $actividad->relationLoaded('vehiculos')
            ? $actividad->vehiculos
            : $actividad->vehiculos()->orderBy('vehiculos.id')->get();

        $detalleFomento = $actividad->relationLoaded('fomentoCulturaVialDetalle')
            ? $actividad->fomentoCulturaVialDetalle
            : $actividad->fomentoCulturaVialDetalle()->first();
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
                            <label class="help-muted d-block">KM recorridos</label>
                            <div class="form-control-like">
                                @if(!is_null($actividad->km_recorridos))
                                    {{ number_format($actividad->km_recorridos, 2) }} km
                                @else
                                    —
                                @endif
                            </div>
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

            @if ($detalleFomento)
                <div class="card card-outline card-info mb-4">
                    <div class="card-header">
                        <h3 class="card-title">Fomento a la Cultura Vial</h3>
                    </div>

                    <div class="card-body">
                        <div class="row" style="row-gap:18px;">
                            <div class="col-md-12">
                                <label class="help-muted d-block">Programa / taller / campaña</label>
                                <div class="form-control-like">{{ $detalleFomento->programa_nombre ?? optional($detalleFomento->programa)->nombre ?? '—' }}</div>
                            </div>

                            <div class="col-md-6">
                                <label class="help-muted d-block">Nombre (escuela, empresa)</label>
                                <div class="form-control-like text-wrap-block">{{ $detalleFomento->nombre_institucion ?? '—' }}</div>
                            </div>

                            <div class="col-md-6">
                                <label class="help-muted d-block">Domicilio</label>
                                <div class="form-control-like text-wrap-block">{{ $detalleFomento->domicilio ?? '—' }}</div>
                            </div>

                            <div class="col-md-6">
                                <label class="help-muted d-block">Nivel educativo</label>
                                <div class="form-control-like">{{ $detalleFomento->nivel_educativo ?? '—' }}</div>
                            </div>

                            <div class="col-md-6">
                                <label class="help-muted d-block">Sector</label>
                                <div class="form-control-like">{{ $detalleFomento->sector ?? '—' }}</div>
                            </div>

                            @foreach ([
                                'ninas' => 'Niñas',
                                'ninos' => 'Niños',
                                'adolescentes_mujeres' => 'Adolescentes mujeres',
                                'adolescentes_hombres' => 'Adolescentes hombres',
                                'docentes_hombres' => 'Docentes hombres',
                                'docentes_mujeres' => 'Docentes mujeres',
                                'hombres' => 'Hombres',
                                'mujeres' => 'Mujeres',
                                'total_poblacion_atendida' => 'Total población atendida',
                            ] as $campo => $label)
                                <div class="col-md-3">
                                    <label class="help-muted d-block">{{ $label }}</label>
                                    <div class="form-control-like">{{ (int) ($detalleFomento->{$campo} ?? 0) }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

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

            <div class="card card-outline card-info mb-4 actividad-vehiculos-card">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
                    <div>
                        <h3 class="card-title mb-0">
                            <i class="fa-solid fa-car-side"></i> Vehículos relacionados
                        </h3>
                        <div class="help-muted mt-1">Datos del vehículo vinculados a esta actividad.</div>
                    </div>

                    <div class="d-flex align-items-center" style="gap:8px; flex-wrap:wrap;">
                        <span class="badge badge-light vehiculo-total-badge">
                            Total: {{ $vehiculosActividad->count() }}
                        </span>
                    </div>
                </div>

                <div class="card-body">
                    @if ($vehiculosActividad->count())
                        <div class="vehiculos-grid">
                            @foreach ($vehiculosActividad as $vehiculo)
                                @php
                                    $placas = trim((string) ($vehiculo->placas ?? ''));
                                    $placasFmt = $placas !== '' ? $placas : 'SIN PLACAS';
                                    $marcaLinea = trim(collect([$vehiculo->marca ?? null, $vehiculo->linea ?? null])->filter()->implode(' '));
                                    $marcaLinea = $marcaLinea !== '' ? $marcaLinea : 'VEHÍCULO';
                                    $modelo = trim((string) ($vehiculo->modelo ?? ''));
                                    $tipo = trim((string) ($vehiculo->tipo ?? ''));
                                    $color = trim((string) ($vehiculo->color ?? ''));
                                    $servicio = trim((string) ($vehiculo->tipo_servicio ?? ''));
                                    $grua = trim((string) ($vehiculo->grua ?? ''));
                                    $corralon = trim((string) ($vehiculo->corralon ?? ''));
                                    $aseguradora = trim((string) ($vehiculo->aseguradora ?? ''));
                                    $serie = trim((string) ($vehiculo->serie ?? ''));
                                @endphp

                                <div class="vehiculo-card">
                                    <div class="vehiculo-card-head">
                                        <div class="min-w-0">
                                            <div class="vehiculo-title text-truncate">{{ $marcaLinea }}</div>
                                            <div class="vehiculo-subtitle">
                                                {{ $modelo !== '' ? 'Modelo ' . $modelo : 'Modelo no especificado' }}
                                            </div>
                                        </div>

                                        <span class="vehiculo-placa">
                                            <i class="fa-solid fa-id-card"></i> {{ $placasFmt }}
                                        </span>
                                    </div>

                                    <div class="vehiculo-card-body">
                                        <div class="vehiculo-chip-row">
                                            <span class="vehiculo-chip">
                                                <i class="fa-solid fa-car-rear"></i> {{ $tipo !== '' ? $tipo : 'Tipo N/D' }}
                                            </span>
                                            <span class="vehiculo-chip">
                                                <i class="fa-solid fa-palette"></i> {{ $color !== '' ? $color : 'Color N/D' }}
                                            </span>
                                            <span class="vehiculo-chip">
                                                <i class="fa-solid fa-user-group"></i> {{ (int) ($vehiculo->capacidad_personas ?? 0) }}
                                            </span>
                                        </div>

                                        <div class="vehiculo-mini-grid">
                                            <div>
                                                <span>Servicio</span>
                                                <strong>{{ $servicio !== '' ? $servicio : '—' }}</strong>
                                            </div>
                                            <div>
                                                <span>Serie</span>
                                                <strong>{{ $serie !== '' ? $serie : '—' }}</strong>
                                            </div>
                                            <div>
                                                <span>Grúa</span>
                                                <strong>{{ $grua !== '' ? $grua : '—' }}</strong>
                                            </div>
                                            <div>
                                                <span>Corralón</span>
                                                <strong>{{ $corralon !== '' ? $corralon : '—' }}</strong>
                                            </div>
                                            <div>
                                                <span>Aseguradora</span>
                                                <strong>{{ $aseguradora !== '' ? $aseguradora : '—' }}</strong>
                                            </div>
                                            <div>
                                                <span>Daños</span>
                                                <strong>
                                                    {{ $vehiculo->monto_danos !== null ? '$' . number_format((float) $vehiculo->monto_danos, 2) : '—' }}
                                                </strong>
                                            </div>
                                        </div>

                                        @if(!empty($vehiculo->partes_danadas))
                                            <div class="vehiculo-nota">
                                                <span>Partes dañadas</span>
                                                <p>{{ $vehiculo->partes_danadas }}</p>
                                            </div>
                                        @endif

                                        <div class="vehiculo-card-actions">
                                            <span class="badge {{ $vehiculo->antecedente_vehiculo ? 'badge-danger' : 'badge-success' }}">
                                                Antecedente: {{ $vehiculo->antecedente_vehiculo ? 'SÍ' : 'NO' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-info mb-0">
                            No hay vehículos vinculados a esta actividad.
                        </div>
                    @endif
                </div>
            </div>

            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Fotos</h3>
                </div>

                <div class="card-body">
                    @if ($fotosActividad->count())
                        <div class="foto-grid">
                            @foreach ($fotosActividad as $foto)
                                @php
                                    $fotoArchivada = !empty($foto->foto_archivo_zip_path) || !empty($foto->foto_archivada_at);
                                    $fotoPath = !$fotoArchivada && (!empty($foto->foto_path) || !empty($foto->foto_blob_path))
                                        ? $foto->foto_path
                                        : (($foto->foto_thumbnail_path ?? null) ?: $foto->foto_path);
                                    $fotoId = is_numeric($foto->id ?? null) ? $foto->id : null;
                                    $fotoUrl = $fotoId
                                        ? route('actividades.fotos.archivo', [$fotoId, 'original'])
                                        : route('actividades.fotos.principal_archivo', [$actividad->id, 'original']);
                                    $fotoPreviewUrl = $fotoId
                                        ? route('actividades.fotos.archivo', [$fotoId, 'thumbnail'])
                                        : route('actividades.fotos.principal_archivo', [$actividad->id, 'thumbnail']);
                                    $fotoNombre = $foto->foto_nombre_original ?: ('Foto ' . ($loop->iteration));
                                @endphp

                                <div class="foto-card">
                                    <a href="{{ $fotoUrl }}" target="_blank" rel="noopener">
                                        <img src="{{ $fotoPreviewUrl }}" alt="{{ $fotoNombre }}" class="foto-big">
                                    </a>

                                    <div class="row mt-3" style="row-gap:18px;">
                                        <div class="col-md-6">
                                            <label class="help-muted d-block">Archivo original</label>
                                            <div class="form-control-like text-wrap-block">{{ $fotoNombre }}</div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="help-muted d-block">Hash de foto</label>
                                            <div class="form-control-like text-wrap-block">{{ $foto->foto_hash ?? '—' }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-warning mb-0">
                            No hay fotos registradas para esta actividad.
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

        .vehiculo-total-badge {
            font-size: .9rem;
            padding: .4rem .6rem;
        }

        .vehiculos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 14px;
        }

        .vehiculo-card {
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 12px;
            overflow: hidden;
            background: rgba(255,255,255,.04);
            box-shadow: 0 10px 24px rgba(0,0,0,.18);
        }

        .vehiculo-card-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 12px;
            border-bottom: 1px solid rgba(255,255,255,.10);
            background: rgba(255,255,255,.05);
        }

        .min-w-0 {
            min-width: 0;
        }

        .vehiculo-title {
            color: #eaf0ff;
            font-weight: 800;
            font-size: 1rem;
            line-height: 1.15;
        }

        .vehiculo-subtitle {
            color: rgba(234,240,255,.68);
            font-weight: 600;
            font-size: .86rem;
            margin-top: 4px;
        }

        .vehiculo-placa {
            color: #0f172a;
            background: #f8fafc;
            border-radius: 8px;
            padding: .35rem .55rem;
            font-weight: 800;
            font-size: .82rem;
            white-space: nowrap;
        }

        .vehiculo-card-body {
            padding: 12px;
        }

        .vehiculo-chip-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 10px;
        }

        .vehiculo-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #eaf0ff;
            background: rgba(255,255,255,.07);
            border: 1px solid rgba(255,255,255,.10);
            border-radius: 8px;
            padding: .35rem .55rem;
            font-weight: 700;
            font-size: .84rem;
        }

        .vehiculo-mini-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .vehiculo-mini-grid div,
        .vehiculo-nota {
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,.10);
            background: rgba(0,0,0,.14);
            padding: 8px 10px;
            min-width: 0;
        }

        .vehiculo-mini-grid span,
        .vehiculo-nota span {
            display: block;
            color: rgba(234,240,255,.62);
            font-size: .76rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .vehiculo-mini-grid strong {
            display: block;
            color: #eaf0ff;
            font-size: .9rem;
            line-height: 1.25;
            word-break: break-word;
        }

        .vehiculo-nota {
            margin-top: 8px;
        }

        .vehiculo-nota p {
            color: #eaf0ff;
            margin: 4px 0 0;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .vehiculo-card-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 12px;
        }


        .text-wrap-block {
            white-space: pre-wrap;
            word-break: break-word;
            align-items: flex-start;
        }

        .foto-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
        }

        .foto-card {
            padding: 12px;
            border: 1px solid rgba(255,255,255,.10);
            border-radius: 16px;
            background: rgba(255,255,255,.03);
        }

        .foto-big {
            width: 100%;
            max-width: 900px;
            height: auto;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,.16);
            background: rgba(255,255,255,.06);
            display: block;
            margin: 0 auto;
        }
    </style>
@stop

@section('js')
    <script>
        async function compartirActividad(id) {
            try {
                const res = await fetch(`{{ url('actividades') }}/${id}/compartir`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!res.ok) {
                    throw new Error('No se pudo obtener la información para compartir');
                }

                const data = await res.json();

                if (!data.texto) {
                    throw new Error('No hay texto para compartir');
                }

                if (navigator.share) {
                    if (Array.isArray(data.fotos) && data.fotos.length > 0) {
                        try {
                            const files = [];

                            for (const [index, fotoUrl] of data.fotos.entries()) {
                                const responseFoto = await fetch(fotoUrl);
                                const blob = await responseFoto.blob();
                                const extension = blob.type === 'image/png'
                                    ? 'png'
                                    : (blob.type === 'image/webp' ? 'webp' : 'jpg');

                                files.push(new File([blob], `actividad_${id}_${index + 1}.${extension}`, { type: blob.type }));
                            }

                            if (files.length > 0 && navigator.canShare && navigator.canShare({ files })) {
                                await navigator.share({
                                    text: data.texto,
                                    files: files
                                });
                                return;
                            }
                        } catch (e) {
                        }

                        await navigator.share({
                            text: data.texto
                        });
                        return;
                    }

                    await navigator.share({
                        text: data.texto
                    });
                    return;
                }

                window.open(`https://wa.me/?text=${encodeURIComponent(data.texto)}`, '_blank');
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'No se pudo compartir la actividad.'
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('form-eliminar-actividad');

            if (form) {
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
            }

            @if (session('success'))
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        position: 'center',
                        icon: 'success',
                        title: '{{ session('success') }}',
                        showConfirmButton: false,
                        timer: 2500
                    });
                }
            @endif
        });
    </script>
@stop
