@extends('adminlte::page')

@section('title', 'Constancia de Manejo')

@section('content_header')
    <div class="constancia-show-header">
        <h1>Constancia de Manejo {{ $constancia->folio }}</h1>
        <p>Control de examen, activación y vigencia</p>
    </div>
@stop

@section('content')
<div class="constancia-show-wrapper">

    <div class="constancia-show-card">
        <div class="constancia-show-card-header">
            <div>
                <h3>Información de la Constancia</h3>
                <span>Folio {{ $constancia->folio }}</span>
            </div>

            @if($constancia->estatus == 'ACTIVA')
                <span class="badge badge-success badge-status">ACTIVA</span>
            @elseif($constancia->estatus == 'IMPRESA_INACTIVA')
                <span class="badge badge-warning badge-status">INACTIVA</span>
            @elseif($constancia->estatus == 'EXPIRADA')
                <span class="badge badge-danger badge-status">EXPIRADA</span>
            @else
                <span class="badge badge-dark badge-status">CANCELADA</span>
            @endif
        </div>

        <div class="constancia-show-card-body">
            <div class="info-grid">
                <div class="info-item">
                    <label>Folio</label>
                    <strong>{{ $constancia->folio }}</strong>
                </div>

                <div class="info-item">
                    <label>Módulo</label>
                    <strong>{{ optional($constancia->modulo)->nombre ?? '—' }}</strong>
                </div>

                <div class="info-item">
                    <label>Solicitante</label>
                    <strong>{{ $constancia->nombre_solicitante ?? 'PENDIENTE' }}</strong>
                </div>

                <div class="info-item">
                    <label>CURP</label>
                    <strong>{{ $constancia->curp ?? '—' }}</strong>
                </div>

                <div class="info-item">
                    <label>Sexo</label>
                    <strong>{{ $constancia->sexo ?? '—' }}</strong>
                </div>

                <div class="info-item">
                    <label>Teléfono</label>
                    <strong>{{ $constancia->telefono ?? '—' }}</strong>
                </div>

                <div class="info-item">
                    <label>Tipo de licencia</label>
                    <strong>{{ $constancia->tipo_licencia ?? '—' }}</strong>
                </div>

                <div class="info-item">
                    <label>Tipo de examen</label>
                    <strong>{{ $constancia->tipo_examen ?? '—' }}</strong>
                </div>

                <div class="info-item">
                    <label>Resultado</label>
                    @if($constancia->examen)
                        @if($constancia->examen->resultado == 'APROBADO')
                            <span class="badge badge-success">APROBADO</span>
                        @else
                            <span class="badge badge-danger">REPROBADO</span>
                        @endif
                    @else
                        <span class="badge badge-secondary">SIN EXAMEN</span>
                    @endif
                </div>

                <div class="info-item">
                    <label>Impresión</label>
                    <strong>{{ $constancia->fecha_impresion ? $constancia->fecha_impresion->format('d/m/Y H:i') : '—' }}</strong>
                </div>

                <div class="info-item">
                    <label>Activación</label>
                    <strong>{{ $constancia->fecha_activacion ? $constancia->fecha_activacion->format('d/m/Y H:i') : '—' }}</strong>
                </div>

                <div class="info-item">
                    <label>Expiración</label>
                    <strong>{{ $constancia->fecha_expiracion ? $constancia->fecha_expiracion->format('d/m/Y H:i') : '—' }}</strong>
                </div>

                <div class="info-item">
                    <label>Activó</label>
                    <strong>{{ optional($constancia->peritoActivador)->name ?? '—' }}</strong>
                </div>
            </div>
        </div>

        <div class="constancia-show-card-footer">
            <a href="{{ route('constancias_manejo.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </a>

            <a href="{{ route('constancias_manejo.imprimir', $constancia->id) }}" class="btn btn-dark" target="_blank">
                <i class="fa-solid fa-print"></i> Imprimir
            </a>

            <a href="{{ route('constancias_manejo.reimprimir', $constancia->id) }}" class="btn btn-info" target="_blank">
                <i class="fa-solid fa-file-arrow-down"></i> Reimprimir
            </a>

            @if($constancia->estatus == 'IMPRESA_INACTIVA' && $constancia->examen && $constancia->examen->resultado == 'APROBADO')
                <form action="{{ route('constancias_manejo.activar', $constancia->id) }}" method="POST" class="form-inline">
                    @csrf
                    <button type="submit" class="btn btn-success btn-activar">
                        <i class="fa-solid fa-check"></i> Activar
                    </button>
                </form>
            @endif

            @if($constancia->estatus != 'CANCELADA' && $constancia->estatus != 'ACTIVA')
                <form action="{{ route('constancias_manejo.cancelar', $constancia->id) }}" method="POST" class="form-inline">
                    @csrf
                    <button type="button" class="btn btn-danger btn-cancelar">
                        <i class="fa-solid fa-ban"></i> Cancelar
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if($constancia->estatus == 'IMPRESA_INACTIVA')
        <div class="constancia-show-card mt-4">
            <div class="constancia-show-card-header">
                <div>
                    <h3>Examen en Línea</h3>
                    <span>El examen se genera sin gastar un folio impreso</span>
                </div>
                <i class="fa-solid fa-key"></i>
            </div>

            <div class="constancia-show-card-body">
                <div class="empty-box">
                    Genera el acceso desde la app en <strong>Nuevo examen</strong>. Si reprueba, esta constancia impresa sigue intacta; si aprueba, escanea este folio y actívalo con ese examen.
                </div>
            </div>
        </div>

        <div class="constancia-show-card mt-4">
            <div class="constancia-show-card-header">
                <div>
                    <h3>Capturar Examen Impreso</h3>
                    <span>Para personas que no harán examen digital</span>
                </div>
                <i class="fa-solid fa-clipboard-check"></i>
            </div>

            <form action="{{ route('constancias_manejo.capturar_examen_impreso', $constancia->id) }}" method="POST">
                @csrf

                <div class="constancia-show-card-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nombre del solicitante</label>
                            <input type="text" name="nombre_solicitante" class="form-control" value="{{ old('nombre_solicitante', $constancia->nombre_solicitante) }}" required>
                        </div>

                        <div class="form-group">
                            <label>CURP</label>
                            <input type="text" name="curp" class="form-control" maxlength="18" value="{{ old('curp', $constancia->curp) }}">
                        </div>

                        <div class="form-group">
                            <label>Sexo</label>
                            <select name="sexo" class="form-control" required>
                                <option value="">Seleccione</option>
                                <option value="HOMBRE" {{ old('sexo', $constancia->sexo) == 'HOMBRE' ? 'selected' : '' }}>Hombre</option>
                                <option value="MUJER" {{ old('sexo', $constancia->sexo) == 'MUJER' ? 'selected' : '' }}>Mujer</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Teléfono</label>
                            <input type="text" name="telefono" class="form-control" value="{{ old('telefono', $constancia->telefono) }}">
                        </div>

                        <div class="form-group">
                            <label>Tipo de licencia</label>
                            <select name="tipo_licencia" class="form-control" required>
                                <option value="">Seleccione</option>
                                <option value="SERVICIO_PUBLICO" {{ old('tipo_licencia', $constancia->tipo_licencia) == 'SERVICIO_PUBLICO' ? 'selected' : '' }}>Servicio Público</option>
                                <option value="AUTOMOVILISTA" {{ old('tipo_licencia', $constancia->tipo_licencia) == 'AUTOMOVILISTA' ? 'selected' : '' }}>Automovilista</option>
                                <option value="CHOFER" {{ old('tipo_licencia', $constancia->tipo_licencia) == 'CHOFER' ? 'selected' : '' }}>Chofer</option>
                                <option value="MOTOCICLISTA" {{ old('tipo_licencia', $constancia->tipo_licencia) == 'MOTOCICLISTA' ? 'selected' : '' }}>Motociclista</option>
                                <option value="PERMISO" {{ old('tipo_licencia', $constancia->tipo_licencia) == 'PERMISO' ? 'selected' : '' }}>Permiso</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Total preguntas</label>
                            <input type="number" name="total_preguntas" class="form-control" min="1" value="{{ old('total_preguntas', optional($constancia->examen)->total_preguntas ?? 20) }}" required>
                        </div>

                        <div class="form-group">
                            <label>Aciertos</label>
                            <input type="number" name="aciertos" class="form-control" min="0" value="{{ old('aciertos', optional($constancia->examen)->aciertos ?? 0) }}" required>
                        </div>

                        <div class="form-group">
                            <label>Errores</label>
                            <input type="number" name="errores" class="form-control" min="0" value="{{ old('errores', optional($constancia->examen)->errores ?? 0) }}" required>
                        </div>

                        <div class="form-group">
                            <label>Calificación</label>
                            <input type="number" name="calificacion" class="form-control" min="0" max="100" step="0.01" value="{{ old('calificacion', optional($constancia->examen)->calificacion) }}" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="3">{{ old('observaciones', optional($constancia->examen)->observaciones) }}</textarea>
                    </div>
                </div>

                <div class="constancia-show-card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Guardar examen impreso
                    </button>
                </div>
            </form>
        </div>
    @endif

</div>
@stop

@section('css')
<style>
.constancia-show-header {
    text-align: center;
    margin-bottom: 20px;
}

.constancia-show-header h1 {
    font-weight: 800;
    margin-bottom: 4px;
    color: #f8fafc;
}

.constancia-show-header p {
    color: #cbd5e1;
    margin: 0;
}

.constancia-show-wrapper {
    max-width: 1150px;
    margin: 0 auto;
    padding-bottom: 35px;
}

.constancia-show-card {
    background: #0f172a;
    border: 1px solid #334155;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 18px 40px rgba(0, 0, 0, 0.6);
}

.constancia-show-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #1e293b;
    border-bottom: 1px solid #334155;
    padding: 22px 26px;
}

.constancia-show-card-header h3 {
    margin: 0;
    font-size: 22px;
    font-weight: 800;
    color: #f8fafc;
}

.constancia-show-card-header span {
    color: #cbd5e1;
}

.constancia-show-card-header i {
    color: #60a5fa;
    font-size: 32px;
}

.constancia-show-card-body {
    padding: 26px;
}

.constancia-show-card-footer {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 10px;
    padding: 20px 26px 26px;
    border-top: 1px solid #334155;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
}

.info-item {
    background: #020617;
    border: 1px solid #334155;
    border-radius: 14px;
    padding: 14px;
}

.info-item label {
    display: block;
    color: #94a3b8;
    font-size: 13px;
    margin-bottom: 6px;
}

.info-item strong {
    color: #f8fafc;
    font-size: 15px;
}

.badge-status {
    font-size: 15px;
    padding: 10px 14px;
    border-radius: 12px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
}

.form-group label {
    color: #e2e8f0;
    font-weight: 700;
}

.form-control {
    background-color: #020617 !important;
    border: 1px solid #3b82f6 !important;
    color: #ffffff !important;
    border-radius: 12px !important;
    min-height: 44px;
    font-weight: 600;
}

.form-control:focus {
    background-color: #020617 !important;
    color: #ffffff !important;
    border-color: #60a5fa !important;
    box-shadow: 0 0 0 0.15rem rgba(96, 165, 250, 0.3) !important;
}

select.form-control,
select.form-control option {
    background-color: #020617 !important;
    color: #ffffff !important;
}

.access-box {
    background: #020617;
    border: 1px solid #334155;
    border-radius: 14px;
    padding: 18px;
}

.access-box label {
    color: #e2e8f0;
    font-weight: 700;
}

.access-box small {
    display: block;
    margin-top: 8px;
    color: #cbd5e1;
}

.empty-box {
    background: #020617;
    border: 1px solid #334155;
    border-radius: 14px;
    padding: 18px;
    color: #cbd5e1;
    font-weight: 600;
    text-align: center;
}

.btn {
    border-radius: 12px;
    font-weight: 700;
    padding: 10px 18px;
}

.form-inline {
    display: inline-block;
}

@media (max-width: 992px) {
    .info-grid,
    .form-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 576px) {
    .info-grid,
    .form-grid {
        grid-template-columns: 1fr;
    }

    .constancia-show-card-header {
        flex-direction: column;
        gap: 10px;
        text-align: center;
    }
}
</style>
@stop

@section('js')
<script>
$(document).on('click', '.btn-copy', function () {
    navigator.clipboard.writeText($(this).data('url'));

    Swal.fire({
        icon: 'success',
        title: 'Enlace copiado',
        timer: 1800,
        showConfirmButton: false
    });
});

$(document).on('click', '.btn-activar', function (e) {
    e.preventDefault();

    let form = $(this).closest('form');

    Swal.fire({
        title: '¿Activar constancia?',
        text: 'A partir de este momento comenzarán los 10 días de vigencia.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, activar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
});

$(document).on('click', '.btn-cancelar', function (e) {
    e.preventDefault();

    let form = $(this).closest('form');

    Swal.fire({
        title: '¿Cancelar constancia?',
        text: 'Esta constancia ya no podrá utilizarse.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
});

@if (session('success'))
Swal.fire({
    icon: 'success',
    title: '{{ session('success') }}',
    timer: 3000,
    showConfirmButton: false
});
@endif

@if (session('error'))
Swal.fire({
    icon: 'error',
    title: '{{ session('error') }}',
    timer: 3500,
    showConfirmButton: false
});
@endif
</script>
@stop
