@extends('adminlte::page')

@section('title', 'Detalles del Destacamento')

@section('content_header')
    <h1>Detalles del Destacamento</h1>
@stop

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-10">

            <div class="card card-outline card-info">

                <div class="card-header">
                    <h3 class="card-title">Datos Registrados</h3>

                    <div class="card-tools">
                        <a href="{{ route('destacamentos.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </a>

                        @can('editar destacamentos')
                            <a href="{{ route('destacamentos.edit', $destacamento->id) }}" class="btn btn-success btn-sm">
                                <i class="fa-regular fa-pen-to-square"></i> Editar
                            </a>
                        @endcan
                    </div>
                </div>

                <div class="card-body">

                    <div class="row">

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Clave</label>
                                <p class="form-control-static">{{ $destacamento->clave ?? 'Sin clave' }}</p>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Nombre</label>
                                <p class="form-control-static">{{ $destacamento->nombre }}</p>
                            </div>
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Municipio</label>
                                <p class="form-control-static">{{ $destacamento->municipio ?? 'No especificado' }}</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Unidad</label>
                                <p class="form-control-static">{{ optional($destacamento->unidad)->nombre ?? 'No especificada' }}</p>
                            </div>
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Estado</label>
                                <p class="form-control-static">
                                    @if($destacamento->activo)
                                        <span class="badge badge-success">ACTIVO</span>
                                    @else
                                        <span class="badge badge-secondary">INACTIVO</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Fecha de Creación</label>
                                <p class="form-control-static">{{ optional($destacamento->created_at)->format('d-m-Y H:i') }}</p>
                            </div>
                        </div>

                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-12 text-center">
                            <a href="{{ route('destacamentos.index') }}" class="btn btn-secondary">
                                <i class="fa-solid fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </div>
@stop

@section('css')
    <style>
        .form-group label { font-weight: bold; }
        .form-control-static {
            display: block;
            font-size: 1rem;
            margin-top: 0.4rem;
        }
    </style>
@stop

@section('js')
<script>
    console.log('Vista show de destacamento cargada correctamente');
</script>
@stop
