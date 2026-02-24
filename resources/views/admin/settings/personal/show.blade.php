@extends('adminlte::page')

@section('title', 'Detalle de Personal')

@section('content_header')
    <h1>Detalle del Elemento</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">

            {{-- INFO GENERAL --}}
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        Información General
                    </h3>
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

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Nombre completo:</strong><br>
                            {{ trim(($personal->nombre ?? '') . ' ' . ($personal->ap_paterno ?? '') . ' ' . ($personal->ap_materno ?? '')) }}
                        </div>

                        <div class="col-md-4">
                            <strong>Unidad:</strong><br>
                            {{ $personal->unidad->nombre ?? 'N/A' }}
                        </div>

                        <div class="col-md-4">
                            <strong>Turno:</strong><br>
                            {{ $personal->turno->nombre ?? 'N/A' }}
                        </div>
                        <div class="col-md-4">
                            <strong>Patrulla:</strong><br>
                            {{ $personal->patrulla->numero_economico ?? 'Sin asignar' }}
                        </div>
                    </div>

                    <hr>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <strong>Grado:</strong><br>
                            {{ $personal->grado ?? 'N/A' }}
                        </div>

                        <div class="col-md-3">
                            <strong>Estatus:</strong><br>
                            <span class="badge badge-{{ ($personal->estatus ?? '') === 'ACTIVO' ? 'success' : 'secondary' }}">
                                {{ $personal->estatus ?? 'N/A' }}
                            </span>
                        </div>

                        <div class="col-md-3">
                            <strong>Fecha de Ingreso:</strong><br>
                            {{ $personal->fecha_ingreso ? \Carbon\Carbon::parse($personal->fecha_ingreso)->format('d-m-Y') : 'N/A' }}
                        </div>

                        <div class="col-md-3">
                            <strong>Fecha de Baja:</strong><br>
                            {{ $personal->fecha_baja ? \Carbon\Carbon::parse($personal->fecha_baja)->format('d-m-Y') : 'N/A' }}
                        </div>
                    </div>

                    <hr>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>CURP:</strong><br>
                            {{ $personal->curp ?? 'N/A' }}
                        </div>

                        <div class="col-md-4">
                            <strong>RFC:</strong><br>
                            {{ $personal->rfc ?? 'N/A' }}
                        </div>

                        <div class="col-md-4">
                            <strong>CUIP:</strong><br>
                            {{ $personal->cuip ?? 'N/A' }}
                        </div>
                    </div>

                </div>
            </div>

            {{-- CONTACTOS --}}
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
                                        <th>Tipo</th>
                                        <th>Valor</th>
                                        <th>Principal</th>
                                        <th>Observaciones</th>
                                        <th style="width: 140px;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($personal->contactos as $c)
                                        <tr>
                                            <td>{{ $c->tipo }}</td>
                                            <td>{{ $c->valor }}</td>
                                            <td>
                                                @if($c->es_principal)
                                                    <span class="badge badge-success">Sí</span>
                                                @else
                                                    <span class="badge badge-secondary">No</span>
                                                @endif
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

            {{-- DOMICILIO --}}
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Domicilio</h3>
                    <div class="card-tools">
                        @can('editar personal')
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalAgregarDomicilio">
                                <i class="fa-solid fa-plus"></i> Registrar/Actualizar domicilio
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

                    <hr>

                    <strong>Historial:</strong>
                    @if($personal->domicilios && $personal->domicilios->count())
                        <div class="table-responsive mt-2">
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>Domicilio</th>
                                        <th>Actual</th>
                                        <th>Registrado</th>
                                        <th style="width: 140px;">Acciones</th>
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
                                                @if($d->es_actual)
                                                    <span class="badge badge-success">Sí</span>
                                                @else
                                                    <span class="badge badge-secondary">No</span>
                                                @endif
                                            </td>
                                            <td>{{ $d->created_at ? $d->created_at->format('d-m-Y H:i') : '' }}</td>
                                            <td>
                                                @can('editar personal')
                                                    <form action="{{ route('personal.domicilios.destroy', [$personal->id, $d->id]) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este domicilio del historial?')">
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

            {{-- EMERGENCIAS --}}
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
                                        <th>Teléfono</th>
                                        <th>Teléfono 2</th>
                                        <th>Dirección</th>
                                        <th>Observaciones</th>
                                        <th style="width: 140px;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($personal->emergencias as $e)
                                        <tr>
                                            <td>{{ $e->nombre }}</td>
                                            <td>{{ $e->parentesco ?? '' }}</td>
                                            <td>{{ $e->telefono }}</td>
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

            {{-- Incidencias --}}
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
                                        <th>Fecha Inicio</th>
                                        <th>Fecha Fin</th>
                                        <th>Folio</th>
                                        <th>Motivo</th>
                                        <th>Observaciones</th>
                                        <th style="width: 140px;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($personal->incidencias as $inc)
                                        <tr>
                                            <td>{{ $inc->incidencia_tipo_id ?? 'N/A' }}</td>
                                            <td>{{ $inc->fecha_inicio ?? 'N/A' }}</td>
                                            <td>{{ $inc->fecha_fin ?? 'N/A' }}</td>
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

            {{-- Asignaciones --}}
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">Asignaciones Activas</h3>

                    <div class="card-tools">
                        @can('editar personal')
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalAsignarArmamento">
                                <i class="fa-solid fa-plus"></i> Asignar armamento
                            </button>
                        @endcan
                    </div>
                </div>

                <div class="card-body">
                    @php
                        // Usamos las asignaciones ya cargadas en $personal, pero filtramos activas aquí mismo.
                        $asignacionesArmamentoActivas = $personal->asignaciones
                            ? $personal->asignaciones->whereNull('fecha_fin')
                            : collect();
                    @endphp

                    @if($asignacionesArmamentoActivas->count())
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Clase</th>
                                        <th>Marca</th>
                                        <th>Modelo</th>
                                        <th>Matrícula</th>
                                        <th>Serie</th>
                                        <th>Calibre</th>
                                        <th>Fecha Inicio</th>
                                        <th>Fecha Fin</th>
                                        <th>Estatus</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($asignacionesArmamentoActivas as $asig)
                                        <tr>
                                            <td>{{ $asig->armamento->tipo ?? '—' }}</td>
                                            <td>{{ $asig->armamento->clase ?? '—' }}</td>
                                            <td>{{ $asig->armamento->marca ?? '—' }}</td>
                                            <td>{{ $asig->armamento->modelo ?? '—' }}</td>
                                            <td>{{ $asig->armamento->matricula ?? '—' }}</td>
                                            <td>{{ $asig->armamento->serie ?? '—' }}</td>
                                            <td>{{ $asig->armamento->calibre ?? '—' }}</td>
                                            <td>{{ $asig->fecha_asignacion ?? '—' }}</td>
                                            <td>{{ $asig->fecha_fin ?? 'Activo' }}</td>
                                            <td>{{ $asig->armamento->estatus ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No hay asignaciones activas de armamento.</p>
                    @endif
                </div>
            </div>

        </div>
    </div>

    {{-- MODAL: AGREGAR CONTACTO --}}
    <div class="modal fade modal-opaque" id="modalAgregarContacto" tabindex="-1" role="dialog" aria-labelledby="modalAgregarContactoLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form method="POST" action="{{ route('personal.contactos.store', $personal->id) }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalAgregarContactoLabel">Agregar contacto</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="form-group">
                            <label>Tipo</label>
                            <select name="tipo" class="form-control" required>
                                <option value="CELULAR">CELULAR</option>
                                <option value="CASA">CASA</option>
                                <option value="OFICINA">OFICINA</option>
                                <option value="EMAIL_INST">EMAIL_INST</option>
                                <option value="EMAIL_PER">EMAIL_PER</option>
                                <option value="OTRO">OTRO</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Valor</label>
                            <input type="text" name="valor" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="contacto_es_principal" name="es_principal" value="1">
                                <label class="custom-control-label" for="contacto_es_principal">Marcar como principal</label>
                            </div>
                            <small class="text-muted">Si marcas principal, el sistema debe quitar principal a los demás (en el controller).</small>
                        </div>

                        <div class="form-group">
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

    {{-- MODAL: AGREGAR DOMICILIO --}}
    <div class="modal fade modal-opaque" id="modalAgregarDomicilio" tabindex="-1" role="dialog" aria-labelledby="modalAgregarDomicilioLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form method="POST" action="{{ route('personal.domicilios.store', $personal->id) }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalAgregarDomicilioLabel">Registrar / Actualizar domicilio</h5>
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
                            <small class="text-muted">Si está marcado, el sistema debe poner en no-actual los demás domicilios (en el controller).</small>
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

    {{-- MODAL: AGREGAR EMERGENCIA --}}
    <div class="modal fade modal-opaque" id="modalAgregarEmergencia" tabindex="-1" role="dialog" aria-labelledby="modalAgregarEmergenciaLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form method="POST" action="{{ route('personal.emergencias.store', $personal->id) }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalAgregarEmergenciaLabel">Agregar contacto de emergencia</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>Parentesco</label>
                            <input type="text" name="parentesco" class="form-control">
                        </div>

                        <div class="form-group">
                            <label>Teléfono</label>
                            <input type="text" name="telefono" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>Teléfono 2</label>
                            <input type="text" name="telefono_2" class="form-control">
                        </div>

                        <div class="form-group">
                            <label>Dirección</label>
                            <input type="text" name="direccion" class="form-control">
                        </div>

                        <div class="form-group">
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

    {{-- MODAL: ASIGNAR ARMAMENTO --}}
    <div class="modal fade modal-opaque" id="modalAsignarArmamento" tabindex="-1" role="dialog" aria-labelledby="modalAsignarArmamentoLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form method="POST" action="{{ route('personal.asignaciones.store', $personal->id) }}">
                @csrf

                <input type="hidden" name="fecha_asignacion" value="{{ now()->toDateString() }}">

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalAsignarArmamentoLabel">Asignar armamento</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">

                        <div class="form-group">
                            <label>Armamento disponible</label>
                            <select name="armamento_id" class="form-control" required>
                                <option value="">-- Selecciona --</option>
                                @foreach($armamentosDisponibles as $a)
                                    <option value="{{ $a->id }}">
                                        {{ $a->tipo }} | {{ $a->clase }} | {{ $a->marca }} {{ $a->modelo }} | Matrícula: {{ $a->matricula }} | Serie: {{ $a->serie }} | Cal: {{ $a->calibre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group mb-0">
                            <label>Observaciones (opcional)</label>
                            <input type="text" name="observaciones" class="form-control" placeholder="Ej: Entregado en buen estado">
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Asignar</button>
                    </div>

                </div>
            </form>
        </div>
    </div>

        {{-- MODAL: AGREGAR INCIDENCIA --}}
    <div class="modal fade modal-opaque" id="modalAgregarIncidencia" tabindex="-1" role="dialog" aria-labelledby="modalAgregarIncidenciaLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form method="POST" action="{{ route('personal.incidencias.store', $personal->id) }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalAgregarIncidenciaLabel">Agregar incidencia</h5>
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
    /* Fondo oscuro detrás del modal (backdrop) */
    .modal-backdrop.show{
        opacity: .85 !important;
    }

    /* Modal NO transparente */
    .modal-opaque .modal-content{
        background-color: #1f2937 !important; /* oscuro sólido */
        opacity: 1 !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
        border: 1px solid rgba(255,255,255,.12) !important;
        box-shadow: 0 20px 60px rgba(0,0,0,.65) !important;
    }

    .modal-opaque .modal-header,
    .modal-opaque .modal-body,
    .modal-opaque .modal-footer{
        background-color: transparent !important;
    }

    /* Inputs dentro del modal (para que no se “mezclen” con el fondo) */
    .modal-opaque .form-control{
        background-color: #111827 !important;
        border: 1px solid rgba(255,255,255,.12) !important;
        color: #e5e7eb !important;
    }

    .modal-opaque .form-control:focus{
        background-color: #0b1220 !important;
        border-color: rgba(59,130,246,.6) !important;
        box-shadow: 0 0 0 .2rem rgba(59,130,246,.25) !important;
        color: #e5e7eb !important;
    }

    .modal-opaque label,
    .modal-opaque .modal-title{
        color: #e5e7eb !important;
    }

    .modal-opaque .text-muted{
        color: rgba(229,231,235,.7) !important;
    }
</style>
@endpush
