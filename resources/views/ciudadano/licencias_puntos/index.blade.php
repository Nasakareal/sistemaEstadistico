@extends('adminlte::page')

@section('title', 'Mis puntos de licencia')

@section('content_header')
    <div class="d-flex flex-wrap align-items-center justify-content-between">
        <div>
            <h1 class="mb-1">Mis puntos de licencia</h1>
            <p class="text-muted mb-0">Consulta privada de saldos, movimientos y cursos vinculados.</p>
        </div>
        <a href="{{ route('ciudadano.licencias_puntos.cursos') }}" class="btn btn-info">
            <i class="fa-solid fa-chalkboard-user"></i> Mis cursos
        </a>
    </div>
@stop

@section('content')
@if ($errors->any())
    <div class="alert alert-danger">
        <strong>No fue posible vincular la licencia.</strong>
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
            <span class="info-box-icon"><i class="fa-solid fa-id-card"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Licencias</span>
                <span class="info-box-number">{{ number_format($resumen['licencias']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="info-box bg-success">
            <span class="info-box-icon"><i class="fa-solid fa-gauge-high"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Puntos disponibles</span>
                <span class="info-box-number">{{ $resumen['saldo_total'] }} / {{ $resumen['saldo_maximo'] }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="info-box bg-warning">
            <span class="info-box-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">En alerta</span>
                <span class="info-box-number">{{ number_format($resumen['en_alerta']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="info-box bg-primary">
            <span class="info-box-icon"><i class="fa-solid fa-graduation-cap"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Cursos recientes</span>
                <span class="info-box-number">{{ number_format($participantes->count()) }}</span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Licencias vinculadas</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Licencia</th>
                            <th>Titular</th>
                            <th>Saldo</th>
                            <th>Recuperacion por tiempo</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cuentas as $cuenta)
                            @php
                                $badge = ['normal' => 'success', 'advertencia' => 'warning', 'critico' => 'danger', 'agotado' => 'dark'][$cuenta->nivel_saldo] ?? 'secondary';
                                $recuperacion = $cuenta->fecha_recuperacion;
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $cuenta->numero_licencia }}</strong>
                                    <small class="d-block text-muted">{{ \App\Support\LicenciaTipoCatalog::label($cuenta->tipo_licencia) ?: 'Tipo no especificado' }}</small>
                                </td>
                                <td>
                                    {{ $cuenta->titular_nombre }}
                                    @if($cuenta->curp)
                                        <small class="d-block text-muted">CURP: {{ substr($cuenta->curp, 0, 4) }}**********{{ substr($cuenta->curp, -4) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-{{ $badge }}">{{ $cuenta->saldo_actual }} / {{ \App\Models\LicenciaPuntoCuenta::SALDO_MAXIMO }}</span>
                                    <small class="d-block text-muted">{{ $cuenta->estado_label }}</small>
                                </td>
                                <td>
                                    @if(!$recuperacion)
                                        <span class="text-success">Saldo completo</span>
                                    @elseif($recuperacion->isFuture())
                                        {{ $recuperacion->format('d/m/Y') }}
                                        <small class="d-block text-muted">Faltan {{ now('America/Mexico_City')->diffInDays($recuperacion) }} dias</small>
                                    @else
                                        <span class="text-info">Ya cumple periodo</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('ciudadano.licencias_puntos.show', $cuenta) }}" class="btn btn-sm btn-info">
                                        <i class="fa-regular fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Aun no tienes licencias vinculadas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title">Cursos vinculados</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Curso</th>
                            <th>Licencia</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Puntos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($participantes as $participante)
                            @php
                                $curso = $participante->curso;
                                $estadoBadge = [
                                    'inscrito' => 'info',
                                    'acreditado' => 'success',
                                    'no_acreditado' => 'warning',
                                    'cancelado' => 'secondary',
                                ][$participante->estado] ?? 'secondary';
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ optional($curso)->folio ?: 'Sin folio' }}</strong>
                                    <small class="d-block text-muted">{{ optional($curso)->nombre ?: 'Curso no disponible' }}</small>
                                </td>
                                <td>{{ $participante->numero_licencia }}</td>
                                <td>
                                    {{ optional(optional($curso)->fecha_inicio)->format('d/m/Y H:i') ?: 'Sin fecha' }}
                                    <small class="d-block text-muted">{{ optional(optional($curso)->fecha_fin)->format('d/m/Y H:i') ?: '' }}</small>
                                </td>
                                <td><span class="badge badge-{{ $estadoBadge }}">{{ $participante->estado_label }}</span></td>
                                <td>
                                    @if((int) $participante->puntos_acreditados > 0)
                                        <span class="text-success">+{{ $participante->puntos_acreditados }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Sin cursos registrados para tus licencias.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-right">
                <a href="{{ route('ciudadano.licencias_puntos.cursos') }}" class="btn btn-success">
                    <i class="fa-solid fa-arrow-right"></i> Ver todos
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">Vincular licencia</h3>
            </div>
            <form method="POST" action="{{ route('ciudadano.licencias_puntos.licencias.store') }}" autocomplete="off">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="numero_licencia">Numero de licencia</label>
                        <input type="text" name="numero_licencia" id="numero_licencia" class="form-control @error('numero_licencia') is-invalid @enderror" value="{{ old('numero_licencia') }}" maxlength="80" required>
                        @error('numero_licencia')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                    <div class="form-group mb-0">
                        <label for="curp">CURP</label>
                        <input type="text" name="curp" id="curp" class="form-control text-uppercase @error('curp') is-invalid @enderror" value="{{ old('curp') }}" maxlength="18" minlength="18" required>
                        @error('curp')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-info btn-block">
                        <i class="fa-solid fa-link"></i> Vincular
                    </button>
                </div>
            </form>
        </div>

        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Acceso seguro</h3>
            </div>
            <div class="card-body">
                <p class="mb-0 text-muted">
                    Solo se muestra informacion de licencias vinculadas a tu usuario mediante numero de licencia y CURP coincidente.
                </p>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    .table td { vertical-align: middle; }
    .text-uppercase { text-transform: uppercase; }
</style>
@stop

@section('js')
<script>
@if (session('success'))
Swal.fire({ icon: 'success', title: '{{ session('success') }}', timer: 2600, showConfirmButton: false });
@endif
</script>
@stop
