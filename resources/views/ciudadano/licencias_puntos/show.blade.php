@extends('adminlte::page')

@section('title', 'Detalle de licencia')

@section('content_header')
    <div class="d-flex flex-wrap align-items-center justify-content-between">
        <div>
            <h1 class="mb-1">{{ $cuenta->numero_licencia }}</h1>
            <p class="text-muted mb-0">{{ $cuenta->titular_nombre }}</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('ciudadano.licencias_puntos.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </a>
            <form method="POST" action="{{ route('ciudadano.licencias_puntos.licencias.destroy', $cuenta) }}" class="js-unlink-form">
                @csrf
                @method('DELETE')
                <button class="btn btn-outline-danger">
                    <i class="fa-solid fa-link-slash"></i> Desvincular
                </button>
            </form>
        </div>
    </div>
@stop

@section('content')
<div class="row">
    <div class="col-lg-4">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Saldo actual</h3>
            </div>
            <div class="card-body text-center">
                @php
                    $badge = ['normal' => 'success', 'advertencia' => 'warning', 'critico' => 'danger', 'agotado' => 'dark'][$cuenta->nivel_saldo] ?? 'secondary';
                @endphp
                <div class="display-4 font-weight-bold text-{{ $badge }}">
                    {{ $cuenta->saldo_actual }} / {{ \App\Models\LicenciaPuntoCuenta::SALDO_MAXIMO }}
                </div>
                <span class="badge badge-{{ $badge }} mt-2">{{ strtoupper($cuenta->nivel_saldo) }}</span>
                <hr>
                <dl class="row text-left mb-0">
                    <dt class="col-sm-5">Estado</dt>
                    <dd class="col-sm-7">{{ $cuenta->estado_label }}</dd>
                    <dt class="col-sm-5">Puntos perdidos</dt>
                    <dd class="col-sm-7 text-danger">{{ $stats['perdidos'] }}</dd>
                    <dt class="col-sm-5">Puntos ganados</dt>
                    <dd class="col-sm-7 text-success">{{ $stats['ganados'] }}</dd>
                    <dt class="col-sm-5">Reincidencias</dt>
                    <dd class="col-sm-7">{{ $cuenta->reincidencias_cero }}</dd>
                </dl>
            </div>
        </div>

        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">Recuperacion por tiempo</h3>
            </div>
            <div class="card-body">
                @if(!$stats['recuperacion'])
                    <div class="text-success font-weight-bold">Tu licencia tiene saldo completo.</div>
                @elseif($stats['recuperacion']->isFuture())
                    <div class="h4 mb-1">{{ $stats['recuperacion']->format('d/m/Y') }}</div>
                    <p class="text-muted mb-0">Faltan {{ $stats['dias_recuperacion'] }} dias para cumplir el periodo de recuperacion por tiempo.</p>
                @else
                    <div class="text-info font-weight-bold">Tu licencia ya cumple el periodo de recuperacion por tiempo.</div>
                @endif
            </div>
        </div>

        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Datos de licencia</h3>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Tipo</dt>
                    <dd class="col-sm-7">{{ \App\Support\LicenciaTipoCatalog::label($cuenta->tipo_licencia) ?: 'N/A' }}</dd>
                    <dt class="col-sm-5">CURP</dt>
                    <dd class="col-sm-7">{{ $cuenta->curp ? substr($cuenta->curp, 0, 4) . '**********' . substr($cuenta->curp, -4) : 'N/A' }}</dd>
                    <dt class="col-sm-5">Emision</dt>
                    <dd class="col-sm-7">{{ $cuenta->fecha_emision ? $cuenta->fecha_emision->format('d/m/Y') : 'N/A' }}</dd>
                    <dt class="col-sm-5">Vencimiento</dt>
                    <dd class="col-sm-7">{{ $cuenta->fecha_vencimiento ? $cuenta->fecha_vencimiento->format('d/m/Y') : 'N/A' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title">Cursos de esta licencia</h3>
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
                    <div class="curso-item">
                        <div class="d-flex flex-wrap justify-content-between">
                            <div>
                                <strong>{{ optional($curso)->folio ?: 'Sin folio' }}</strong>
                                <div>{{ optional($curso)->nombre ?: 'Curso no disponible' }}</div>
                                <small class="text-muted">
                                    {{ optional(optional($curso)->fecha_inicio)->format('d/m/Y H:i') ?: 'Sin inicio' }}
                                    @if(optional($curso)->fecha_fin)
                                        - {{ $curso->fecha_fin->format('d/m/Y H:i') }}
                                    @endif
                                </small>
                            </div>
                            <div class="text-right">
                                <span class="badge badge-{{ $estadoBadge }}">{{ $participante->estado_label }}</span>
                                <div class="mt-1">
                                    @if((int) $participante->puntos_acreditados > 0)
                                        <span class="text-success">+{{ $participante->puntos_acreditados }} puntos</span>
                                    @else
                                        <span class="text-muted">0 puntos</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if($curso)
                            <div class="mt-2">
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
                    <p class="text-muted mb-0">No hay cursos vinculados a esta licencia.</p>
                @endforelse
            </div>
        </div>

        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Historial de movimientos</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Movimiento</th>
                            <th>Detalle</th>
                            <th>Puntos</th>
                            <th>Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movimientos as $movimiento)
                            <tr>
                                <td>{{ $movimiento->fecha_movimiento ? $movimiento->fecha_movimiento->format('d/m/Y H:i') : 'N/A' }}</td>
                                <td>{{ str_replace('_', ' ', ucfirst($movimiento->tipo)) }}</td>
                                <td>
                                    <strong>{{ optional($movimiento->infraccion)->nombre ?: $movimiento->referencia }}</strong>
                                    <small class="d-block text-muted">{{ $movimiento->descripcion }}</small>
                                </td>
                                <td>
                                    @if($movimiento->puntos > 0)
                                        <span class="text-success">+{{ $movimiento->puntos }}</span>
                                    @elseif($movimiento->puntos < 0)
                                        <span class="text-danger">{{ $movimiento->puntos }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td>{{ $movimiento->saldo_anterior }} -> {{ $movimiento->saldo_nuevo }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Sin movimientos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{ $movimientos->links() }}
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    .table td { vertical-align: middle; }
    .curso-item {
        border-bottom: 1px solid rgba(255,255,255,.12);
        padding: 12px 0;
    }
    .curso-item:first-child { padding-top: 0; }
    .curso-item:last-child { border-bottom: 0; padding-bottom: 0; }
    .btn-group form { display: inline-block; }
</style>
@stop

@section('js')
<script>
@if (session('success'))
Swal.fire({ icon: 'success', title: '{{ session('success') }}', timer: 2600, showConfirmButton: false });
@endif

document.querySelectorAll('.js-unlink-form').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Desvincular licencia',
            text: 'Ya no aparecera en tu portal hasta que la vuelvas a vincular con numero y CURP.',
            showCancelButton: true,
            confirmButtonText: 'Desvincular',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (result.isConfirmed) form.submit();
        });
    });
});
</script>
@stop
