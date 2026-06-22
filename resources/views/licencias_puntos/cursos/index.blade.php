@extends('adminlte::page')

@section('title', 'Cursos de recuperacion')

@section('content_header')
    <div class="d-flex flex-wrap align-items-center justify-content-between">
        <div>
            <h1 class="mb-1">Cursos de recuperacion de puntos</h1>
            <p class="text-muted mb-0">Cursos de 15 horas para acreditar recuperacion de puntos.</p>
        </div>
        @if($puedeCrearCursos)
            <a href="{{ route('licencias_puntos.cursos.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Nuevo curso
            </a>
        @endif
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
    <div class="col-md-3 col-sm-6">
        <div class="info-box bg-info">
            <span class="info-box-icon"><i class="fa-solid fa-chalkboard-user"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Cursos</span>
                <span class="info-box-number">{{ number_format($stats['total']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="info-box bg-primary">
            <span class="info-box-icon"><i class="fa-solid fa-calendar-check"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Programados</span>
                <span class="info-box-number">{{ number_format($stats['programados']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="info-box bg-success">
            <span class="info-box-icon"><i class="fa-solid fa-graduation-cap"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Acreditados</span>
                <span class="info-box-number">{{ number_format($stats['acreditados']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="info-box bg-secondary">
            <span class="info-box-icon"><i class="fa-solid fa-lock"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Cerrados</span>
                <span class="info-box-number">{{ number_format($stats['cerrados']) }}</span>
            </div>
        </div>
    </div>
</div>

<div class="card card-outline card-secondary">
    <div class="card-header">
        <h3 class="card-title">Cursos capturados</h3>
        <div class="card-tools">
            <form method="GET" class="form-inline">
                <input type="text" name="buscar" class="form-control form-control-sm mr-2" value="{{ request('buscar') }}" placeholder="Folio, curso o lugar">
                <select name="estado" class="form-control form-control-sm custom-select mr-2">
                    <option value="">Todos</option>
                    @foreach([
                        'programado' => 'Programado',
                        'en_curso' => 'En curso',
                        'cerrado' => 'Cerrado',
                        'cancelado' => 'Cancelado',
                    ] as $value => $label)
                        <option value="{{ $value }}" {{ request('estado') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-info">
                    <i class="fa-solid fa-filter"></i>
                </button>
            </form>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped mb-0">
            <thead>
                <tr>
                    <th>Curso</th>
                    <th>Instructor</th>
                    <th>Fechas</th>
                    <th>Participantes</th>
                    <th>Estado</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cursos as $curso)
                    @php
                        $badge = [
                            'programado' => 'primary',
                            'en_curso' => 'info',
                            'cerrado' => 'success',
                            'cancelado' => 'secondary',
                        ][$curso->estado] ?? 'secondary';
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $curso->folio }}</strong>
                            <small class="d-block text-muted">{{ $curso->nombre }}</small>
                            <small class="d-block text-info">{{ $curso->horas_totales }} horas</small>
                            <div class="mt-1">
                                @foreach($curso->modalidades as $modalidad)
                                    <span class="badge badge-light border">{{ $modalidad }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td>
                            {{ optional($curso->instructor)->name ?: 'Sin instructor' }}
                            <small class="d-block text-muted">{{ optional($curso->unidad)->nombre ?: 'Sin unidad' }}</small>
                        </td>
                        <td>
                            {{ $curso->fecha_inicio ? $curso->fecha_inicio->format('d/m/Y H:i') : 'Sin inicio' }}
                            <small class="d-block text-muted">
                                {{ $curso->fecha_fin ? $curso->fecha_fin->format('d/m/Y H:i') : 'Sin cierre programado' }}
                            </small>
                        </td>
                        <td>
                            <span class="badge badge-info">{{ $curso->participantes_count }} inscritos</span>
                            <span class="badge badge-success">{{ $curso->participantes_acreditados_count }} acreditados</span>
                        </td>
                        <td><span class="badge badge-{{ $badge }}">{{ $curso->estado_label }}</span></td>
                        <td class="text-right">
                            <a href="{{ route('licencias_puntos.cursos.show', $curso) }}" class="btn btn-sm btn-info">
                                <i class="fa-regular fa-eye"></i>
                            </a>
                            @if($puedeCrearCursos && $curso->puede_modificarse && (auth()->user()->hasRole('Superadmin') || (int) $curso->instructor_id === auth()->id()))
                                <a href="{{ route('licencias_puntos.cursos.edit', $curso) }}" class="btn btn-sm btn-warning">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No hay cursos de recuperacion capturados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $cursos->links() }}
    </div>
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

    .table td {
        vertical-align: middle;
    }
</style>
@stop

@section('js')
<script>
@if (session('success'))
Swal.fire({ icon: 'success', title: '{{ session('success') }}', timer: 3000, showConfirmButton: false });
@endif
</script>
@stop
