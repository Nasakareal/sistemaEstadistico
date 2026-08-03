@extends('adminlte::page')

@section('title', 'Examen ' . $solicitud->folio_examen)

@section('content_header')
    <h1>Examen {{ $solicitud->folio_examen }}</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Información del examen</h3>
                    <div class="card-tools">
                        @if($solicitud->estatus === 'APROBADO')
                            <span class="badge badge-success">APROBADO</span>
                        @elseif($solicitud->estatus === 'REPROBADO')
                            <span class="badge badge-danger">REPROBADO</span>
                        @else
                            <span class="badge badge-warning">PENDIENTE</span>
                        @endif
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <strong>Folio examen:</strong><br>
                            {{ $solicitud->folio_examen }}
                        </div>
                        <div class="col-md-4 mb-3">
                            <strong>Módulo:</strong><br>
                            {{ optional($solicitud->modulo)->nombre ?? 'N/A' }}
                        </div>
                        <div class="col-md-4 mb-3">
                            <strong>Modalidad:</strong><br>
                            {{ $solicitud->modalidad === 'LINEA' ? 'En línea' : 'Impreso' }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Solicitante:</strong><br>
                            {{ $solicitud->nombre_solicitante }}
                        </div>
                        <div class="col-md-3 mb-3">
                            <strong>Sexo:</strong><br>
                            {{ $solicitud->sexo }}
                        </div>
                        <div class="col-md-3 mb-3">
                            <strong>Licencia:</strong><br>
                            {{ $tiposLicencia[$solicitud->tipo_licencia] ?? $solicitud->tipo_licencia }}
                        </div>
                        <div class="col-md-4 mb-3">
                            <strong>CURP:</strong><br>
                            {{ $solicitud->curp ?? 'N/A' }}
                        </div>
                        <div class="col-md-4 mb-3">
                            <strong>Teléfono:</strong><br>
                            {{ $solicitud->telefono ?? 'N/A' }}
                        </div>
                        <div class="col-md-4 mb-3">
                            <strong>Fecha examen:</strong><br>
                            {{ $solicitud->fecha_examen ? $solicitud->fecha_examen->format('d-m-Y H:i') : 'N/A' }}
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <strong>Calificación:</strong><br>
                            {{ $solicitud->calificacion ?? 'N/A' }}
                        </div>
                        <div class="col-md-3 mb-3">
                            <strong>Total:</strong><br>
                            {{ $solicitud->total_preguntas }}
                        </div>
                        <div class="col-md-3 mb-3">
                            <strong>Aciertos:</strong><br>
                            {{ $solicitud->aciertos }}
                        </div>
                        <div class="col-md-3 mb-3">
                            <strong>Errores:</strong><br>
                            {{ $solicitud->errores }}
                        </div>
                    </div>

                    @if($solicitud->constancia)
                        <div class="alert alert-success mb-0">
                            Activado en constancia
                            <a href="{{ route('constancias_manejo.show', $solicitud->constancia) }}">
                                {{ $solicitud->constancia->folio }}
                            </a>.
                        </div>
                    @endif
                </div>

                <div class="card-footer">
                    <a href="{{ route('constancias_manejo.examenes.index') }}" class="btn btn-secondary">
                        <i class="fa-solid fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>

            @if($solicitud->modalidad === 'IMPRESO' && !$solicitud->constancia_id && $solicitud->estatus === 'PENDIENTE')
                <div class="card card-outline card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">Capturar resultado escrito</h3>
                    </div>
                    <form method="POST" action="{{ route('constancias_manejo.examenes.capturar_impreso', $solicitud) }}">
                        @csrf
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Total preguntas</label>
                                        <input type="number" name="total_preguntas" class="form-control" min="1" value="{{ old('total_preguntas', 20) }}" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Aciertos</label>
                                        <input type="number" name="aciertos" class="form-control" min="0" value="{{ old('aciertos', 0) }}" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Errores</label>
                                        <input type="number" name="errores" class="form-control" min="0" value="{{ old('errores', 0) }}" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group mb-0">
                                <label>Observaciones</label>
                                <textarea name="observaciones" class="form-control" rows="2">{{ old('observaciones') }}</textarea>
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <button class="btn btn-primary">
                                <i class="fa-solid fa-save"></i> Guardar resultado
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            @if($solicitud->estatus === 'APROBADO' && !$solicitud->constancia_id)
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <h3 class="card-title">Activar constancia impresa</h3>
                    </div>
                    <form method="POST" action="{{ route('constancias_manejo.examenes.activar', $solicitud) }}">
                        @csrf
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Constancia disponible</label>
                                        <select name="constancia_id" class="form-control">
                                            <option value="">Seleccionar por lista</option>
                                            @foreach($constanciasDisponibles as $constancia)
                                                <option value="{{ $constancia->id }}" {{ old('constancia_id') == $constancia->id ? 'selected' : '' }}>
                                                    {{ $constancia->folio }} - {{ optional($constancia->modulo)->nombre ?? 'Sin módulo' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>QR de constancia</label>
                                        <input type="text" name="constancia_qr" class="form-control" value="{{ old('constancia_qr') }}" placeholder="Escanear o pegar token/URL">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <button type="submit" class="btn btn-success btn-activar">
                                <i class="fa-solid fa-check"></i> Activar constancia
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">Acceso al examen</h3>
                </div>
                <div class="card-body text-center">
                    @if($solicitud->modalidad === 'IMPRESO')
                        <a href="{{ url('/constancias-manejo/examenes/' . $solicitud->getRouteKey() . '/descargar-pdf') }}" class="btn btn-success btn-lg btn-block mb-3">
                            <i class="fa-solid fa-file-pdf"></i> Descargar examen PDF
                        </a>
                    @endif

                    @if($tokenVigente && $qrBase64)
                        <img src="{{ $qrBase64 }}" alt="QR examen" class="img-fluid mb-3" style="max-width: 230px;">
                        <input type="text" class="form-control mb-2" value="{{ $urlExamen }}" readonly>
                        <button type="button" class="btn btn-primary btn-copy" data-url="{{ $urlExamen }}">
                            <i class="fa-regular fa-copy"></i> Copiar enlace
                        </button>
                        <a href="{{ $urlExamen }}" target="_blank" class="btn btn-dark">
                            <i class="fa-solid fa-up-right-from-square"></i> Vista para imprimir
                        </a>
                        <div class="text-muted mt-3">
                            Expira: {{ $solicitud->token_expira ? $solicitud->token_expira->format('d-m-Y H:i') : 'N/A' }}
                        </div>
                    @elseif($solicitud->estatus === 'PENDIENTE')
                        <div class="alert alert-warning mb-0">
                            El acceso ya expiró. Genera un nuevo examen para reintentar.
                        </div>
                    @else
                        <div class="alert alert-secondary mb-0">
                            El examen ya fue contestado.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
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
    const form = $(this).closest('form');

    Swal.fire({
        title: '¿Activar constancia?',
        text: 'La vigencia empezará en este momento.',
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
