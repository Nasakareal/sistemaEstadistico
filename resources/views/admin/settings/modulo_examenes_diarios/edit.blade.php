a@extends('adminlte::page')

@section('title', 'Editar Examen Diario')

@section('content_header')
    <h1>Editar Examen Diario</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title">Modificar Registro</h3>
            </div>

            <div class="card-body">
                <form action="{{ route('modulo_examenes_diarios.update', $registro->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Fecha</label>
                                <input type="date"
                                       name="fecha"
                                       class="form-control @error('fecha') is-invalid @enderror"
                                       value="{{ old('fecha', $registro->fecha->format('Y-m-d')) }}"
                                       required>
                                @error('fecha')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Módulo</label>
                                <input type="text"
                                       name="modulo_nombre"
                                       class="form-control @error('modulo_nombre') is-invalid @enderror"
                                       value="{{ old('modulo_nombre', $registro->modulo_nombre) }}"
                                       required>
                                @error('modulo_nombre')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Informado por</label>
                                <input type="text"
                                       name="informado_por"
                                       class="form-control @error('informado_por') is-invalid @enderror"
                                       value="{{ old('informado_por', $registro->informado_por) }}">
                                @error('informado_por')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <hr>
                    <h5><strong>Tipos de Examen</strong></h5>

                    <div class="row">
                        @foreach ([
                            'servicio_publico' => 'Servicio Público',
                            'automovilista' => 'Automovilista',
                            'chofer' => 'Chofer',
                            'motociclista' => 'Motociclista',
                            'permiso' => 'Permiso',
                            'total' => 'Total'
                        ] as $campo => $label)
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>{{ $label }}</label>
                                <input type="number"
                                       name="{{ $campo }}"
                                       class="form-control @error($campo) is-invalid @enderror"
                                       value="{{ old($campo, $registro->$campo) }}"
                                       min="0">
                                @error($campo)
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <hr>
                    <h5><strong>Resultados</strong></h5>

                    <div class="row">
                        @foreach ([
                            'hombres' => 'Hombres',
                            'mujeres' => 'Mujeres',
                            'aprobados' => 'Aprobados',
                            'reprobados' => 'Reprobados'
                        ] as $campo => $label)
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>{{ $label }}</label>
                                <input type="number"
                                       name="{{ $campo }}"
                                       class="form-control @error($campo) is-invalid @enderror"
                                       value="{{ old($campo, $registro->$campo) }}"
                                       min="0">
                                @error($campo)
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Folios</label>
                                <input type="text"
                                       name="folios"
                                       class="form-control @error('folios') is-invalid @enderror"
                                       value="{{ old('folios', $registro->folios) }}">
                                @error('folios')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="form-group">
                        <button type="submit" class="btn btn-warning">
                            <i class="fa-solid fa-save"></i> Actualizar
                        </button>

                        <a href="{{ route('modulo_examenes_diarios.index') }}" class="btn btn-secondary">
                            Cancelar
                        </a>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
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
