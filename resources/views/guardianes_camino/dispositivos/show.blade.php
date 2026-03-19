@extends('adminlte::page')

@section('title', 'Detalle del Dispositivo Guardianes del Camino')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Detalle del Dispositivo</h1>
        <div>
            <a href="{{ route('guardianes_camino.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </a>

            @can('editar operativos carreteras')
                <a href="{{ route('guardianes_camino.dispositivos.edit', $dispositivo->id) }}" class="btn btn-success">
                    <i class="fa-solid fa-pen"></i> Editar
                </a>
            @endcan
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">

            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        {{ $dispositivo->catalogo->nombre ?? 'Dispositivo' }}
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-info p-2">
                            ID: {{ $dispositivo->id }}
                        </span>
                    </div>
                </div>

                <div class="card-body">

                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="alert alert-info mb-0">
                                <strong>Operativo:</strong> {{ $operativo->catalogo->nombre ?? 'Guardianes del Camino' }}
                            </div>
                        </div>
                    </div>

                    <div class="row">

                        <div class="col-md-6">
                            <div class="card bg-dark h-100">
                                <div class="card-header">
                                    <h3 class="card-title">Datos generales</h3>
                                </div>
                                <div class="card-body table-responsive p-0">
                                    <table class="table table-striped table-sm mb-0">
                                        <tbody>
                                            <tr>
                                                <th style="width: 35%;">ID</th>
                                                <td>{{ $dispositivo->id }}</td>
                                            </tr>
                                            <tr>
                                                <th>UUID cliente</th>
                                                <td>{{ $dispositivo->client_uuid ?? 'No disponible' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Tipo de dispositivo</th>
                                                <td>{{ $dispositivo->catalogo->nombre ?? 'Sin tipo' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Tipo de reporte</th>
                                                <td>{{ $dispositivo->tipo_reporte ?? 'No especificado' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Asunto</th>
                                                <td>{{ $dispositivo->asunto ?? 'No especificado' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Fecha</th>
                                                <td>{{ optional($dispositivo->fecha)->format('d-m-Y') }}</td>
                                            </tr>
                                            <tr>
                                                <th>Hora</th>
                                                <td>{{ $dispositivo->hora ?? 'No especificada' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Hora inicio</th>
                                                <td>{{ $dispositivo->hora_inicio ?? 'No especificada' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Hora fin</th>
                                                <td>{{ $dispositivo->hora_fin ?? 'No especificada' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Lugar</th>
                                                <td>{{ $dispositivo->lugar ?? 'No especificado' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Descripción breve</th>
                                                <td>{{ $dispositivo->descripcion ?? 'No especificada' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Destacamento</th>
                                                <td>{{ $dispositivo->destacamento_nombre_snapshot ?? ($dispositivo->destacamento->nombre ?? 'Sin destacamento') }}</td>
                                            </tr>
                                            <tr>
                                                <th>Capturó</th>
                                                <td>{{ $dispositivo->usuario->name ?? 'Desconocido' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Creado</th>
                                                <td>{{ optional($dispositivo->created_at)->format('d-m-Y H:i') }}</td>
                                            </tr>
                                            <tr>
                                                <th>Actualizado</th>
                                                <td>{{ optional($dispositivo->updated_at)->format('d-m-Y H:i') }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card bg-dark h-100">
                                <div class="card-header">
                                    <h3 class="card-title">Georreferencia y tramo</h3>
                                </div>
                                <div class="card-body table-responsive p-0">
                                    <table class="table table-striped table-sm mb-0">
                                        <tbody>
                                            <tr>
                                                <th style="width: 35%;">Carretera</th>
                                                <td>{{ $dispositivo->carretera ?? 'No especificada' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Tramo</th>
                                                <td>{{ $dispositivo->tramo ?? 'No especificado' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Kilómetro</th>
                                                <td>{{ $dispositivo->kilometro ?? 'No especificado' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Latitud</th>
                                                <td>{{ $dispositivo->lat ?? 'No especificada' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Longitud</th>
                                                <td>{{ $dispositivo->lng ?? 'No especificada' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Coordenadas texto</th>
                                                <td>{{ $dispositivo->coordenadas_texto ?? 'No especificadas' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Requiere evidencia</th>
                                                <td>
                                                    @if($dispositivo->requiere_evidencia)
                                                        <span class="badge badge-success">Sí</span>
                                                    @else
                                                        <span class="badge badge-secondary">No</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Compartido por WhatsApp</th>
                                                <td>
                                                    @if($dispositivo->compartido_whatsapp)
                                                        <span class="badge badge-success">Sí</span>
                                                        @if($dispositivo->compartido_whatsapp_at)
                                                            <br>
                                                            <small>{{ optional($dispositivo->compartido_whatsapp_at)->format('d-m-Y H:i') }}</small>
                                                        @endif
                                                    @else
                                                        <span class="badge badge-secondary">No</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Estado sync</th>
                                                <td>{{ $dispositivo->sync_status ?? 'No disponible' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Sincronizado</th>
                                                <td>{{ optional($dispositivo->synced_at)->format('d-m-Y H:i') ?? 'No disponible' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Error sync</th>
                                                <td>{{ $dispositivo->sync_error ?? 'Sin error' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="row mt-3">

                        <div class="col-md-12">
                            <div class="card bg-dark">
                                <div class="card-header">
                                    <h3 class="card-title">Resultados del dispositivo</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">

                                        <div class="col-md-3">
                                            <div class="info-box bg-info">
                                                <span class="info-box-icon"><i class="fa-solid fa-layer-group"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Cantidad</span>
                                                    <span class="info-box-number">{{ $dispositivo->cantidad ?? 0 }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="info-box bg-primary">
                                                <span class="info-box-icon"><i class="fa-solid fa-car"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Vehículos inspeccionados</span>
                                                    <span class="info-box-number">{{ $dispositivo->vehiculos_inspeccionados ?? 0 }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="info-box bg-primary">
                                                <span class="info-box-icon"><i class="fa-solid fa-users"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Personas inspeccionadas</span>
                                                    <span class="info-box-number">{{ $dispositivo->personas_inspeccionadas ?? 0 }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="info-box bg-secondary">
                                                <span class="info-box-icon"><i class="fa-solid fa-car-burst"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Vehículos impactados</span>
                                                    <span class="info-box-number">{{ $dispositivo->vehiculos_impactados ?? 0 }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="info-box bg-secondary">
                                                <span class="info-box-icon"><i class="fa-solid fa-user-injured"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Personas impactadas</span>
                                                    <span class="info-box-number">{{ $dispositivo->personas_impactadas ?? 0 }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="info-box bg-success">
                                                <span class="info-box-icon"><i class="fa-solid fa-shield-halved"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Estado de fuerza</span>
                                                    <span class="info-box-number">{{ $dispositivo->estado_fuerza_participante ?? 0 }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="info-box bg-warning">
                                                <span class="info-box-icon"><i class="fa-solid fa-road"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Km recorridos</span>
                                                    <span class="info-box-number">{{ number_format((float) ($dispositivo->kilometros_recorridos ?? 0), 2) }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="info-box bg-dark">
                                                <span class="info-box-icon"><i class="fa-solid fa-truck-fast"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">CRPS participantes</span>
                                                    <span class="info-box-number" style="font-size: 14px;">{{ $dispositivo->crps_participantes ?? 'N/D' }}</span>
                                                </div>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="row mt-2">
                                        <div class="col-md-3">
                                            <strong>Acompañamientos:</strong> {{ $dispositivo->acompanamientos ?? 0 }}
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Abanderamientos:</strong> {{ $dispositivo->abanderamientos ?? 0 }}
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Auxilios viales:</strong> {{ $dispositivo->auxilios_viales ?? 0 }}
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Puestas a disposición:</strong> {{ $dispositivo->puestas_disposicion ?? 0 }}
                                        </div>
                                    </div>

                                    <div class="row mt-2">
                                        <div class="col-md-3">
                                            <strong>Empresas:</strong> {{ $dispositivo->prox_empresas ?? 0 }}
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Tiendas conveniencia:</strong> {{ $dispositivo->prox_tiendas_conveniencia ?? 0 }}
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Escuelas:</strong> {{ $dispositivo->prox_escuelas ?? 0 }}
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Hospitales:</strong> {{ $dispositivo->prox_hospitales ?? 0 }}
                                        </div>
                                    </div>

                                    <div class="row mt-2">
                                        <div class="col-md-3">
                                            <strong>Antecedentes personas:</strong> {{ $dispositivo->antecedentes_personas ?? 0 }}
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Antecedentes vehículos:</strong> {{ $dispositivo->antecedentes_vehiculos ?? 0 }}
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Antecedentes motos:</strong> {{ $dispositivo->antecedentes_motos ?? 0 }}
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Antecedentes camiones:</strong> {{ $dispositivo->antecedentes_camiones ?? 0 }}
                                        </div>
                                    </div>

                                    <div class="row mt-2">
                                        <div class="col-md-3">
                                            <strong>Vehículos recuperados:</strong> {{ $dispositivo->vehiculos_recuperados ?? 0 }}
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Armas aseguradas:</strong> {{ $dispositivo->armas_aseguradas ?? 0 }}
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Mercancía recuperada:</strong> {{ $dispositivo->mercancia_recuperada ?? 0 }}
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Decomiso drogas:</strong> {{ $dispositivo->decomiso_drogas ?? 0 }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="row mt-3">

                        <div class="col-md-12">
                            <div class="card bg-dark">
                                <div class="card-header">
                                    <h3 class="card-title">Narrativa</h3>
                                </div>
                                <div class="card-body">
                                    <p><strong>Narrativa:</strong></p>
                                    <div class="p-3 bg-secondary rounded mb-3" style="white-space: pre-line;">{{ $dispositivo->narrativa ?? 'No especificada' }}</div>

                                    <p><strong>Acciones realizadas:</strong></p>
                                    <div class="p-3 bg-secondary rounded mb-3" style="white-space: pre-line;">{{ $dispositivo->acciones_realizadas ?? 'No especificadas' }}</div>

                                    <p><strong>Frase institucional:</strong></p>
                                    <div class="p-3 bg-secondary rounded" style="white-space: pre-line;">{{ $dispositivo->frase_institucional ?? 'No especificada' }}</div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="row mt-3">

                        <div class="col-md-6">
                            <div class="card bg-dark h-100">
                                <div class="card-header">
                                    <h3 class="card-title">Apoyo a usuario</h3>
                                </div>
                                <div class="card-body table-responsive p-0">
                                    <table class="table table-striped table-sm mb-0">
                                        <tbody>
                                            <tr>
                                                <th style="width: 35%;">Nombre conductor</th>
                                                <td>{{ $dispositivo->nombre_conductor ?? 'No especificado' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Ocupación</th>
                                                <td>{{ $dispositivo->ocupacion_conductor ?? 'No especificada' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Acompañantes</th>
                                                <td>{{ $dispositivo->acompanantes_cantidad ?? 0 }}</td>
                                            </tr>
                                            <tr>
                                                <th>Vehículo</th>
                                                <td>{{ $dispositivo->vehiculo_descripcion ?? 'No especificado' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Placas</th>
                                                <td>{{ $dispositivo->placas_apoyado ?? 'No especificadas' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Procedencia</th>
                                                <td>{{ $dispositivo->procedencia ?? 'No especificada' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Destino</th>
                                                <td>{{ $dispositivo->destino ?? 'No especificado' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Motivo apoyo</th>
                                                <td style="white-space: pre-line;">{{ $dispositivo->motivo_apoyo ?? 'No especificado' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card bg-dark h-100">
                                <div class="card-header">
                                    <h3 class="card-title">Responsable y personal</h3>
                                </div>
                                <div class="card-body table-responsive p-0">
                                    <table class="table table-striped table-sm mb-0">
                                        <tbody>
                                            <tr>
                                                <th style="width: 35%;">Cargo responsable</th>
                                                <td>{{ $dispositivo->cargo_responsable ?? 'No especificado' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Nombre responsable</th>
                                                <td>{{ $dispositivo->nombre_responsable ?? 'No especificado' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Elementos participantes</th>
                                                <td style="white-space: pre-line;">{{ $dispositivo->elementos_participantes_texto ?? 'No especificados' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="row mt-3">

                        <div class="col-md-12">
                            <div class="card bg-dark">
                                <div class="card-header">
                                    <h3 class="card-title">Observaciones</h3>
                                </div>
                                <div class="card-body">
                                    <div class="p-3 bg-secondary rounded" style="white-space: pre-line;">
                                        {{ $dispositivo->observaciones ?? 'Sin observaciones' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="row mt-3">

                        <div class="col-md-12">
                            <div class="card card-outline card-info">
                                <div class="card-header">
                                    <h3 class="card-title">Evidencia fotográfica</h3>
                                    <div class="card-tools">
                                        <span class="badge badge-info p-2">
                                            {{ $dispositivo->fotos->count() }} foto(s)
                                        </span>
                                    </div>
                                </div>

                                <div class="card-body">
                                    @if($dispositivo->fotos->count())
                                        <div class="row">
                                            @foreach($dispositivo->fotos as $foto)
                                                <div class="col-md-4 mb-4">
                                                    <div class="card bg-dark h-100">
                                                        @if($foto->ruta)
                                                            <a href="{{ asset('storage/' . $foto->ruta) }}" target="_blank">
                                                                <img
                                                                    src="{{ asset('storage/' . $foto->ruta) }}"
                                                                    alt="Foto {{ $foto->id }}"
                                                                    class="card-img-top"
                                                                    style="height: 260px; object-fit: cover;"
                                                                >
                                                            </a>
                                                        @endif

                                                        <div class="card-body">
                                                            <p class="mb-1">
                                                                <strong>Archivo:</strong><br>
                                                                {{ $foto->nombre_original ?? 'Sin nombre' }}
                                                            </p>

                                                            <p class="mb-1">
                                                                <strong>Caption:</strong><br>
                                                                {{ $foto->caption ?? 'Sin caption' }}
                                                            </p>

                                                            <p class="mb-1">
                                                                <strong>Observaciones:</strong><br>
                                                                {{ $foto->observaciones ?? 'Sin observaciones' }}
                                                            </p>

                                                            <p class="mb-1">
                                                                <strong>Portada:</strong>
                                                                @if($foto->es_portada)
                                                                    <span class="badge badge-success">Sí</span>
                                                                @else
                                                                    <span class="badge badge-secondary">No</span>
                                                                @endif
                                                            </p>

                                                            <p class="mb-1">
                                                                <strong>Incluida en compartido:</strong>
                                                                @if($foto->incluida_en_compartido)
                                                                    <span class="badge badge-success">Sí</span>
                                                                @else
                                                                    <span class="badge badge-secondary">No</span>
                                                                @endif
                                                            </p>

                                                            <p class="mb-1">
                                                                <strong>Orden:</strong> {{ $foto->orden ?? 0 }}
                                                            </p>

                                                            <p class="mb-1">
                                                                <strong>Tomada:</strong>
                                                                {{ optional($foto->tomada_en)->format('d-m-Y H:i') ?? 'No disponible' }}
                                                            </p>
                                                        </div>

                                                        <div class="card-footer text-center">
                                                            <a href="{{ asset('storage/' . $foto->ruta) }}" target="_blank" class="btn btn-info btn-sm">
                                                                <i class="fa-solid fa-image"></i> Ver completa
                                                            </a>

                                                            @can('editar operativos carreteras')
                                                                <form action="{{ route('guardianes_camino.dispositivos.fotos.destroy', [$dispositivo->id, $foto->id]) }}" method="POST" style="display:inline-block;">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar esta foto?')">
                                                                        <i class="fa-solid fa-trash"></i>
                                                                    </button>
                                                                </form>
                                                            @endcan
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="alert alert-secondary mb-0">
                                            No hay fotos registradas para este dispositivo.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

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
            vertical-align: middle;
        }

        .card.bg-dark {
            border-radius: 14px;
        }

        .info-box {
            border-radius: 12px;
        }
    </style>
@stop

@section('js')
    @if (session('success'))
        <script>
            Swal.fire({
                position: 'center',
                icon: 'success',
                title: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 2500
            });
        </script>
    @endif

    @if (session('error'))
        <script>
            Swal.fire({
                position: 'center',
                icon: 'error',
                title: '{{ session('error') }}',
                showConfirmButton: true
            });
        </script>
    @endif
@stop
