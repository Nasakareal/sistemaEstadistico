@extends('adminlte::page')

@section('title', 'Crear Examen Diario')

@section('content_header')
    <h1>Registro de Examen Diario (Módulo Licencia)</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Llene los Datos</h3>
                </div>

                <div class="card-body">
                    <form action="{{ route('modulo_examenes_diarios.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            {{-- Fecha --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="fecha">Fecha</label>
                                    <input
                                        type="date"
                                        name="fecha"
                                        id="fecha"
                                        class="form-control @error('fecha') is-invalid @enderror"
                                        value="{{ old('fecha', now()->format('Y-m-d')) }}"
                                        required
                                    >
                                    @error('fecha')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Módulo --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="modulo_nombre">Módulo</label>
                                    <input
                                        type="text"
                                        name="modulo_nombre"
                                        id="modulo_nombre"
                                        class="form-control @error('modulo_nombre') is-invalid @enderror"
                                        value="{{ old('modulo_nombre') }}"
                                        placeholder="Ej. MODULO A, MODULO TARASCO, etc."
                                        required
                                    >
                                    @error('modulo_nombre')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Informado por --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="informado_por">Informado por</label>
                                    <input
                                        type="text"
                                        name="informado_por"
                                        id="informado_por"
                                        class="form-control @error('informado_por') is-invalid @enderror"
                                        value="{{ old('informado_por', auth()->user()->name ?? '') }}"
                                        placeholder="Nombre de quien reporta"
                                    >
                                    @error('informado_por')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h5 class="mb-3"><strong>Tipos de Examen</strong></h5>

                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="servicio_publico">Servicio Público</label>
                                    <input
                                        type="number"
                                        name="servicio_publico"
                                        id="servicio_publico"
                                        class="form-control @error('servicio_publico') is-invalid @enderror"
                                        value="{{ old('servicio_publico', 0) }}"
                                        min="0"
                                    >
                                    @error('servicio_publico')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="automovilista">Automovilista</label>
                                    <input
                                        type="number"
                                        name="automovilista"
                                        id="automovilista"
                                        class="form-control @error('automovilista') is-invalid @enderror"
                                        value="{{ old('automovilista', 0) }}"
                                        min="0"
                                    >
                                    @error('automovilista')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="chofer">Chofer</label>
                                    <input
                                        type="number"
                                        name="chofer"
                                        id="chofer"
                                        class="form-control @error('chofer') is-invalid @enderror"
                                        value="{{ old('chofer', 0) }}"
                                        min="0"
                                    >
                                    @error('chofer')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="motociclista">Motociclista</label>
                                    <input
                                        type="number"
                                        name="motociclista"
                                        id="motociclista"
                                        class="form-control @error('motociclista') is-invalid @enderror"
                                        value="{{ old('motociclista', 0) }}"
                                        min="0"
                                    >
                                    @error('motociclista')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="permiso">Permiso</label>
                                    <input
                                        type="number"
                                        name="permiso"
                                        id="permiso"
                                        class="form-control @error('permiso') is-invalid @enderror"
                                        value="{{ old('permiso', 0) }}"
                                        min="0"
                                    >
                                    @error('permiso')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="total">Total</label>
                                    <input
                                        type="number"
                                        name="total"
                                        id="total"
                                        class="form-control @error('total') is-invalid @enderror"
                                        value="{{ old('total', 0) }}"
                                        min="0"
                                        readonly
                                    >
                                    @error('total')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h5 class="mb-3"><strong>Resultados</strong></h5>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="hombres">Hombres</label>
                                    <input
                                        type="number"
                                        name="hombres"
                                        id="hombres"
                                        class="form-control @error('hombres') is-invalid @enderror"
                                        value="{{ old('hombres', 0) }}"
                                        min="0"
                                    >
                                    @error('hombres')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="mujeres">Mujeres</label>
                                    <input
                                        type="number"
                                        name="mujeres"
                                        id="mujeres"
                                        class="form-control @error('mujeres') is-invalid @enderror"
                                        value="{{ old('mujeres', 0) }}"
                                        min="0"
                                    >
                                    @error('mujeres')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="aprobados">Aprobados</label>
                                    <input
                                        type="number"
                                        name="aprobados"
                                        id="aprobados"
                                        class="form-control @error('aprobados') is-invalid @enderror"
                                        value="{{ old('aprobados', 0) }}"
                                        min="0"
                                    >
                                    @error('aprobados')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="reprobados">Reprobados</label>
                                    <input
                                        type="number"
                                        name="reprobados"
                                        id="reprobados"
                                        class="form-control @error('reprobados') is-invalid @enderror"
                                        value="{{ old('reprobados', 0) }}"
                                        min="0"
                                    >
                                    @error('reprobados')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            {{-- Folios --}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="folios">Folios</label>
                                    <input
                                        type="text"
                                        name="folios"
                                        id="folios"
                                        class="form-control @error('folios') is-invalid @enderror"
                                        value="{{ old('folios') }}"
                                        placeholder="Ej. 1201-1210, 1215, 1220"
                                    >
                                    @error('folios')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa-solid fa-check"></i> Guardar
                                    </button>

                                    <a href="{{ route('modulo_examenes_diarios.index') }}" class="btn btn-secondary">
                                        <i class="fa-solid fa-ban"></i> Cancelar
                                    </a>
                                </div>
                            </div>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </div>
@stop

@section('css')
<style>
    .form-group label { font-weight: bold; }
</style>
@stop

@section('js')
<script>
    function actualizarTotalExamenes() {
        const campos = ['servicio_publico', 'automovilista', 'chofer', 'motociclista', 'permiso'];
        const total = campos.reduce((suma, campo) => {
            const valor = parseInt(document.getElementById(campo)?.value || '0', 10);
            return suma + (Number.isNaN(valor) ? 0 : valor);
        }, 0);
        const totalInput = document.getElementById('total');
        if (totalInput) {
            totalInput.value = total;
        }
    }

    ['servicio_publico', 'automovilista', 'chofer', 'motociclista', 'permiso'].forEach((campo) => {
        document.getElementById(campo)?.addEventListener('input', actualizarTotalExamenes);
    });

    actualizarTotalExamenes();
</script>

@if ($errors->any())
    <script>
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
    </script>
@endif
@stop
