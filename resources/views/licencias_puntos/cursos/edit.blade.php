@extends('adminlte::page')

@section('title', 'Editar curso')

@section('content_header')
    <div class="d-flex flex-wrap align-items-center justify-content-between">
        <div>
            <h1 class="mb-1">{{ $curso->folio }}</h1>
            <p class="text-muted mb-0">Editar curso de recuperacion.</p>
        </div>
        <a href="{{ route('licencias_puntos.cursos.show', $curso) }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Volver
        </a>
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

<div class="card card-outline card-warning">
    <div class="card-header">
        <h3 class="card-title">Datos del curso</h3>
    </div>
    <form method="POST" action="{{ route('licencias_puntos.cursos.update', $curso) }}">
        @csrf
        @method('PUT')
        <div class="card-body">
            <div class="alert alert-info mb-3">
                <strong>Duracion fija:</strong> {{ $curso->horas_totales }} horas.
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Nombre del curso</label>
                        <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $curso->nombre) }}" required maxlength="180">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Estado</label>
                        <select name="estado" class="form-control custom-select">
                            @foreach([
                                'programado' => 'Programado',
                                'en_curso' => 'En curso',
                                'cancelado' => 'Cancelado',
                            ] as $value => $label)
                                <option value="{{ $value }}" {{ old('estado', $curso->estado) === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Instructor</label>
                        <select name="instructor_id" class="form-control custom-select">
                            @foreach($instructores as $instructor)
                                <option value="{{ $instructor->id }}" {{ old('instructor_id', $curso->instructor_id) == $instructor->id ? 'selected' : '' }}>
                                    {{ $instructor->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Fecha de inicio</label>
                        <input type="datetime-local" name="fecha_inicio" class="form-control" value="{{ old('fecha_inicio', $curso->fecha_inicio ? $curso->fecha_inicio->format('Y-m-d\TH:i') : null) }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Fecha de termino</label>
                        <input type="datetime-local" name="fecha_fin" class="form-control" value="{{ old('fecha_fin', $curso->fecha_fin ? $curso->fecha_fin->format('Y-m-d\TH:i') : null) }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Cupo</label>
                        <input type="number" name="cupo" class="form-control" value="{{ old('cupo', $curso->cupo) }}" min="1" max="1000" placeholder="Sin limite">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-md-0">
                        <label>Lugar</label>
                        <input type="text" name="lugar" class="form-control" value="{{ old('lugar', $curso->lugar) }}" maxlength="180">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-md-0">
                        <label>Observaciones internas</label>
                        <input type="text" name="observaciones" class="form-control" value="{{ old('observaciones', $curso->observaciones) }}">
                    </div>
                </div>
                <div class="col-12 mt-3">
                    <div class="form-group mb-0">
                        <label>Descripcion</label>
                        <textarea name="descripcion" class="form-control" rows="3">{{ old('descripcion', $curso->descripcion) }}</textarea>
                    </div>
                </div>
            </div>

            <hr>

            <h5 class="mb-3">Modalidad y aula</h5>
            <div class="row">
                <div class="col-md-4">
                    <input type="hidden" name="clase_en_vivo" value="0">
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" name="clase_en_vivo" value="1" class="custom-control-input" id="clase_en_vivo" {{ old('clase_en_vivo', $curso->clase_en_vivo) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="clase_en_vivo">Clase en vivo BBB</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <input type="hidden" name="materiales_pdf" value="0">
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" name="materiales_pdf" value="1" class="custom-control-input" id="materiales_pdf" {{ old('materiales_pdf', $curso->materiales_pdf) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="materiales_pdf">Materiales PDF/link/texto</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <input type="hidden" name="examen_habilitado" value="0">
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" name="examen_habilitado" value="1" class="custom-control-input" id="examen_habilitado" {{ old('examen_habilitado', $curso->examen_habilitado) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="examen_habilitado">Examen o evaluacion</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <input type="hidden" name="calificacion_por_instructor" value="0">
                    <div class="custom-control custom-checkbox mb-3">
                        <input type="checkbox" name="calificacion_por_instructor" value="1" class="custom-control-input" id="calificacion_por_instructor" {{ old('calificacion_por_instructor', $curso->calificacion_por_instructor) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="calificacion_por_instructor">El instructor captura calificacion</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Calificacion minima</label>
                        <input type="number" name="calificacion_minima" class="form-control" value="{{ old('calificacion_minima', $curso->calificacion_minima ?: 80) }}" min="0" max="100">
                    </div>
                </div>
            </div>

            <h5 class="mb-3">Controles de clase en vivo</h5>
            <div class="row">
                <div class="col-md-3">
                    <input type="hidden" name="bbb_mute_on_start" value="0">
                    <div class="custom-control custom-checkbox mb-3">
                        <input type="checkbox" name="bbb_mute_on_start" value="1" class="custom-control-input" id="bbb_mute_on_start" {{ old('bbb_mute_on_start', $curso->bbb_mute_on_start) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="bbb_mute_on_start">Entrar silenciados</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <input type="hidden" name="bbb_lock_viewers_microphone" value="0">
                    <div class="custom-control custom-checkbox mb-3">
                        <input type="checkbox" name="bbb_lock_viewers_microphone" value="1" class="custom-control-input" id="bbb_lock_viewers_microphone" {{ old('bbb_lock_viewers_microphone', $curso->bbb_lock_viewers_microphone) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="bbb_lock_viewers_microphone">Bloquear microfono alumno</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <input type="hidden" name="bbb_anyone_can_talk" value="0">
                    <div class="custom-control custom-checkbox mb-3">
                        <input type="checkbox" name="bbb_anyone_can_talk" value="1" class="custom-control-input" id="bbb_anyone_can_talk" {{ old('bbb_anyone_can_talk', $curso->bbb_anyone_can_talk) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="bbb_anyone_can_talk">Permitir que todos hablen</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <input type="hidden" name="bbb_record" value="0">
                    <div class="custom-control custom-checkbox mb-3">
                        <input type="checkbox" name="bbb_record" value="1" class="custom-control-input" id="bbb_record" {{ old('bbb_record', $curso->bbb_record) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="bbb_record">Permitir grabacion</label>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer text-right">
            <button class="btn btn-warning">
                <i class="fa-solid fa-save"></i> Guardar cambios
            </button>
        </div>
    </form>
</div>
@stop

@section('css')
<style>
    select.form-control,
    select.custom-select {
        background-color: #12263c;
        color: #f8fafc;
        border-color: rgba(125, 178, 225, .45);
    }

    select.form-control option,
    select.custom-select option {
        background-color: #12263c;
        color: #f8fafc;
    }
</style>
@stop
