@extends('adminlte::page')

@section('title', 'Curso de recuperacion')

@section('content_header')
    <div class="d-flex flex-wrap align-items-center justify-content-between">
        <div>
            <h1 class="mb-1">{{ $curso->folio }}</h1>
            <p class="text-muted mb-0">{{ $curso->nombre }}</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('licencias_puntos.cursos.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </a>
            @if($puedeGestionar && $curso->puede_modificarse)
                <a href="{{ route('licencias_puntos.cursos.edit', $curso) }}" class="btn btn-warning">
                    <i class="fa-solid fa-pen"></i> Editar
                </a>
            @endif
        </div>
    </div>
@stop

@section('content')
@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Revisa los datos capturados.</strong>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
    <div class="col-lg-4">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Curso</h3>
            </div>
            <div class="card-body">
                @php
                    $badge = [
                        'programado' => 'primary',
                        'en_curso' => 'info',
                        'cerrado' => 'success',
                        'cancelado' => 'secondary',
                    ][$curso->estado] ?? 'secondary';
                @endphp
                <dl class="row mb-0">
                    <dt class="col-sm-5">Estado</dt>
                    <dd class="col-sm-7"><span class="badge badge-{{ $badge }}">{{ $curso->estado_label }}</span></dd>
                    <dt class="col-sm-5">Horas</dt>
                    <dd class="col-sm-7">{{ $curso->horas_totales }}</dd>
                    <dt class="col-sm-5">Modalidad</dt>
                    <dd class="col-sm-7">
                        @foreach($curso->modalidades as $modalidad)
                            <span class="badge badge-light border">{{ $modalidad }}</span>
                        @endforeach
                    </dd>
                    @if($curso->requiere_calificacion)
                        <dt class="col-sm-5">Minima</dt>
                        <dd class="col-sm-7">{{ $curso->calificacion_minima }}/100</dd>
                    @endif
                    <dt class="col-sm-5">Instructor</dt>
                    <dd class="col-sm-7">{{ optional($curso->instructor)->name ?: 'Sin instructor' }}</dd>
                    <dt class="col-sm-5">Unidad</dt>
                    <dd class="col-sm-7">{{ optional($curso->unidad)->nombre ?: 'Sin unidad' }}</dd>
                    <dt class="col-sm-5">Inicio</dt>
                    <dd class="col-sm-7">{{ $curso->fecha_inicio ? $curso->fecha_inicio->format('d/m/Y H:i') : 'N/A' }}</dd>
                    <dt class="col-sm-5">Termino</dt>
                    <dd class="col-sm-7">{{ $curso->fecha_fin ? $curso->fecha_fin->format('d/m/Y H:i') : 'N/A' }}</dd>
                    <dt class="col-sm-5">Lugar</dt>
                    <dd class="col-sm-7">{{ $curso->lugar ?: 'N/A' }}</dd>
                    <dt class="col-sm-5">Cupo</dt>
                    <dd class="col-sm-7">{{ $curso->cupo ?: 'Sin limite' }}</dd>
                </dl>
                @if($curso->descripcion)
                    <hr>
                    <p class="mb-0">{{ $curso->descripcion }}</p>
                @endif
            </div>
            @if($puedeGestionar && $curso->puede_modificarse)
                <div class="card-footer">
                    <form method="POST" action="{{ route('licencias_puntos.cursos.cerrar', $curso) }}" class="js-confirm-close">
                        @csrf
                        <button class="btn btn-success btn-block">
                            <i class="fa-solid fa-lock"></i> Cerrar y acreditar
                        </button>
                    </form>
                </div>
            @endif
        </div>

        @if($curso->clase_en_vivo)
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">Clase en vivo</h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-6">Silenciar al entrar</dt>
                        <dd class="col-sm-6">{{ $curso->bbb_mute_on_start && !$curso->bbb_anyone_can_talk ? 'Si' : 'No' }}</dd>
                        <dt class="col-sm-6">Microfono bloqueado</dt>
                        <dd class="col-sm-6">{{ $curso->bbb_lock_viewers_microphone && !$curso->bbb_anyone_can_talk ? 'Si' : 'No' }}</dd>
                        <dt class="col-sm-6">Todos pueden hablar</dt>
                        <dd class="col-sm-6">{{ $curso->bbb_anyone_can_talk ? 'Si' : 'No' }}</dd>
                        <dt class="col-sm-6">Ultimo inicio</dt>
                        <dd class="col-sm-6">{{ $curso->bbb_last_started_at ? $curso->bbb_last_started_at->format('d/m/Y H:i') : 'Sin iniciar' }}</dd>
                    </dl>
                </div>
                @if($puedeGestionar && $curso->puede_modificarse)
                    <div class="card-footer">
                        @if($bbbDisponible)
                            <form method="POST" action="{{ route('licencias_puntos.cursos.aula.iniciar', $curso) }}" target="_blank">
                                @csrf
                                <button class="btn btn-info btn-block">
                                    <i class="fa-solid fa-video"></i> Iniciar aula como instructor
                                </button>
                            </form>
                        @else
                            <div class="alert alert-warning mb-0">
                                Falta configurar BIGBLUEBUTTON_ENABLED, BIGBLUEBUTTON_URL y BIGBLUEBUTTON_SECRET.
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    </div>

    <div class="col-lg-8">
        <div class="row">
            <div class="col-md-3 col-sm-6">
                <div class="info-box bg-info">
                    <span class="info-box-icon"><i class="fa-solid fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Inscritos</span>
                        <span class="info-box-number">{{ number_format($stats['total']) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="info-box bg-primary">
                    <span class="info-box-icon"><i class="fa-solid fa-clock"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">15 horas</span>
                        <span class="info-box-number">{{ number_format($stats['horas_completas']) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="info-box bg-success">
                    <span class="info-box-icon"><i class="fa-solid fa-check"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Acreditados</span>
                        <span class="info-box-number">{{ number_format($stats['acreditados']) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="info-box bg-warning">
                    <span class="info-box-icon"><i class="fa-solid fa-plus"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Puntos</span>
                        <span class="info-box-number">{{ number_format($stats['puntos']) }}</span>
                    </div>
                </div>
            </div>
        </div>

        @if($curso->materiales_pdf || $curso->materiales->count())
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">Materiales del curso</h3>
                </div>
                <div class="card-body">
                    @forelse($curso->materiales as $material)
                        <div class="d-flex align-items-center justify-content-between border-bottom py-2">
                            <div>
                                <strong>{{ $material->titulo }}</strong>
                                <span class="badge badge-light border">{{ $material->tipo_label }}</span>
                                @if(!$material->activo)
                                    <span class="badge badge-secondary">Inactivo</span>
                                @endif
                                @if($material->tipo === \App\Models\LicenciaPuntoCursoMaterial::TIPO_PDF && $material->archivo_url)
                                    <a href="{{ $material->archivo_url }}" class="d-block small" target="_blank">Abrir PDF</a>
                                @elseif($material->tipo === \App\Models\LicenciaPuntoCursoMaterial::TIPO_LINK && $material->url)
                                    <a href="{{ $material->url }}" class="d-block small" target="_blank">{{ $material->url }}</a>
                                @elseif($material->contenido)
                                    <small class="d-block text-muted">{{ \Illuminate\Support\Str::limit($material->contenido, 180) }}</small>
                                @endif
                            </div>
                            @if($puedeGestionar && $curso->puede_modificarse)
                                <form method="POST" action="{{ route('licencias_puntos.cursos.materiales.destroy', [$curso, $material]) }}" class="js-confirm-delete">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted mb-0">Sin materiales capturados.</p>
                    @endforelse
                </div>
                @if($puedeGestionar && $curso->puede_modificarse && $curso->materiales_pdf)
                    <form method="POST" action="{{ route('licencias_puntos.cursos.materiales.store', $curso) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="card-footer">
                            <div class="row">
                                <div class="col-md-3">
                                    <input type="text" name="titulo" class="form-control form-control-sm" placeholder="Titulo" required maxlength="180">
                                </div>
                                <div class="col-md-2">
                                    <select name="tipo" class="form-control form-control-sm custom-select">
                                        <option value="pdf">PDF</option>
                                        <option value="link">Link</option>
                                        <option value="texto">Texto</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input type="file" name="archivo" class="form-control form-control-sm" accept="application/pdf">
                                </div>
                                <div class="col-md-3">
                                    <input type="url" name="url" class="form-control form-control-sm" placeholder="https://...">
                                </div>
                                <div class="col-md-1 text-right">
                                    <button class="btn btn-sm btn-info">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                                <div class="col-12 mt-2">
                                    <input type="text" name="contenido" class="form-control form-control-sm" placeholder="Texto breve si el material no es PDF ni link">
                                </div>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        @endif

        @if($puedeGestionar && $curso->puede_modificarse)
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title">Agregar participante</h3>
            </div>
            <form method="POST" action="{{ route('licencias_puntos.cursos.participantes.store', $curso) }}">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Licencia</label>
                                <input type="text" name="numero_licencia" class="form-control" value="{{ old('numero_licencia') }}" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Titular</label>
                                <input type="text" name="titular_nombre" class="form-control" value="{{ old('titular_nombre') }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Horas</label>
                                <input type="number" name="asistencia_horas" class="form-control" value="{{ old('asistencia_horas', $curso->horas_totales) }}" min="0" max="{{ $curso->horas_totales }}" step="0.5">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>CURP</label>
                                <input type="text" name="curp" class="form-control" value="{{ old('curp') }}" maxlength="18">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Telefono</label>
                                <input type="text" name="telefono" class="form-control" value="{{ old('telefono') }}">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group mb-0">
                                <label>Observaciones</label>
                                <input type="text" name="observaciones" class="form-control" value="{{ old('observaciones') }}">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-right">
                    <button class="btn btn-success">
                        <i class="fa-solid fa-user-plus"></i> Agregar
                    </button>
                </div>
            </form>
        </div>
        @endif
    </div>
</div>

<div class="card card-outline card-secondary">
    <div class="card-header">
        <h3 class="card-title">Participantes</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped mb-0">
            <thead>
                <tr>
                    <th>Licencia</th>
                    <th>Participante</th>
                    <th>Saldo</th>
                    <th>Horas</th>
                    @if($curso->examen_habilitado)
                        <th>Calificacion</th>
                    @endif
                    <th>Estado</th>
                    <th>Puntos</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($participantes as $participante)
                    @php
                        $estadoBadge = [
                            'inscrito' => 'info',
                            'acreditado' => 'success',
                            'no_acreditado' => 'warning',
                            'cancelado' => 'secondary',
                        ][$participante->estado] ?? 'secondary';
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $participante->numero_licencia }}</strong>
                            @if($participante->cuenta)
                                <a href="{{ route('licencias_puntos.show', $participante->cuenta) }}" class="d-block small">Ver cuenta</a>
                            @else
                                <small class="d-block text-warning">Sin cuenta de puntos</small>
                            @endif
                        </td>
                        <td>
                            {{ $participante->titular_nombre }}
                            @if($participante->curp)
                                <small class="d-block text-muted">{{ $participante->curp }}</small>
                            @endif
                            @if($participante->observaciones)
                                <small class="d-block text-muted">{{ $participante->observaciones }}</small>
                            @endif
                        </td>
                        <td>
                            @if($participante->cuenta)
                                {{ $participante->cuenta->saldo_actual }} / {{ \App\Models\LicenciaPuntoCuenta::SALDO_MAXIMO }}
                            @else
                                N/A
                            @endif
                        </td>
                        <td style="min-width: 130px;">
                            @if($puedeGestionar && $curso->puede_modificarse && $participante->estado !== \App\Models\LicenciaPuntoCursoParticipante::ESTADO_ACREDITADO)
                                <form method="POST" action="{{ route('licencias_puntos.cursos.participantes.update', [$curso, $participante]) }}" class="form-inline">
                                    @csrf
                                    @method('PUT')
                                    <input type="number" name="asistencia_horas" class="form-control form-control-sm mr-1" value="{{ $participante->asistencia_horas }}" min="0" max="{{ $curso->horas_totales }}" step="0.5" style="width: 82px;">
                                    <input type="hidden" name="observaciones" value="{{ $participante->observaciones }}">
                                    <button class="btn btn-sm btn-outline-info">
                                        <i class="fa-solid fa-save"></i>
                                    </button>
                                </form>
                            @else
                                {{ number_format($participante->asistencia_horas, 1) }}
                            @endif
                        </td>
                        @if($curso->examen_habilitado)
                            <td style="min-width: 145px;">
                                @if($puedeGestionar && $curso->puede_modificarse && $participante->estado !== \App\Models\LicenciaPuntoCursoParticipante::ESTADO_ACREDITADO && $curso->calificacion_por_instructor)
                                    <form method="POST" action="{{ route('licencias_puntos.cursos.participantes.calificacion', [$curso, $participante]) }}" class="form-inline">
                                        @csrf
                                        @method('PUT')
                                        <input type="number" name="calificacion" class="form-control form-control-sm mr-1" value="{{ $participante->calificacion }}" min="0" max="100" style="width: 82px;">
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="fa-solid fa-save"></i>
                                        </button>
                                    </form>
                                    <small class="{{ $participante->cumple_calificacion ? 'text-success' : 'text-warning' }}">
                                        Minima {{ $curso->calificacion_minima }}
                                    </small>
                                @else
                                    @if(is_null($participante->calificacion))
                                        <span class="text-muted">Sin calificar</span>
                                    @else
                                        <span class="{{ $participante->cumple_calificacion ? 'text-success' : 'text-warning' }}">{{ $participante->calificacion }}/100</span>
                                    @endif
                                @endif
                            </td>
                        @endif
                        <td><span class="badge badge-{{ $estadoBadge }}">{{ $participante->estado_label }}</span></td>
                        <td>
                            @if($participante->puntos_acreditados > 0)
                                <span class="text-success">+{{ $participante->puntos_acreditados }}</span>
                            @else
                                <span class="text-muted">0</span>
                            @endif
                        </td>
                        <td class="text-right">
                            @if($curso->clase_en_vivo && $bbbDisponible && $curso->bbb_create_time)
                                @php
                                    $aulaParticipanteUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                                        'licencias_puntos.cursos.aula.participante',
                                        now('America/Mexico_City')->addDays(30),
                                        [$curso, $participante]
                                    );
                                @endphp
                                <a href="{{ $aulaParticipanteUrl }}" class="btn btn-sm btn-outline-primary" target="_blank" title="Aula participante">
                                    <i class="fa-solid fa-video"></i>
                                </a>
                            @endif
                            @if($puedeGestionar && $curso->puede_modificarse && $participante->estado !== \App\Models\LicenciaPuntoCursoParticipante::ESTADO_ACREDITADO)
                                <form method="POST" action="{{ route('licencias_puntos.cursos.participantes.acreditar', [$curso, $participante]) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-success" {{ !$participante->puede_acreditarse ? 'disabled' : '' }}>
                                        <i class="fa-solid fa-graduation-cap"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('licencias_puntos.cursos.participantes.destroy', [$curso, $participante]) }}" class="d-inline js-confirm-delete">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            @elseif($participante->movimiento)
                                <small class="text-muted">Movimiento #{{ $participante->movimiento_id }}</small>
                            @else
                                <small class="text-muted">Sin acciones</small>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $curso->examen_habilitado ? 8 : 7 }}" class="text-center text-muted py-4">No hay participantes registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $participantes->links() }}
    </div>
</div>
@stop

@section('css')
<style>
    .table td {
        vertical-align: middle;
    }
</style>
@stop

@section('js')
<script>
@if (session('success'))
Swal.fire({ icon: 'success', title: '{{ session('success') }}', timer: 3200, showConfirmButton: false });
@endif

document.querySelectorAll('.js-confirm-close').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        Swal.fire({
            icon: 'question',
            title: 'Cerrar curso',
            text: 'Se acreditaran solo participantes con 15 horas completas y calificacion aprobatoria cuando aplique.',
            showCancelButton: true,
            confirmButtonText: 'Cerrar y acreditar',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (result.isConfirmed) form.submit();
        });
    });
});

document.querySelectorAll('.js-confirm-delete').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Retirar participante',
            text: 'El participante se quitara del curso.',
            showCancelButton: true,
            confirmButtonText: 'Retirar',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (result.isConfirmed) form.submit();
        });
    });
});
</script>
@stop
