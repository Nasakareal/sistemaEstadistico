@extends('adminlte::page')

@section('title', 'Detalle de Personal')

@section('content_header')
    <h1>Detalle del Elemento</h1>
@stop

@section('content')
    @php
        $nombreCompleto = $personal->nombre_completo;
        $fotosPersonal = $personal->fotos ?? collect();
        $fotoActualModelo = $personal->foto && $fotosPersonal
            ? $fotosPersonal->firstWhere('ruta', $personal->foto)
            : null;
        $fotoActualModelo = $fotoActualModelo ?: $personal->fotoPrincipal;
        $fotoActual = $personal->foto ?: optional($fotoActualModelo)->ruta;
        $fotoActualUrl = $fotoActualModelo
            ? route('personal.fotos.show', [$personal->id, $fotoActualModelo->id])
            : ($fotoActual ? route('personal.fotos.principal', $personal->id) : null);
        $asignacionesActivas = $personal->asignaciones ? $personal->asignaciones->whereNull('fecha_fin') : collect();
        $documentosPersonal = $personal->documentos ?? collect();
        $comprobanteEstudios = $documentosPersonal
            ->first(fn ($documento) => optional($documento->documentoTipo)->clave === 'COMPROBANTE_ESTUDIOS');
        $licenciasPersonal = $personal->licencias ?? collect();
        $documentoTipos = $documentoTipos ?? collect();
        $documentoTipoSeleccionadoId = old('documento_tipo_id');
        $documentoTipoSeleccionado = $documentoTipoSeleccionadoId !== null && $documentoTipoSeleccionadoId !== ''
            ? $documentoTipos->first(fn ($tipo) => (string) $tipo->id === (string) $documentoTipoSeleccionadoId)
            : null;
        $mostrarCamposOficioDocumento = $documentoTipos->isEmpty()
            || optional($documentoTipoSeleccionado)->clave === 'OFICIOS_PERSONAL';
        $tiposLicencia = $tiposLicencia ?? \App\Models\PersonalLicencia::tipos();
    @endphp

    <div class="row">
        <div class="col-md-12">

            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Información general</h3>
                    <div class="card-tools">
                        <a href="{{ route('personal.edit', $personal->id) }}" class="btn btn-success btn-sm">
                            <i class="fa-regular fa-pen-to-square"></i> Editar
                        </a>
                        <a href="{{ route('personal.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            @if($fotoActualUrl)
                                <img src="{{ $fotoActualUrl }}" alt="Foto de {{ $nombreCompleto }}" class="img-fluid rounded personal-photo">
                            @else
                                <div class="personal-photo-placeholder">
                                    Sin foto
                                </div>
                            @endif
                        </div>

                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <strong>Nombre completo:</strong><br>
                                    {{ $nombreCompleto ?: 'N/A' }}
                                </div>

                                <div class="col-md-4 mb-3">
                                    <strong>Número de empleado:</strong><br>
                                    {{ $personal->numero_empleado ?? 'N/A' }}
                                </div>

                                <div class="col-md-4 mb-3">
                                    <strong>Número de placa:</strong><br>
                                    {{ $personal->numero_placa ?? 'N/A' }}
                                </div>

                                <div class="col-md-4 mb-3">
                                    <strong>Usuario del sistema:</strong><br>
                                    @if($personal->user)
                                        {{ $personal->user->name }}{{ $personal->user->email ? ' - ' . $personal->user->email : '' }}
                                    @else
                                        <span class="text-muted">Sin usuario</span>
                                    @endif
                                </div>

                                <div class="col-md-4 mb-3">
                                    <strong>Unidad:</strong><br>
                                    {{ $personal->unidad->nombre ?? 'N/A' }}
                                </div>

                                <div class="col-md-4 mb-3">
                                    <strong>Destacamento:</strong><br>
                                    {{ optional($personal->destacamento)->nombre ?? 'Sin asignar' }}
                                </div>

                                <div class="col-md-4 mb-3">
                                    <strong>Turno:</strong><br>
                                    {{ $personal->turno->nombre ?? 'N/A' }}
                                </div>

                                <div class="col-md-4 mb-3">
                                    <strong>Patrulla:</strong><br>
                                    {{ $personal->patrulla->numero_economico ?? 'Sin asignar' }}
                                </div>

                                <div class="col-md-3 mb-3">
                                    <strong>Grado:</strong><br>
                                    {{ $personal->grado ?? 'N/A' }}
                                </div>

                                <div class="col-md-3 mb-3">
                                    <strong>Puesto:</strong><br>
                                    {{ $personal->puesto ?? 'N/A' }}
                                </div>

                                <div class="col-md-3 mb-3">
                                    <strong>Categoría:</strong><br>
                                    {{ $personal->categoria ?? 'N/A' }}
                                </div>

                                <div class="col-md-3 mb-3">
                                    <strong>Estatus:</strong><br>
                                    <span class="badge badge-{{ ($personal->estatus ?? '') === 'ACTIVO' ? 'success' : 'secondary' }}">
                                        {{ $personal->estatus ?? 'N/A' }}
                                    </span>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <strong>Adscripción:</strong><br>
                                    {{ $personal->adscripcion ?? 'N/A' }}
                                </div>

                                <div class="col-md-4 mb-3">
                                    <strong>Área:</strong><br>
                                    {{ $personal->area ?? 'N/A' }}
                                </div>

                                <div class="col-md-3 mb-3">
                                    <strong>CUIP:</strong><br>
                                    {{ $personal->cuip ?? 'N/A' }}
                                </div>

                                <div class="col-md-3 mb-3">
                                    <strong>CUP:</strong><br>
                                    {{ $personal->cup ?? 'N/A' }}
                                </div>

                                <div class="col-md-3 mb-3">
                                    <strong>CURP:</strong><br>
                                    {{ $personal->curp ?? 'N/A' }}
                                </div>

                                <div class="col-md-3 mb-3">
                                    <strong>RFC:</strong><br>
                                    {{ $personal->rfc ?? 'N/A' }}
                                </div>

                                <div class="col-md-3 mb-3">
                                    <strong>Fecha de nacimiento:</strong><br>
                                    {{ $personal->fecha_nacimiento ? $personal->fecha_nacimiento->format('d-m-Y') : 'N/A' }}
                                </div>

                                <div class="col-md-3 mb-3">
                                    <strong>Tipo de sangre:</strong><br>
                                    {{ $personal->tipoSangreLabel() ?: 'N/A' }}
                                </div>

                                <div class="col-md-3 mb-3">
                                    <strong>Número de Seguro Social:</strong><br>
                                    {{ $personal->numero_seguro_social ?? 'N/A' }}
                                </div>

                                <div class="col-md-3 mb-3">
                                    <strong>Correo electrónico:</strong><br>
                                    @if($personal->correo_electronico)
                                        <a href="mailto:{{ $personal->correo_electronico }}">{{ $personal->correo_electronico }}</a>
                                    @else
                                        N/A
                                    @endif
                                </div>

                                <div class="col-md-3 mb-3">
                                    <strong>Último grado de estudios:</strong><br>
                                    {{ $personal->ultimoGradoEstudiosLabel() ?: 'N/A' }}
                                    @if($comprobanteEstudios && $comprobanteEstudios->archivo_path)
                                        <br>
                                        <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('personal.documentos.signed', now()->addMinutes(30), ['documento' => $comprobanteEstudios->id, 'archivo' => 'general']) }}">
                                            <i class="fa-solid fa-file-pdf"></i> Descargar comprobante
                                        </a>
                                    @endif
                                </div>

                                <div class="col-md-6 mb-3">
                                    <strong>Alergias:</strong><br>
                                    {{ $personal->alergiasEstadoLabel() ?: 'N/A' }}
                                    @if($personal->alergias_estado === 'SI' && $personal->alergias)
                                        — {{ $personal->alergias }}
                                    @endif
                                </div>

                                <div class="col-md-2 mb-3">
                                    <strong>Ingreso a SSP:</strong><br>
                                    {{ $personal->fecha_ingreso ? \Carbon\Carbon::parse($personal->fecha_ingreso)->format('d-m-Y') : 'N/A' }}
                                </div>

                                <div class="col-md-2 mb-3">
                                    <strong>Ingreso a unidad / subdirección:</strong><br>
                                    {{ $personal->fecha_ingreso_unidad ? $personal->fecha_ingreso_unidad->format('d-m-Y') : 'N/A' }}
                                </div>

                                <div class="col-md-2 mb-3">
                                    <strong>Baja:</strong><br>
                                    {{ $personal->fecha_baja ? \Carbon\Carbon::parse($personal->fecha_baja)->format('d-m-Y') : 'N/A' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Fotos</h3>
                    <div class="card-tools">
                        @can('editar personal')
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalAgregarFotos">
                                <i class="fa-solid fa-plus"></i> Agregar fotos
                            </button>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    @if($personal->fotos && $personal->fotos->count())
                        <div class="row">
                            @foreach($personal->fotos as $foto)
                                <div class="col-md-2 col-sm-4 mb-3">
                                    <img src="{{ route('personal.fotos.show', [$personal->id, $foto->id]) }}" alt="{{ $foto->nombre_original ?? 'Foto de personal' }}" class="img-fluid rounded personal-gallery-photo">
                                    <div class="small text-muted mt-1 text-truncate">{{ $foto->nombre_original ?? basename($foto->ruta) }}</div>
                                    @can('editar personal')
                                        <form action="{{ route('personal.fotos.destroy', [$personal->id, $foto->id]) }}" method="POST" class="mt-1">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger btn-xs" onclick="return confirm('¿Eliminar esta foto?')">
                                                <i class="fa-solid fa-trash"></i> Eliminar
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted mb-0">No hay fotos registradas.</p>
                    @endif
                </div>
            </div>

            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Contacto</h3>
                    <div class="card-tools">
                        @can('editar personal')
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalAgregarContacto">
                                <i class="fa-solid fa-plus"></i> Agregar contacto
                            </button>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    @if($personal->contactos && $personal->contactos->count())
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>Teléfono personal</th>
                                        <th>Teléfono secundario</th>
                                        <th>Correo</th>
                                        <th>Tipo</th>
                                        <th>Valor</th>
                                        <th>Principal</th>
                                        <th>Observaciones</th>
                                        <th style="width: 110px;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($personal->contactos as $c)
                                        <tr>
                                            <td>{{ $c->telefono_personal ?? 'N/A' }}</td>
                                            <td>{{ $c->telefono_secundario ?? 'N/A' }}</td>
                                            <td>{{ $c->correo_electronico ?? 'N/A' }}</td>
                                            <td>{{ $c->tipo ?? 'N/A' }}</td>
                                            <td>{{ $c->valor ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge badge-{{ $c->es_principal ? 'success' : 'secondary' }}">
                                                    {{ $c->es_principal ? 'Sí' : 'No' }}
                                                </span>
                                            </td>
                                            <td>{{ $c->observaciones ?? '' }}</td>
                                            <td>
                                                @can('editar personal')
                                                    <form action="{{ route('personal.contactos.destroy', [$personal->id, $c->id]) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este contacto?')">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No hay contactos registrados.</p>
                    @endif
                </div>
            </div>

            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Domicilio</h3>
                    <div class="card-tools">
                        @can('editar personal')
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalAgregarDomicilio">
                                <i class="fa-solid fa-plus"></i> Registrar domicilio
                            </button>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    @php
                        $domActual = $personal->domicilioActual ?? null;
                    @endphp

                    <div class="mb-3">
                        <strong>Domicilio actual:</strong><br>
                        @if($domActual)
                            {{ $domActual->calle }} #{{ $domActual->numero_ext }}{{ $domActual->numero_int ? ' Int ' . $domActual->numero_int : '' }},
                            {{ $domActual->colonia }},
                            {{ $domActual->municipio }},
                            {{ $domActual->estado }},
                            CP {{ $domActual->cp }}
                            @if($domActual->referencias)
                                <br><small class="text-muted">Referencias: {{ $domActual->referencias }}</small>
                            @endif
                        @else
                            <span class="text-muted">No hay domicilio actual registrado.</span>
                        @endif
                    </div>

                    @if($personal->domicilios && $personal->domicilios->count())
                        <div class="table-responsive mt-2">
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>Domicilio</th>
                                        <th>Actual</th>
                                        <th>Registrado</th>
                                        <th style="width: 110px;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($personal->domicilios as $d)
                                        <tr>
                                            <td>
                                                {{ $d->calle }} #{{ $d->numero_ext }}{{ $d->numero_int ? ' Int ' . $d->numero_int : '' }},
                                                {{ $d->colonia }},
                                                {{ $d->municipio }},
                                                {{ $d->estado }},
                                                CP {{ $d->cp }}
                                                @if($d->referencias)
                                                    <br><small class="text-muted">Ref: {{ $d->referencias }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $d->es_actual ? 'success' : 'secondary' }}">
                                                    {{ $d->es_actual ? 'Sí' : 'No' }}
                                                </span>
                                            </td>
                                            <td>{{ $d->created_at ? $d->created_at->format('d-m-Y H:i') : '' }}</td>
                                            <td>
                                                @can('editar personal')
                                                    <form action="{{ route('personal.domicilios.destroy', [$personal->id, $d->id]) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este domicilio?')">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0 mt-2">No hay domicilios en el historial.</p>
                    @endif
                </div>
            </div>

            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Contactos de emergencia</h3>
                    <div class="card-tools">
                        @can('editar personal')
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalAgregarEmergencia">
                                <i class="fa-solid fa-plus"></i> Agregar emergencia
                            </button>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    @if($personal->emergencias && $personal->emergencias->count())
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Parentesco</th>
                                        <th>Teléfono emergencia</th>
                                        <th>Teléfono 2</th>
                                        <th>Dirección</th>
                                        <th>Observaciones</th>
                                        <th style="width: 110px;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($personal->emergencias as $e)
                                        <tr>
                                            <td>{{ $e->nombre_contacto ?? $e->nombre }}</td>
                                            <td>{{ $e->parentesco ?? '' }}</td>
                                            <td>{{ $e->telefono_emergencia ?? $e->telefono }}</td>
                                            <td>{{ $e->telefono_2 ?? '' }}</td>
                                            <td>{{ $e->direccion ?? '' }}</td>
                                            <td>{{ $e->observaciones ?? '' }}</td>
                                            <td>
                                                @can('editar personal')
                                                    <form action="{{ route('personal.emergencias.destroy', [$personal->id, $e->id]) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este contacto de emergencia?')">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No hay contactos de emergencia registrados.</p>
                    @endif
                </div>
            </div>

            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Licencias de conducir</h3>
                    <div class="card-tools">
                        @can('editar personal')
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalAgregarLicencia">
                                <i class="fa-solid fa-plus"></i> Agregar licencia
                            </button>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    @if($licenciasPersonal->count())
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Número</th>
                                        <th>Vigencia</th>
                                        <th>Estado</th>
                                        <th>Observaciones</th>
                                        <th style="width: 140px;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($licenciasPersonal as $licencia)
                                        @php
                                            $licenciaVencida = $licencia->estaVencida();
                                        @endphp
                                        <tr>
                                            <td>{{ $licencia->tipo_label }}</td>
                                            <td>{{ $licencia->numero ?? 'N/A' }}</td>
                                            <td>
                                                @if($licencia->permanente)
                                                    <span class="badge badge-info">Permanente</span>
                                                    <span class="text-muted d-block">{{ optional($licencia->vigencia)->format('d-m-Y') }}</span>
                                                @else
                                                    {{ $licencia->vigencia ? $licencia->vigencia->format('d-m-Y') : 'N/A' }}
                                                @endif
                                            </td>
                                            <td>
                                                @if(!$licencia->activo)
                                                    <span class="badge badge-secondary">Inactiva</span>
                                                @elseif($licencia->permanente)
                                                    <span class="badge badge-info">Permanente</span>
                                                @elseif($licenciaVencida)
                                                    <span class="badge badge-danger">Vencida</span>
                                                @else
                                                    <span class="badge badge-success">Vigente</span>
                                                @endif

                                                @if($licencia->vencimiento_notificado_at)
                                                    <small class="text-muted d-block">
                                                        Alertada: {{ $licencia->vencimiento_notificado_at->format('d-m-Y H:i') }}
                                                    </small>
                                                @endif
                                            </td>
                                            <td>{{ $licencia->observaciones ?? '' }}</td>
                                            <td>
                                                @can('editar personal')
                                                    <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#modalEditarLicencia{{ $licencia->id }}">
                                                        <i class="fa-regular fa-pen-to-square"></i>
                                                    </button>
                                                    <form action="{{ route('personal.licencias.destroy', [$personal->id, $licencia->id]) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar esta licencia?')">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @can('editar personal')
                            @foreach($licenciasPersonal as $licencia)
                                <div class="modal fade modal-opaque" id="modalEditarLicencia{{ $licencia->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                    <div class="modal-dialog modal-lg" role="document">
                                        <form method="POST" action="{{ route('personal.licencias.update', [$personal->id, $licencia->id]) }}" class="js-licencia-form">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Editar licencia</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="form-group">
                                                                <label>Tipo</label>
                                                                <select name="tipo" class="form-control" required>
                                                                    <option value="">Seleccionar</option>
                                                                    @foreach($tiposLicencia as $tipoClave => $tipoLabel)
                                                                        <option value="{{ $tipoClave }}" {{ $licencia->tipo === $tipoClave ? 'selected' : '' }}>
                                                                            {{ $tipoLabel }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="form-group">
                                                                <label>Número</label>
                                                                <input type="text" name="numero" class="form-control" value="{{ $licencia->numero }}">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="form-group">
                                                                <label>Activo</label>
                                                                <input type="hidden" name="activo" value="0">
                                                                <div class="custom-control custom-switch mt-2">
                                                                    <input type="checkbox" name="activo" value="1" class="custom-control-input" id="licenciaActivo{{ $licencia->id }}" {{ $licencia->activo ? 'checked' : '' }}>
                                                                    <label class="custom-control-label" for="licenciaActivo{{ $licencia->id }}">Sí</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="form-group">
                                                                <label>Vigencia</label>
                                                                <input type="date" name="vigencia" class="form-control js-licencia-vigencia" value="{{ optional($licencia->vigencia)->format('Y-m-d') }}" required>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="form-group">
                                                                <label>Permanente</label>
                                                                <input type="hidden" name="permanente" value="0">
                                                                <div class="custom-control custom-switch mt-2">
                                                                    <input type="checkbox" name="permanente" value="1" class="custom-control-input js-licencia-permanente" id="licenciaPermanente{{ $licencia->id }}" {{ $licencia->permanente ? 'checked' : '' }}>
                                                                    <label class="custom-control-label" for="licenciaPermanente{{ $licencia->id }}">Sí</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="form-group mb-0">
                                                                <label>Observaciones</label>
                                                                <textarea name="observaciones" class="form-control" rows="2">{{ $licencia->observaciones }}</textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-primary">Guardar</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        @endcan
                    @else
                        <p class="text-muted mb-0">No hay licencias registradas.</p>
                    @endif
                </div>
            </div>

            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Documentos y oficios</h3>
                    <div class="card-tools">
                        @can('editar personal')
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalAgregarDocumento">
                                <i class="fa-solid fa-plus"></i> Agregar documento
                            </button>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    @if($documentosPersonal->count())
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Número / folio</th>
                                        <th>Emisión</th>
                                        <th>Vencimiento</th>
                                        <th>Archivo</th>
                                        <th>Oficios</th>
                                        <th>Estado</th>
                                        <th>Observaciones</th>
                                        <th style="width: 110px;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($documentosPersonal as $doc)
                                        <tr>
                                            <td>{{ $doc->documentoTipo->nombre ?? 'Sin tipo' }}</td>
                                            <td>{{ $doc->numero ?? 'N/A' }}</td>
                                            <td>{{ $doc->fecha_emision ? $doc->fecha_emision->format('d-m-Y') : 'N/A' }}</td>
                                            <td>{{ $doc->fecha_vencimiento ? $doc->fecha_vencimiento->format('d-m-Y') : 'N/A' }}</td>
                                            <td>
                                                @if($doc->archivo_path)
                                                    <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('personal.documentos.signed', now()->addMinutes(30), ['documento' => $doc->id, 'archivo' => 'general']) }}" target="_blank">
                                                        {{ $doc->archivo_nombre ?: 'Ver archivo' }}
                                                    </a>
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>
                                                @if($doc->oficio_comision_secretario || $doc->oficio_asignacion || $doc->titular_firma_oficio || $doc->titular_firma_asignacion)
                                                    @if($doc->oficio_comision_secretario)
                                                        <div>
                                                            <strong>Comisión:</strong> {{ $doc->oficio_comision_secretario }}
                                                            @if($doc->fecha_oficio)
                                                                <span class="text-muted">({{ $doc->fecha_oficio->format('d-m-Y') }})</span>
                                                            @endif
                                                            @if($doc->archivo_oficio_comision)
                                                                <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('personal.documentos.signed', now()->addMinutes(30), ['documento' => $doc->id, 'archivo' => 'comision']) }}" target="_blank" class="ml-1">Archivo</a>
                                                            @endif
                                                        </div>
                                                    @endif
                                                    @if($doc->titular_firma_oficio)
                                                        <div class="text-muted">Firma oficio: {{ $doc->titular_firma_oficio }}</div>
                                                    @endif
                                                    @if($doc->oficio_asignacion)
                                                        <div>
                                                            <strong>Asignación:</strong> {{ $doc->oficio_asignacion }}
                                                            @if($doc->fecha_asignacion)
                                                                <span class="text-muted">({{ $doc->fecha_asignacion->format('d-m-Y') }})</span>
                                                            @endif
                                                            @if($doc->archivo_oficio_asignacion)
                                                                <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('personal.documentos.signed', now()->addMinutes(30), ['documento' => $doc->id, 'archivo' => 'asignacion']) }}" target="_blank" class="ml-1">Archivo</a>
                                                            @endif
                                                        </div>
                                                    @endif
                                                    @if($doc->titular_firma_asignacion)
                                                        <div class="text-muted">Firma asignación: {{ $doc->titular_firma_asignacion }}</div>
                                                    @endif
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>
                                                @if($doc->activo)
                                                    <span class="badge badge-success">Activo</span>
                                                @else
                                                    <span class="badge badge-secondary">Inactivo</span>
                                                @endif
                                            </td>
                                            <td>{{ $doc->observaciones ?? '' }}</td>
                                            <td>
                                                @can('editar personal')
                                                    <form action="{{ route('personal.documentos.destroy', [$personal->id, $doc->id]) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este documento?')">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No hay documentos registrados.</p>
                    @endif
                </div>
            </div>

            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">Incidencias</h3>
                    <div class="card-tools">
                        @can('editar personal')
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalAgregarIncidencia">
                                <i class="fa-solid fa-plus"></i> Agregar incidencia
                            </button>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    @if($personal->incidencias && $personal->incidencias->count())
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Fecha inicio</th>
                                        <th>Hora inicio</th>
                                        <th>Fecha fin</th>
                                        <th>Hora fin</th>
                                        <th>Folio</th>
                                        <th>Motivo</th>
                                        <th>Observaciones</th>
                                        <th style="width: 110px;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($personal->incidencias as $inc)
                                        <tr>
                                            <td>{{ $inc->tipo->nombre ?? 'N/A' }}</td>
                                            <td>{{ $inc->fecha_inicio ? \Carbon\Carbon::parse($inc->fecha_inicio)->format('d-m-Y') : 'N/A' }}</td>
                                            <td>{{ $inc->hora_inicio ?? 'N/A' }}</td>
                                            <td>{{ $inc->fecha_fin ? \Carbon\Carbon::parse($inc->fecha_fin)->format('d-m-Y') : 'N/A' }}</td>
                                            <td>{{ $inc->hora_fin ?? 'N/A' }}</td>
                                            <td>{{ $inc->folio ?? '' }}</td>
                                            <td>{{ $inc->motivo ?? '' }}</td>
                                            <td>{{ $inc->observaciones ?? '' }}</td>
                                            <td>
                                                @can('editar personal')
                                                    <form action="{{ route('personal.incidencias.destroy', [$personal->id, $inc->id]) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar esta incidencia?')">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No hay incidencias registradas.</p>
                    @endif
                </div>
            </div>

            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">Asignaciones activas</h3>
                    <div class="card-tools">
                        @can('editar personal')
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalAgregarAsignacion">
                                <i class="fa-solid fa-plus"></i> Registrar asignación
                            </button>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    @if($asignacionesActivas->count())
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>Comisionado a</th>
                                        <th>Ubicación interna</th>
                                        <th>Municipio/localidad</th>
                                        <th>Funciones</th>
                                        <th>Horario</th>
                                        <th>Contratación</th>
                                        <th>DPC</th>
                                        <th>Oficina pago</th>
                                        <th>Armamento</th>
                                        <th>Fecha inicio</th>
                                        <th style="width: 140px;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($asignacionesActivas as $asig)
                                        <tr>
                                            <td>{{ $asig->comisionado_a ?? 'N/A' }}</td>
                                            <td>{{ $asig->ubicacion_interna ?? 'N/A' }}</td>
                                            <td>{{ $asig->municipio_localidad_servicio ?? 'N/A' }}</td>
                                            <td>{{ $asig->funciones ?? 'N/A' }}</td>
                                            <td>{{ $asig->horario ?? 'N/A' }}</td>
                                            <td>{{ $asig->tipo_contratacion ?? 'N/A' }}</td>
                                            <td>{{ $asig->dpc ?? 'N/A' }}</td>
                                            <td>{{ $asig->oficina_pago ?? 'N/A' }}</td>
                                            <td>
                                                @if($asig->armamento)
                                                    {{ $asig->armamento->tipo }} {{ $asig->armamento->clase }} {{ $asig->armamento->marca }} {{ $asig->armamento->modelo }}
                                                    <br><small>Matrícula: {{ $asig->armamento->matricula ?? 'N/A' }} | Serie: {{ $asig->armamento->serie ?? 'N/A' }}</small>
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>{{ $asig->fecha_asignacion ? \Carbon\Carbon::parse($asig->fecha_asignacion)->format('d-m-Y') : 'N/A' }}</td>
                                            <td>
                                                @can('editar personal')
                                                    <form action="{{ route('personal.asignaciones.cerrar', [$personal->id, $asig->id]) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button class="btn btn-warning btn-sm" onclick="return confirm('¿Cerrar esta asignación?')">
                                                            Cerrar
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('personal.asignaciones.destroy', [$personal->id, $asig->id]) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar esta asignación?')">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No hay asignaciones activas.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade modal-opaque" id="modalAgregarFotos" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form method="POST" action="{{ route('personal.fotos.store', $personal->id) }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Agregar fotos</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group mb-0">
                            <label>Fotos</label>
                            <input type="file" name="fotos[]" class="form-control-file" accept=".jpg,.jpeg,.png,.webp,.heic,.heif,.avif,.bmp,.gif,.tif,.tiff,image/*" multiple required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade modal-opaque" id="modalAgregarContacto" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form method="POST" action="{{ route('personal.contactos.store', $personal->id) }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Agregar contacto</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Teléfono personal</label>
                            <input type="text" name="telefono_personal" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Teléfono secundario</label>
                            <input type="text" name="telefono_secundario" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Correo electrónico</label>
                            <input type="email" name="correo_electronico" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Tipo adicional</label>
                            <select name="tipo" class="form-control">
                                <option value="">Sin tipo adicional</option>
                                <option value="CELULAR">CELULAR</option>
                                <option value="CASA">CASA</option>
                                <option value="OFICINA">OFICINA</option>
                                <option value="EMAIL_INST">EMAIL_INST</option>
                                <option value="EMAIL_PER">EMAIL_PER</option>
                                <option value="OTRO">OTRO</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Valor adicional</label>
                            <input type="text" name="valor" class="form-control">
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="contacto_es_principal" name="es_principal" value="1">
                                <label class="custom-control-label" for="contacto_es_principal">Marcar como principal</label>
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label>Observaciones</label>
                            <input type="text" name="observaciones" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade modal-opaque" id="modalAgregarDomicilio" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form method="POST" action="{{ route('personal.domicilios.store', $personal->id) }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Registrar domicilio</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Calle</label>
                                    <input type="text" name="calle" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Número ext</label>
                                    <input type="text" name="numero_ext" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Número int</label>
                                    <input type="text" name="numero_int" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Colonia</label>
                                    <input type="text" name="colonia" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Municipio</label>
                                    <input type="text" name="municipio" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Estado</label>
                                    <input type="text" name="estado" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>CP</label>
                                    <input type="text" name="cp" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label>Referencias</label>
                                    <input type="text" name="referencias" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="dom_es_actual" name="es_actual" value="1" checked>
                                <label class="custom-control-label" for="dom_es_actual">Marcar como domicilio actual</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade modal-opaque" id="modalAgregarEmergencia" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form method="POST" action="{{ route('personal.emergencias.store', $personal->id) }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Agregar contacto de emergencia</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nombre del contacto</label>
                            <input type="text" name="nombre_contacto" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Parentesco</label>
                            <input type="text" name="parentesco" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Teléfono de emergencia</label>
                            <input type="text" name="telefono_emergencia" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Teléfono 2</label>
                            <input type="text" name="telefono_2" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Dirección</label>
                            <input type="text" name="direccion" class="form-control">
                        </div>
                        <div class="form-group mb-0">
                            <label>Observaciones</label>
                            <input type="text" name="observaciones" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade modal-opaque" id="modalAgregarLicencia" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form method="POST" action="{{ route('personal.licencias.store', $personal->id) }}" class="js-licencia-form">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Agregar licencia</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Tipo</label>
                                    <select name="tipo" class="form-control" required>
                                        <option value="">Seleccionar</option>
                                        @foreach($tiposLicencia as $tipoClave => $tipoLabel)
                                            <option value="{{ $tipoClave }}" {{ old('tipo') === $tipoClave ? 'selected' : '' }}>
                                                {{ $tipoLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Número</label>
                                    <input type="text" name="numero" class="form-control" value="{{ old('numero') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Activo</label>
                                    <input type="hidden" name="activo" value="0">
                                    <div class="custom-control custom-switch mt-2">
                                        <input type="checkbox" name="activo" value="1" class="custom-control-input" id="licenciaActivoNueva" {{ old('activo', '1') === '1' ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="licenciaActivoNueva">Sí</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Vigencia</label>
                                    <input type="date" name="vigencia" class="form-control js-licencia-vigencia" value="{{ old('vigencia') }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Permanente</label>
                                    <input type="hidden" name="permanente" value="0">
                                    <div class="custom-control custom-switch mt-2">
                                        <input type="checkbox" name="permanente" value="1" class="custom-control-input js-licencia-permanente" id="licenciaPermanenteNueva" {{ old('permanente') === '1' ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="licenciaPermanenteNueva">Sí</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-0">
                                    <label>Observaciones</label>
                                    <textarea name="observaciones" class="form-control" rows="2">{{ old('observaciones') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade modal-opaque" id="modalAgregarDocumento" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form method="POST" action="{{ route('personal.documentos.store', $personal->id) }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Agregar documento</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Tipo de documento</label>
                                    <select name="documento_tipo_id" class="form-control js-documento-tipo" {{ $documentoTipos->count() ? 'required' : '' }}>
                                        <option value="" data-clave="">Seleccionar</option>
                                        @foreach($documentoTipos as $tipo)
                                            <option value="{{ $tipo->id }}" data-clave="{{ $tipo->clave }}" {{ (string) old('documento_tipo_id') === (string) $tipo->id ? 'selected' : '' }}>
                                                {{ $tipo->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Número / folio</label>
                                    <input type="text" name="numero" class="form-control" value="{{ old('numero') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Activo</label>
                                    <input type="hidden" name="activo" value="0">
                                    <div class="custom-control custom-switch mt-2">
                                        <input type="checkbox" name="activo" value="1" class="custom-control-input" id="documentoActivo" {{ old('activo', '1') === '1' ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="documentoActivo">Sí</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Fecha de emisión</label>
                                    <input type="date" name="fecha_emision" class="form-control" value="{{ old('fecha_emision') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Fecha de vencimiento</label>
                                    <input type="date" name="fecha_vencimiento" class="form-control" value="{{ old('fecha_vencimiento') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Archivo principal</label>
                                    <input type="file" name="archivo" class="form-control-file" accept=".pdf,.doc,.docx,image/jpeg,image/png,image/webp">
                                </div>
                            </div>
                        </div>
                        <div class="js-documento-oficios-fields {{ $mostrarCamposOficioDocumento ? '' : 'd-none' }}">
                            <hr>
                            <h6 class="font-weight-bold text-muted mb-3">Datos de oficios</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Oficio comisión secretario</label>
                                        <input type="text" name="oficio_comision_secretario" class="form-control" value="{{ old('oficio_comision_secretario') }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Fecha oficio</label>
                                        <input type="date" name="fecha_oficio" class="form-control" value="{{ old('fecha_oficio') }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Titular firma oficio</label>
                                        <input type="text" name="titular_firma_oficio" class="form-control" value="{{ old('titular_firma_oficio') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Archivo oficio comisión</label>
                                <input type="file" name="archivo_oficio_comision" class="form-control-file" accept=".pdf,.doc,.docx,image/jpeg,image/png,image/webp">
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Oficio asignación</label>
                                        <input type="text" name="oficio_asignacion" class="form-control" value="{{ old('oficio_asignacion') }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Fecha asignación</label>
                                        <input type="date" name="fecha_asignacion" class="form-control" value="{{ old('fecha_asignacion') }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Titular firma asignación</label>
                                        <input type="text" name="titular_firma_asignacion" class="form-control" value="{{ old('titular_firma_asignacion') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Archivo oficio asignación</label>
                                <input type="file" name="archivo_oficio_asignacion" class="form-control-file" accept=".pdf,.doc,.docx,image/jpeg,image/png,image/webp">
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label>Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="2">{{ old('observaciones') }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade modal-opaque" id="modalAgregarAsignacion" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form method="POST" action="{{ route('personal.asignaciones.store', $personal->id) }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Registrar asignación</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Fecha de asignación</label>
                                    <input type="date" name="fecha_asignacion" class="form-control" value="{{ now()->toDateString() }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Tipo contratación</label>
                                    <select name="tipo_contratacion" class="form-control">
                                        <option value="">Sin especificar</option>
                                        <option value="BASE">BASE</option>
                                        <option value="INTERINATO">INTERINATO</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Folio</label>
                                    <input type="text" name="folio" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Comisionado a</label>
                                    <input type="text" name="comisionado_a" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Ubicación interna</label>
                                    <input type="text" name="ubicacion_interna" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Municipio/localidad de servicio</label>
                                    <input type="text" name="municipio_localidad_servicio" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Funciones</label>
                                    <input type="text" name="funciones" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Horario</label>
                                    <input type="text" name="horario" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>DPC</label>
                                    <input type="text" name="dpc" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Oficina de pago</label>
                                    <input type="text" name="oficina_pago" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Actividades</label>
                            <textarea name="actividades" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Documento relacionado</label>
                                    <select name="documento_id" class="form-control">
                                        <option value="">Sin documento</option>
                                        @foreach($documentosPersonal as $doc)
                                            <option value="{{ $doc->id }}">
                                                {{ trim(($doc->documentoTipo->nombre ?? 'Documento') . ' - ' . ($doc->numero ?? $doc->oficio_asignacion ?? $doc->oficio_comision_secretario ?? ('#' . $doc->id)), ' -') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Armamento disponible</label>
                                    <select name="armamento_id" class="form-control">
                                        <option value="">Sin armamento</option>
                                        @foreach($armamentosDisponibles as $a)
                                            <option value="{{ $a->id }}">
                                                {{ $a->tipo }} | {{ $a->clase }} | {{ $a->marca }} {{ $a->modelo }} | Matrícula: {{ $a->matricula }} | Serie: {{ $a->serie }} | Cal: {{ $a->calibre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label>Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade modal-opaque" id="modalAgregarIncidencia" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form method="POST" action="{{ route('personal.incidencias.store', $personal->id) }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Agregar incidencia</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Tipo</label>
                            <select name="tipo" class="form-control" required>
                                <option value="">-- Selecciona --</option>
                                <option value="VACACIONES">VACACIONES</option>
                                <option value="INCAPACIDAD">INCAPACIDAD</option>
                                <option value="PERMISO">PERMISO</option>
                                <option value="FALTA">FALTA</option>
                                <option value="COMISION">COMISION</option>
                                <option value="SUSPENSION">SUSPENSION</option>
                                <option value="SERVICIO">SERVICIO</option>
                                <option value="OTRO">OTRO</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Fecha inicio</label>
                                    <input type="date" name="fecha_inicio" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Fecha fin</label>
                                    <input type="date" name="fecha_fin" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Hora inicio</label>
                                    <input type="time" name="hora_inicio" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Hora fin</label>
                                    <input type="time" name="hora_fin" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Folio</label>
                            <input type="text" name="folio" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Motivo</label>
                            <input type="text" name="motivo" class="form-control">
                        </div>
                        <div class="form-group mb-0">
                            <label>Observaciones</label>
                            <input type="text" name="observaciones" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop

@push('css')
<style>
    .personal-photo {
        max-height: 220px;
        object-fit: cover;
        width: 100%;
    }

    .personal-photo-placeholder {
        align-items: center;
        background: #111827;
        border: 1px solid rgba(255,255,255,.18);
        border-radius: 6px;
        color: #e5e7eb;
        display: flex;
        height: 220px;
        justify-content: center;
        width: 100%;
    }

    .personal-gallery-photo {
        aspect-ratio: 1 / 1;
        object-fit: cover;
        width: 100%;
    }

    .modal-backdrop.show {
        opacity: .85 !important;
    }

    .modal-opaque .modal-content {
        background-color: #1f2937 !important;
        opacity: 1 !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
        border: 1px solid rgba(255,255,255,.12) !important;
        box-shadow: 0 20px 60px rgba(0,0,0,.65) !important;
    }

    .modal-opaque .modal-header,
    .modal-opaque .modal-body,
    .modal-opaque .modal-footer {
        background-color: transparent !important;
    }

    .modal-opaque .form-control {
        background-color: #111827 !important;
        border: 1px solid rgba(255,255,255,.12) !important;
        color: #e5e7eb !important;
    }

    .modal-opaque .form-control:focus {
        background-color: #0b1220 !important;
        border-color: rgba(59,130,246,.6) !important;
        box-shadow: 0 0 0 .2rem rgba(59,130,246,.25) !important;
        color: #e5e7eb !important;
    }

    .modal-opaque label,
    .modal-opaque .modal-title {
        color: #e5e7eb !important;
    }

    .modal-opaque .text-muted {
        color: rgba(229,231,235,.7) !important;
    }
</style>
@endpush

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const documentoTipo = document.querySelector('#modalAgregarDocumento .js-documento-tipo');
    const documentoOficiosFields = document.querySelector('#modalAgregarDocumento .js-documento-oficios-fields');

    if (documentoTipo && documentoOficiosFields) {
        const oficioInputs = documentoOficiosFields.querySelectorAll('input, select, textarea');
        const syncDocumentoOficios = function () {
            const option = documentoTipo.options[documentoTipo.selectedIndex];
            const mostrarOficios = option && option.dataset.clave === 'OFICIOS_PERSONAL';

            documentoOficiosFields.classList.toggle('d-none', !mostrarOficios);
            oficioInputs.forEach(function (input) {
                input.disabled = !mostrarOficios;
            });
        };

        documentoTipo.addEventListener('change', syncDocumentoOficios);
        syncDocumentoOficios();
    }

    document.querySelectorAll('.js-licencia-form').forEach(function (form) {
        const permanente = form.querySelector('.js-licencia-permanente');
        const vigencia = form.querySelector('.js-licencia-vigencia');

        if (!permanente || !vigencia) {
            return;
        }

        const syncPermanente = function () {
            if (permanente.checked) {
                if (vigencia.value && vigencia.value !== '2099-12-31') {
                    vigencia.dataset.previousValue = vigencia.value;
                }

                vigencia.value = '2099-12-31';
                vigencia.readOnly = true;
                return;
            }

            vigencia.readOnly = false;

            if (vigencia.value === '2099-12-31') {
                vigencia.value = vigencia.dataset.previousValue && vigencia.dataset.previousValue !== '2099-12-31'
                    ? vigencia.dataset.previousValue
                    : '';
            }
        };

        permanente.addEventListener('change', syncPermanente);
        syncPermanente();
    });
});

@if ($errors->any())
Swal.fire({
    icon: 'error',
    title: 'Errores en el formulario',
    html: `
        <ul style="text-align:left;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    `,
    confirmButtonText: 'Aceptar'
});
@endif

@if (session('success'))
Swal.fire({
    icon: 'success',
    title: '{{ session('success') }}',
    showConfirmButton: false,
    timer: 3000
});
@endif
</script>
@stop
