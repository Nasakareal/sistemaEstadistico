@extends('adminlte::page')

@section('title', 'Mis cursos')

@section('content_header')
    <div class="d-flex flex-wrap align-items-center justify-content-between">
        <div>
            <h1 class="mb-1">Mis cursos</h1>
            <p class="text-muted mb-0">Cursos registrados para tus licencias vinculadas.</p>
        </div>
        <a href="{{ route('ciudadano.licencias_puntos.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Mis puntos
        </a>
    </div>
@stop

@section('content')
<div class="card card-outline card-success">
    <div class="card-header">
        <h3 class="card-title">Historial y agenda de cursos</h3>
    </div>
    <div class="card-body">
        @forelse($participantes as $participante)
            @php
                $curso = $participante->curso;
                $aulaUrl = \App\Http\Controllers\CiudadanoLicenciaPuntosController::aulaUrlSiDisponible($participante);
                $estadoBadge = [
                    'inscrito' => 'info',
                    'acreditado' => 'success',
                    'no_acreditado' => 'warning',
                    'cancelado' => 'secondary',
                ][$participante->estado] ?? 'secondary';
            @endphp
            <div class="curso-row">
                <div class="d-flex flex-wrap align-items-start justify-content-between">
                    <div>
                        <div class="h5 mb-1">{{ optional($curso)->nombre ?: 'Curso no disponible' }}</div>
                        <div class="text-muted">
                            <strong>{{ optional($curso)->folio ?: 'Sin folio' }}</strong>
                            <span class="mx-1">|</span>
                            Licencia {{ $participante->numero_licencia }}
                        </div>
                        <div class="mt-2">
                            <span class="badge badge-light border">{{ optional($curso)->horas_totales ?: 15 }} horas</span>
                            @if($curso)
                                @foreach($curso->modalidades as $modalidad)
                                    <span class="badge badge-light border">{{ $modalidad }}</span>
                                @endforeach
                            @endif
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="badge badge-{{ $estadoBadge }}">{{ $participante->estado_label }}</span>
                        <div class="mt-2">
                            @if((int) $participante->puntos_acreditados > 0)
                                <span class="text-success">+{{ $participante->puntos_acreditados }} puntos</span>
                            @else
                                <span class="text-muted">0 puntos</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-3">
                        <small class="text-muted d-block">Inicio</small>
                        {{ optional(optional($curso)->fecha_inicio)->format('d/m/Y H:i') ?: 'Sin fecha' }}
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Termino</small>
                        {{ optional(optional($curso)->fecha_fin)->format('d/m/Y H:i') ?: 'Sin fecha' }}
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Horas registradas</small>
                        {{ number_format((float) $participante->asistencia_horas, 1) }}
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Calificacion</small>
                        {{ is_null($participante->calificacion) ? 'Sin calificar' : $participante->calificacion . '/100' }}
                    </div>
                </div>

                @if($curso)
                    <div class="mt-3">
                        @if($aulaUrl)
                            <a href="{{ $aulaUrl }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fa-solid fa-video"></i> Entrar al aula
                            </a>
                        @endif

                        @foreach($curso->materiales as $material)
                            @php $url = \App\Http\Controllers\CiudadanoLicenciaPuntosController::materialUrl($material); @endphp
                            @if($url)
                                <a href="{{ $url }}" target="_blank" class="btn btn-sm btn-outline-info">
                                    <i class="fa-solid fa-file-lines"></i> {{ $material->titulo }}
                                </a>
                            @elseif($material->tipo === \App\Models\LicenciaPuntoCursoMaterial::TIPO_TEXTO && $material->contenido)
                                <div class="small text-muted mt-2">{{ $material->titulo }}: {{ $material->contenido }}</div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="text-center text-muted py-5">
                <i class="fa-solid fa-chalkboard-user fa-2x mb-3"></i>
                <div class="font-weight-bold">Sin cursos registrados</div>
                <div>Cuando una licencia vinculada sea inscrita a un curso, aparecera aqui.</div>
            </div>
        @endforelse
    </div>
    @if($participantes->hasPages())
        <div class="card-footer">
            {{ $participantes->links() }}
        </div>
    @endif
</div>
@stop

@section('css')
<style>
    .curso-row {
        border-bottom: 1px solid rgba(255,255,255,.12);
        padding: 16px 0;
    }
    .curso-row:first-child { padding-top: 0; }
    .curso-row:last-child { border-bottom: 0; padding-bottom: 0; }
</style>
@stop
