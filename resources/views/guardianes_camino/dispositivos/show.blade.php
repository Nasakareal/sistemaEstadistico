@extends('adminlte::page')

@section('title', 'Detalle del Dispositivo Guardianes del Camino')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Detalle del Dispositivo</h1>
        <div>
            <a href="{{ route('guardianes_camino.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </a>

            <a href="javascript:void(0);"
               class="btn btn-success"
               onclick="compartirWhatsapp({{ $dispositivo->id }})">
                <i class="fa-brands fa-whatsapp"></i> Compartir
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
    @php
        $configDispositivos = config('guardianes_camino.dispositivos', []);

        $normalizar = function ($valor) {
            $valor = mb_strtoupper(trim((string) $valor), 'UTF-8');
            $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $valor);
            return $ascii !== false ? $ascii : $valor;
        };

        $nombreCatalogo = $dispositivo->catalogo->nombre ?? '';
        $nombreCatalogoNormalizado = $normalizar($nombreCatalogo);

        $configActual = null;
        foreach ($configDispositivos as $clave => $item) {
            if ($normalizar($clave) === $nombreCatalogoNormalizado) {
                $configActual = $item;
                break;
            }
        }

        $camposDinamicos = $configActual['campos'] ?? [];

        $labelsCampos = [
            'cantidad' => 'Cantidad',
            'vehiculos_inspeccionados' => 'Vehículos inspeccionados',
            'personas_inspeccionadas' => 'Personas inspeccionadas',
            'vehiculos_impactados' => 'Vehículos impactados',
            'personas_impactadas' => 'Personas impactadas',
            'estado_fuerza_participante' => 'Estado de fuerza participante',
            'crps_participantes' => 'CRPS participantes',
            'kilometros_recorridos' => 'Kilómetros recorridos',
            'acompanamientos' => 'Acompañamientos',
            'abanderamientos' => 'Abanderamientos',
            'auxilios_viales' => 'Auxilios viales',
            'prox_empresas' => 'Empresas',
            'prox_tiendas_conveniencia' => 'Tiendas de conveniencia',
            'prox_escuelas' => 'Escuelas',
            'prox_hospitales' => 'Hospitales',
            'puestas_disposicion' => 'Puestas a disposición',
            'vehiculos_recuperados' => 'Vehículos recuperados',
            'armas_aseguradas' => 'Armas aseguradas',
            'mercancia_recuperada' => 'Mercancía recuperada',
            'decomiso_drogas' => 'Decomiso de drogas',
            'antecedentes_personas' => 'Antecedentes personas',
            'antecedentes_vehiculos' => 'Antecedentes vehículos',
            'antecedentes_motos' => 'Antecedentes motos',
            'antecedentes_camiones' => 'Antecedentes camiones',
        ];

        $extras = [
            'puestas_disposicion',
            'vehiculos_recuperados',
            'armas_aseguradas',
            'mercancia_recuperada',
            'decomiso_drogas',
            'antecedentes_personas',
            'antecedentes_vehiculos',
            'antecedentes_motos',
            'antecedentes_camiones',
        ];

        $camposMostrar = array_values(array_unique(array_merge($camposDinamicos, $extras)));

        $tieneNarrativa = filled($dispositivo->narrativa) || filled($dispositivo->acciones_realizadas) || filled($dispositivo->frase_institucional);
        $tieneApoyoUsuario = filled($dispositivo->nombre_conductor) || filled($dispositivo->ocupacion_conductor) || filled($dispositivo->vehiculo_descripcion) || filled($dispositivo->placas_apoyado) || filled($dispositivo->procedencia) || filled($dispositivo->destino) || filled($dispositivo->motivo_apoyo) || (int) $dispositivo->acompanantes_cantidad > 0;
        $tieneResponsable = filled($dispositivo->cargo_responsable) || filled($dispositivo->nombre_responsable) || filled($dispositivo->elementos_participantes_texto);
        $tieneObservaciones = filled($dispositivo->observaciones);
        $tieneRelacionados = $dispositivo->vehiculos->isNotEmpty() || $dispositivo->personas->isNotEmpty();
    @endphp

    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">{{ $configActual['titulo'] ?? ($dispositivo->catalogo->nombre ?? 'Dispositivo') }}</h3>
                </div>

                <div class="card-body">
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
                                                <th style="width: 35%;">Tipo de dispositivo</th>
                                                <td>{{ $dispositivo->catalogo->nombre ?? 'Sin tipo' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Fecha</th>
                                                <td>{{ optional($dispositivo->fecha)->format('d-m-Y') }}</td>
                                            </tr>
                                            <tr>
                                                <th>Hora</th>
                                                <td>{{ $dispositivo->hora ?: 'No especificada' }}</td>
                                            </tr>
                                            @if(filled($dispositivo->hora_inicio))
                                                <tr>
                                                    <th>Hora inicio</th>
                                                    <td>{{ $dispositivo->hora_inicio }}</td>
                                                </tr>
                                            @endif
                                            @if(filled($dispositivo->hora_fin))
                                                <tr>
                                                    <th>Hora fin</th>
                                                    <td>{{ $dispositivo->hora_fin }}</td>
                                                </tr>
                                            @endif
                                            @if(filled($dispositivo->tipo_reporte))
                                                <tr>
                                                    <th>Tipo de reporte</th>
                                                    <td>{{ $dispositivo->tipo_reporte }}</td>
                                                </tr>
                                            @endif
                                            @if(filled($dispositivo->asunto))
                                                <tr>
                                                    <th>Asunto</th>
                                                    <td>{{ $dispositivo->asunto }}</td>
                                                </tr>
                                            @endif
                                            @if(filled($dispositivo->lugar))
                                                <tr>
                                                    <th>Lugar</th>
                                                    <td>{{ $dispositivo->lugar }}</td>
                                                </tr>
                                            @endif
                                            @if(filled($dispositivo->descripcion))
                                                <tr>
                                                    <th>Descripción</th>
                                                    <td>{{ $dispositivo->descripcion }}</td>
                                                </tr>
                                            @endif
                                            <tr>
                                                <th>Destacamento</th>
                                                <td>{{ $dispositivo->destacamento_nombre_snapshot ?? ($dispositivo->destacamento->nombre ?? 'Sin destacamento') }}</td>
                                            </tr>
                                            <tr>
                                                <th>Capturó</th>
                                                <td>{{ $dispositivo->usuario->name ?? 'Desconocido' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card bg-dark h-100">
                                <div class="card-header">
                                    <h3 class="card-title">Ubicación</h3>
                                </div>
                                <div class="card-body table-responsive p-0">
                                    <table class="table table-striped table-sm mb-0">
                                        <tbody>
                                            @if(filled($dispositivo->carretera))
                                                <tr>
                                                    <th style="width: 35%;">Carretera</th>
                                                    <td>{{ $dispositivo->carretera }}</td>
                                                </tr>
                                            @endif
                                            @if(filled($dispositivo->tramo))
                                                <tr>
                                                    <th>Tramo</th>
                                                    <td>{{ $dispositivo->tramo }}</td>
                                                </tr>
                                            @endif
                                            @if(filled($dispositivo->kilometro))
                                                <tr>
                                                    <th>Kilómetro</th>
                                                    <td>{{ $dispositivo->kilometro }}</td>
                                                </tr>
                                            @endif
                                            @if(filled($dispositivo->lat))
                                                <tr>
                                                    <th>Latitud</th>
                                                    <td>{{ $dispositivo->lat }}</td>
                                                </tr>
                                            @endif
                                            @if(filled($dispositivo->lng))
                                                <tr>
                                                    <th>Longitud</th>
                                                    <td>{{ $dispositivo->lng }}</td>
                                                </tr>
                                            @endif
                                            @if(filled($dispositivo->coordenadas_texto))
                                                <tr>
                                                    <th>Coordenadas</th>
                                                    <td>{{ $dispositivo->coordenadas_texto }}</td>
                                                </tr>
                                            @endif
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
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>

                    @if(count($camposMostrar))
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="card bg-dark">
                                    <div class="card-header">
                                        <h3 class="card-title">Resultados del dispositivo</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            @foreach($camposMostrar as $campo)
                                                @php
                                                    $valor = $dispositivo->{$campo} ?? null;
                                                    $mostrar = false;

                                                    if (is_numeric($valor)) {
                                                        $mostrar = (float) $valor > 0;
                                                    } elseif (filled($valor)) {
                                                        $mostrar = true;
                                                    }

                                                    if ($campo === 'cantidad' && $valor !== null) {
                                                        $mostrar = true;
                                                    }

                                                    if ($campo === 'kilometros_recorridos' && $valor !== null) {
                                                        $mostrar = true;
                                                    }
                                                @endphp

                                                @if($mostrar)
                                                    <div class="col-md-3 mb-3">
                                                        <div class="info-box bg-info">
                                                            <span class="info-box-icon">
                                                                <i class="fa-solid fa-chart-column"></i>
                                                            </span>
                                                            <div class="info-box-content">
                                                                <span class="info-box-text">{{ $labelsCampos[$campo] ?? $campo }}</span>
                                                                <span class="info-box-number">
                                                                    @if($campo === 'kilometros_recorridos')
                                                                        {{ number_format((float) $valor, 2) }}
                                                                    @else
                                                                        {{ $valor }}
                                                                    @endif
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($tieneRelacionados)
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="card bg-dark h-100">
                                    <div class="card-header">
                                        <h3 class="card-title">Vehículos relacionados</h3>
                                    </div>
                                    <div class="card-body table-responsive p-0">
                                        @if($dispositivo->vehiculos->count())
                                            <table class="table table-striped table-sm mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Vehículo</th>
                                                        <th>Placas</th>
                                                        <th>Rol</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($dispositivo->vehiculos as $vehiculo)
                                                        <tr>
                                                            <td>
                                                                {{ trim(collect([$vehiculo->marca, $vehiculo->linea, $vehiculo->tipo, $vehiculo->color])->filter()->implode(' ')) ?: 'Vehículo sin descripción' }}
                                                                @if(filled($vehiculo->pivot->observaciones))
                                                                    <br><small>{{ $vehiculo->pivot->observaciones }}</small>
                                                                @endif
                                                            </td>
                                                            <td>{{ $vehiculo->placas ?: 'Sin placas' }}</td>
                                                            <td>{{ $vehiculo->pivot->rol ?: 'Sin rol' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @else
                                            <div class="p-3">Sin vehículos relacionados.</div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card bg-dark h-100">
                                    <div class="card-header">
                                        <h3 class="card-title">Personas relacionadas</h3>
                                    </div>
                                    <div class="card-body table-responsive p-0">
                                        @if($dispositivo->personas->count())
                                            <table class="table table-striped table-sm mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Nombre</th>
                                                        <th>Participación</th>
                                                        <th>Teléfono</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($dispositivo->personas as $persona)
                                                        <tr>
                                                            <td>
                                                                {{ $persona->nombre }}
                                                                @if(filled($persona->observaciones))
                                                                    <br><small>{{ $persona->observaciones }}</small>
                                                                @endif
                                                            </td>
                                                            <td>{{ $persona->tipo_participacion ?: 'Sin tipo' }}</td>
                                                            <td>{{ $persona->telefono ?: 'N/D' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @else
                                            <div class="p-3">Sin personas relacionadas.</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($tieneNarrativa)
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="card bg-dark">
                                    <div class="card-header">
                                        <h3 class="card-title">Narrativa</h3>
                                    </div>
                                    <div class="card-body">
                                        @if(filled($dispositivo->narrativa))
                                            <p><strong>Narrativa:</strong></p>
                                            <div class="p-3 bg-secondary rounded mb-3" style="white-space: pre-line;">{{ $dispositivo->narrativa }}</div>
                                        @endif

                                        @if(filled($dispositivo->acciones_realizadas))
                                            <p><strong>Acciones realizadas:</strong></p>
                                            <div class="p-3 bg-secondary rounded mb-3" style="white-space: pre-line;">{{ $dispositivo->acciones_realizadas }}</div>
                                        @endif

                                        @if(filled($dispositivo->frase_institucional))
                                            <p><strong>Frase institucional:</strong></p>
                                            <div class="p-3 bg-secondary rounded" style="white-space: pre-line;">{{ $dispositivo->frase_institucional }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($tieneApoyoUsuario)
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="card bg-dark h-100">
                                    <div class="card-header">
                                        <h3 class="card-title">Apoyo a usuario</h3>
                                    </div>
                                    <div class="card-body table-responsive p-0">
                                        <table class="table table-striped table-sm mb-0">
                                            <tbody>
                                                @if(filled($dispositivo->nombre_conductor))
                                                    <tr>
                                                        <th style="width: 35%;">Nombre conductor</th>
                                                        <td>{{ $dispositivo->nombre_conductor }}</td>
                                                    </tr>
                                                @endif
                                                @if(filled($dispositivo->ocupacion_conductor))
                                                    <tr>
                                                        <th>Ocupación</th>
                                                        <td>{{ $dispositivo->ocupacion_conductor }}</td>
                                                    </tr>
                                                @endif
                                                @if((int) $dispositivo->acompanantes_cantidad > 0)
                                                    <tr>
                                                        <th>Acompañantes</th>
                                                        <td>{{ $dispositivo->acompanantes_cantidad }}</td>
                                                    </tr>
                                                @endif
                                                @if(filled($dispositivo->vehiculo_descripcion))
                                                    <tr>
                                                        <th>Vehículo</th>
                                                        <td>{{ $dispositivo->vehiculo_descripcion }}</td>
                                                    </tr>
                                                @endif
                                                @if(filled($dispositivo->placas_apoyado))
                                                    <tr>
                                                        <th>Placas</th>
                                                        <td>{{ $dispositivo->placas_apoyado }}</td>
                                                    </tr>
                                                @endif
                                                @if(filled($dispositivo->procedencia))
                                                    <tr>
                                                        <th>Procedencia</th>
                                                        <td>{{ $dispositivo->procedencia }}</td>
                                                    </tr>
                                                @endif
                                                @if(filled($dispositivo->destino))
                                                    <tr>
                                                        <th>Destino</th>
                                                        <td>{{ $dispositivo->destino }}</td>
                                                    </tr>
                                                @endif
                                                @if(filled($dispositivo->motivo_apoyo))
                                                    <tr>
                                                        <th>Motivo apoyo</th>
                                                        <td style="white-space: pre-line;">{{ $dispositivo->motivo_apoyo }}</td>
                                                    </tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            @if($tieneResponsable)
                                <div class="col-md-6">
                                    <div class="card bg-dark h-100">
                                        <div class="card-header">
                                            <h3 class="card-title">Responsable y personal</h3>
                                        </div>
                                        <div class="card-body table-responsive p-0">
                                            <table class="table table-striped table-sm mb-0">
                                                <tbody>
                                                    @if(filled($dispositivo->cargo_responsable))
                                                        <tr>
                                                            <th style="width: 35%;">Cargo responsable</th>
                                                            <td>{{ $dispositivo->cargo_responsable }}</td>
                                                        </tr>
                                                    @endif
                                                    @if(filled($dispositivo->nombre_responsable))
                                                        <tr>
                                                            <th>Nombre responsable</th>
                                                            <td>{{ $dispositivo->nombre_responsable }}</td>
                                                        </tr>
                                                    @endif
                                                    @if(filled($dispositivo->elementos_participantes_texto))
                                                        <tr>
                                                            <th>Elementos participantes</th>
                                                            <td style="white-space: pre-line;">{{ $dispositivo->elementos_participantes_texto }}</td>
                                                        </tr>
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @elseif($tieneResponsable)
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="card bg-dark">
                                    <div class="card-header">
                                        <h3 class="card-title">Responsable y personal</h3>
                                    </div>
                                    <div class="card-body table-responsive p-0">
                                        <table class="table table-striped table-sm mb-0">
                                            <tbody>
                                                @if(filled($dispositivo->cargo_responsable))
                                                    <tr>
                                                        <th style="width: 35%;">Cargo responsable</th>
                                                        <td>{{ $dispositivo->cargo_responsable }}</td>
                                                    </tr>
                                                @endif
                                                @if(filled($dispositivo->nombre_responsable))
                                                    <tr>
                                                        <th>Nombre responsable</th>
                                                        <td>{{ $dispositivo->nombre_responsable }}</td>
                                                    </tr>
                                                @endif
                                                @if(filled($dispositivo->elementos_participantes_texto))
                                                    <tr>
                                                        <th>Elementos participantes</th>
                                                        <td style="white-space: pre-line;">{{ $dispositivo->elementos_participantes_texto }}</td>
                                                    </tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($tieneObservaciones)
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="card bg-dark">
                                    <div class="card-header">
                                        <h3 class="card-title">Observaciones</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="p-3 bg-secondary rounded" style="white-space: pre-line;">
                                            {{ $dispositivo->observaciones }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

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
                                                            @if(filled($foto->nombre_original))
                                                                <p class="mb-1">
                                                                    <strong>Archivo:</strong><br>
                                                                    {{ $foto->nombre_original }}
                                                                </p>
                                                            @endif

                                                            @if(filled($foto->caption))
                                                                <p class="mb-1">
                                                                    <strong>Caption:</strong><br>
                                                                    {{ $foto->caption }}
                                                                </p>
                                                            @endif

                                                            @if(filled($foto->observaciones))
                                                                <p class="mb-1">
                                                                    <strong>Observaciones:</strong><br>
                                                                    {{ $foto->observaciones }}
                                                                </p>
                                                            @endif
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
    <script>
        function compartirWhatsapp(id) {
            let url = "{{ route('guardianes_camino.dispositivos.whatsapp', ':id') }}";
            url = url.replace(':id', id);

            fetch(url)
                .then(res => {
                    if (!res.ok) {
                        throw new Error('Error al generar el texto para WhatsApp');
                    }
                    return res.json();
                })
                .then(data => {
                    const texto = encodeURIComponent(data.text);
                    window.open(`https://wa.me/?text=${texto}`, '_blank');
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'No se pudo compartir',
                        text: error.message
                    });
                });
        }
    </script>
@stop
